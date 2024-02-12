<?php
namespace Controller\Template;

use BlocksEdit\Http\Annotations\InjectTemplate;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Integrations\FilesystemIntegrationInterface;
use BlocksEdit\Integrations\Hook;
use BlocksEdit\Integrations\HookDispatcher;
use BlocksEdit\System\Serializer;
use Entity\Source;
use Entity\TemplateLinkParam;
use Entity\TemplateSource;
use Entity\User;
use Exception;
use Repository\UserRepository;
use Repository\Exception\ChangePasswordException;
use Repository\Exception\UpdateException;
use Repository\ChecklistItemRepository;
use Repository\EmailRepository;
use Repository\SourcesRepository;
use Repository\TemplateLinkParamRepository;
use Repository\TemplateSourcesRepository;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"USER"})
 */
class SettingsController extends Controller
{
    /**
     * @IsGranted({"template"})
     * @Route("/api/v1/templates/{id}/settings", name="template_settings", methods={"GET"})
     * @InjectTemplate()
     *
     * @param int                         $oid
     * @param array                       $template
     * @param Request                     $request
     * @param TemplatesRepository         $templatesRepository
     * @param SourcesRepository           $sourcesRepository
     * @param TemplateLinkParamRepository $linkParamRepository
     * @param ChecklistItemRepository     $checklistItemRepository
     * @param HookDispatcher              $dispatcher
     *
     * @return Response
     * @throws Exception
     */
    public function settingsGetAction(
        int $oid,
        array $template,
        Request $request,
        User $user,
        TemplatesRepository $templatesRepository,
        SourcesRepository $sourcesRepository,
        TemplateLinkParamRepository $linkParamRepository,
        ChecklistItemRepository $checklistItemRepository,
        HookDispatcher $dispatcher
    ): Response
    {
        $templates = [$template];
        $sourcesRepository->setTemplatesSources($oid, $templates);

        /** @var Source $source */
        /** @phpstan-ignore-next-line */
        $serialized = [];
        foreach($templates[0]['sources'] as $source) {
            $settings = $sourcesRepository->findSettings($source);
            $s        = $source->toArray();
            if (isset($settings['home_dir']) && (empty($s['homeDir']) || $s['homeDir'] === '/')) {
                $s['homeDir'] = $settings['home_dir'];
            }

            $hook = new Hook('template_settings_pre', $request, [], $template);
            $dispatcher->dispatch($hook, $source->getId());
            if ($hook->getResponses()) {
                $s['extraFields'] = $hook->getResponses();
            }
            $serialized[] = $s;
        }

        $billingPlan = $this->getBillingPlan();
        $isIntegrationsDisabled = false;
        if (!$billingPlan->isCustom()) {
            $isIntegrationsDisabled = (
                $billingPlan->isPaused() || ($billingPlan->isTrialComplete() && !$this->getBillingCreditCard())
            );
        }

        $parameters = [];
        $linkParams = $linkParamRepository->findByTemplate($template['tmp_id']);
        foreach($linkParams as $linkParam) {
            $parameters[] = $linkParam->getParam();
        }

        $template = $templatesRepository->findByID($template['tmp_id'], true);
        $checklistSettings = $template->getChecklistSettings();
        foreach($checklistItemRepository->findTemplates($template) as $item) {
            // $checklistSettings['items'][] = $serializer->serializeChecklistItem($item);
        }

        return $this->json([
            'sources'                => $serialized,
            'parameters'             => $parameters,
            'checklistSettings'      => $checklistSettings,
            'tpaEnabled'             => $template->isTpaEnabled(),
            'tmpAliasEnabled'        => $template->isAliasEnabled(),
            'isIntegrationsDisabled' => $isIntegrationsDisabled
        ]);
    }

    /**
     * @IsGranted({"template"})
     * @Route("/api/v1/templates/{id}/settings", name="template_settings_save", methods={"POST"})
     * @InjectTemplate()
     *
     * @param int                         $id
     * @param array                       $template
     * @param Request                     $request
     * @param HookDispatcher              $dispatcher
     * @param SourcesRepository           $sourcesRepository
     * @param EmailRepository             $emailRepository
     * @param TemplatesRepository         $templatesRepository
     * @param TemplateSourcesRepository   $templateSourcesRepository
     * @param TemplateLinkParamRepository $linkParamRepository
     * @param ChecklistItemRepository     $checklistItemRepository
     * @param User                        $user
     * @param UserRepository              $userRepository
     * @return JsonResponse
     * @throws Exception
     */
    public function settingsSaveAction(
        int $id,
        array $template,
        Request $request,
        HookDispatcher $dispatcher,
        SourcesRepository $sourcesRepository,
        EmailRepository $emailRepository,
        TemplatesRepository $templatesRepository,
        TemplateSourcesRepository $templateSourcesRepository,
        TemplateLinkParamRepository $linkParamRepository,
        ChecklistItemRepository $checklistItemRepository,
        User $user,
        UserRepository $userRepository
    ): JsonResponse {
        $homeDirs          = $request->json->getArray('homeDirs');
        $enables           = $request->json->getArray('enabled');
        $params            = $request->json->getArray('parameters');
        $tpaEnabled        = $request->json->getBoolean('tpaEnabled');
        $tmpAliasEnabled   = $request->json->getBoolean('tmpAliasEnabled');
        $checklistSettings = $request->json->getArray('checklistSettings');
        $checklistItems    = $request->json->getArray('checklistItems');
        $userID            = $request->json->getArray('userID')['0'];
        $editedTemplateSettings = $request->json->getArray('editedTemplateSettings')['0'];

        $selectedUser = $userRepository->findByID($userID); 
        $oid               = $template['tmp_org_id'];
        $sources           = $sourcesRepository->findByOrg($oid);
        foreach($sources as $source) {
            if (!isset($enables[$source->getId()])) {
                $enables[$source->getId()] = 0;
            }
        }

        foreach($enables as $iid => $enabled) {
            $source = $sourcesRepository->findByID($iid);
            if (!$source || $source->getOrgId() != $oid) {
                continue;
            }

            $homeDir     = '';
            $integration = $source->getIntegration();
            if ($integration instanceof FilesystemIntegrationInterface) {
                $homeDir = !empty($homeDirs[$iid])
                    ? $homeDirs[$iid]
                    : $integration->getDefaultHomeDirectory();
            }
            $templateSource = $templateSourcesRepository->findByTemplateAndSource($id, $iid);
            if (!$templateSource) {
                $templateSource = (new TemplateSource())
                    ->setTmpId($id)
                    ->setSrcId($iid)
                    ->setHomeDir($homeDir)
                    ->setIsEnabled($enabled);
                $templateSourcesRepository->insert($templateSource);
            } else  {
                $templateSource
                    ->setIsEnabled($enabled)
                    ->setHomeDir($homeDir);
                $templateSourcesRepository->update($templateSource);
            }
        }

        // Let integrations save the form values they added to the settings form.
        $hook = new Hook('template_settings_post', $request, [], $template);
        $dispatcher->dispatch($hook);
        foreach($hook->getDispatchedSources() as $source) {
            $integration = $source->getIntegration();
            $settings    = $integration->getSettings();
            $sourcesRepository->updateSettings($source, $settings);
            $sourcesRepository->update($source);
        }

        $linkParams = $linkParamRepository->findByTemplate($template['tmp_id']);
        foreach($linkParams as $linkParam) {
            $found = false;
            foreach($params as $i => $param) {
                if ($param === $linkParam->getParam()) {
                    unset($params[$i]);
                    $found = true;
                }
            }
            if (!$found) {
                $linkParamRepository->delete($linkParam);
            }
        }

        foreach($params as $param) {
            $linkParam = (new TemplateLinkParam())
                ->setTmpId($template['tmp_id'])
                ->setParam($param);
            $linkParamRepository->insert($linkParam);
        }

        $templatesRepository->updateTpaEnabled($template['tmp_id'], $tpaEnabled);
        $templatesRepository->updateAliasEnabled($template['tmp_id'], $tmpAliasEnabled);

        if ($tpaEnabled || $tmpAliasEnabled) {
            $emails = $emailRepository->findByTemplate($template['tmp_id']);
            if ($tpaEnabled) {
                foreach($emails as $email) {
                    $emailRepository->updateEpaEnabled($email['ema_id'], true);
                }
            }
            if ($tmpAliasEnabled) {
                foreach($emails as $email) {
                    $emailRepository->updateAliasEnabled($email['ema_id'], true);
                }
            }
        }

        $template = $templatesRepository->findByID($template['tmp_id'], true);
        
        //if a user edited template settings then set that value to true in the database
        if ($editedTemplateSettings) {
            $user->setEditedTemplateSettings($editedTemplateSettings);
            $userRepository->update($user);
        }

        $template->setChecklistSettings($checklistSettings);
        $templatesRepository->update($template);

        return $this->json([
            'checklistSettings' => $checklistSettings,
            'tpaEnabled' => $template->isTpaEnabled(),
            'tmpAliasEnabled' => $template->isAliasEnabled(),
        ]);
    }
}
