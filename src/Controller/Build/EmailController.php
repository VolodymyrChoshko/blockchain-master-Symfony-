<?php
namespace Controller\Build;

use BlocksEdit\Html\DomParser;
use BlocksEdit\Html\Imagify;
use BlocksEdit\Html\Scriptify;
use BlocksEdit\Html\Utils;
use BlocksEdit\Http\Annotations\InjectEmail;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\RouteGenerator;
use BlocksEdit\Integrations\Hook;
use BlocksEdit\Integrations\HookDispatcher;
use BlocksEdit\System\Serializer;
use Entity\Email;
use Entity\EmailLinkParam;
use Entity\User;
use Exception;
use Repository\AccessRepository;
use Repository\ChecklistItemRepository;
use Repository\CommentRepository;
use Repository\ComponentsRepository;
use Repository\EmailHistoryRepository;
use Repository\EmailLinkParamRepository;
use Repository\EmailRepository;
use Repository\Exception\CreateTemplateException;
use Repository\ImagesRepository;
use Repository\OrganizationAccessRepository;
use Repository\PinGroupRepository;
use Repository\SectionLibraryRepository;
use Repository\SectionsRepository;
use Repository\TemplateLinkParamRepository;
use Repository\TemplatesRepository;
use Service\Mentions;
use simplehtmldom_1_5\simple_html_dom;

/**
 * @IsGranted({"USER"})
 */
class EmailController extends BuildController
{
    /**
     * @Route("/build/email/{id<\d+>}/html", name="build_email_get_html", methods={"GET"})
     *
     * @param int             $id
     * @param EmailRepository $emailRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function htmlGetAction(
        int $id,
        EmailRepository $emailRepository
    ): JsonResponse
    {
        $email = $emailRepository->findByID($id, true);
        $html  = $emailRepository->getHtml($id)->getHtml();

        return $this->json([
            'html'   => $html,
            'title'  => $email->getTitle(),
        ]);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/email/{tid<\d+>}/{id<\d+>}", name="build_email", methods={"GET"})
     * @InjectEmail()
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(Request $request): Response {
        return $this->renderFrontend($request);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/email/{tid<\d+>}/{id<\d+>}/versions/{version<\d+>}", name="build_email_version", methods={"GET"})
     * @InjectEmail()
     * @throws Exception
     */
    public function versionAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/email/{id<\d+>}", name="build_save", methods={"POST"})
     * @InjectEmail()
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param User|null                    $user
     * @param array                        $email
     * @param Request                      $request
     * @param EmailRepository              $emailRepository
     * @param TemplatesRepository          $templatesRepository
     * @param EmailHistoryRepository       $emailHistoryRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     * @param Serializer                   $serializer
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function saveAction(
        int $uid,
        int $oid,
        ?User $user,
        array $email,
        Request $request,
        EmailRepository $emailRepository,
        TemplatesRepository $templatesRepository,
        EmailHistoryRepository $emailHistoryRepository,
        OrganizationAccessRepository $organizationAccessRepository,
        Serializer $serializer
    ): JsonResponse {
        try {
            $html            = $request->json->get('html');
            $title           = $request->json->get('title');
            $version         = $request->json->getInt('version');
            $templateVersion = $request->json->getInt('templateVersion');

            $dom = DomParser::fromString($html);
            foreach($dom->find('strongr') as $item) {
                $item->tag = 'strong';
            }

            $emailHistory = $emailRepository->save(
                $uid,
                $oid,
                $email['ema_id'],
                (string)$dom,
                $title,
                $version,
                $templateVersion
            );

            $template = $templatesRepository->findByID($email['ema_tmp_id']);
            $isOwner  = $user && $organizationAccessRepository->isOwner($user->getId(), $template['tmp_org_id']);
            $isAdmin  = $user && $organizationAccessRepository->isAdmin($user->getId(), $template['tmp_org_id']);

            $history        = [];
            $emailHistories = $emailHistoryRepository->findByEmail($email['ema_id']);
            foreach ($emailHistories as $item) {
                if ($item->getVersion() !== 0) {
                    $item = $serializer->serializeEmailHistory($item);
                    $item['user']['isOwner'] = $isOwner;
                    $item['user']['isAdmin'] = $isAdmin;
                    $history[] = $item;
                }
            }

            $dom = DomParser::fromString($emailHistory->getHtml());
            foreach($dom->find('strongr') as $item) {
                // $item->tag = 'strong';
            }
            // $imagify->convert($dom, $email['ema_token']);
            /*if ($version && $emailHistory->getParentId()) {
                $version    = $emailHistory->getVersion();
                $previewUrl = $this->url('build_email_preview_version', [
                    'id'      => $email['ema_id'],
                    'tid'     => $email['ema_tmp_id'],
                    'token'   => $email['ema_token'],
                    'version' => $version
                ]);

                return $this->json([
                    'history'    => $history,
                    'version'    => $version,
                    'previewUrl' => $previewUrl,
                    'html'       => (string)$dom
                ]);
            }*/

            $previewUrl = $this->url('build_email_preview', [
                'id'      => $email['ema_id'],
                'tid'     => $email['ema_tmp_id'],
                'token'   => $email['ema_token'],
            ]);

            $html = (string)$dom;
            $html = str_replace('<strongr>', '', $html);
            $html = str_replace('</strongr>', '', $html);

            return $this->json([
                'history'    => $history,
                'version'    => $emailHistory->getVersion(),
                'previewUrl' => $previewUrl,
                'html'       => $html
            ]);
        } catch (CreateTemplateException $e) {
            $this->logger->error($e->getMessage());
            return $this->json([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @IsGranted({"ANY"})
     * @Route("/build/email/{id<\d+>}/html", name="build_email_html", methods={"POST"})
     * @InjectEmail()
     *
     * @param Email                        $email
     * @param int                          $id
     * @param int                          $oid
     * @param User|null                    $user
     * @param Request                      $request
     * @param Imagify                      $imagify
     * @param Scriptify                    $scriptify
     * @param PinGroupRepository           $pinGroupRepository
     * @param TemplatesRepository          $templatesRepository
     * @param EmailRepository              $emailRepository
     * @param ComponentsRepository         $componentsRepository
     * @param SectionsRepository           $sectionsRepository
     * @param SectionLibraryRepository     $sectionLibraryRepository
     * @param TemplateLinkParamRepository  $linkParamRepository
     * @param EmailLinkParamRepository     $emailLinkParamRepository
     * @param EmailHistoryRepository       $emailHistoryRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     * @param ChecklistItemRepository      $checklistItemRepository
     * @param CommentRepository            $commentRepository
     * @param RouteGenerator               $routeGenerator
     * @param Serializer                   $serializer
     * @param Mentions                     $mentions
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function htmlAction(
        Email $email,
        int $id,
        int $oid,
        ?User $user,
        Request $request,
        Imagify $imagify,
        Scriptify $scriptify,
        PinGroupRepository $pinGroupRepository,
        TemplatesRepository $templatesRepository,
        EmailRepository $emailRepository,
        ComponentsRepository $componentsRepository,
        SectionsRepository $sectionsRepository,
        SectionLibraryRepository $sectionLibraryRepository,
        TemplateLinkParamRepository $linkParamRepository,
        EmailLinkParamRepository $emailLinkParamRepository,
        EmailHistoryRepository $emailHistoryRepository,
        OrganizationAccessRepository $organizationAccessRepository,
        ChecklistItemRepository $checklistItemRepository,
        CommentRepository $commentRepository,
        RouteGenerator $routeGenerator,
        Serializer $serializer,
        Mentions $mentions
    ): JsonResponse {
        $previewToken = $request->query->get('previewToken');
        $version      = $request->query->getInt('version');

        $templateVersion = 0;
        $template = $templatesRepository->findByID($email->getTemplate()->getId(), true);
        if ($template->isTmhEnabled()) {
            $templateVersion = $template->getVersion();
        }

        $components = $componentsRepository->findByTemplateAndVersion($email->getTemplate()->getId(), $templateVersion);
        $sections   = $sectionsRepository->findByTemplateAndVersion($email->getTemplate()->getId(), $templateVersion);
        $layouts    = $emailRepository->findLayouts($id);
        if ($version) {
            $previewUrl = $routeGenerator->generate('build_email_preview_version', [
                'id'      => $id,
                'tid'     => $email->getTemplate()->getId(),
                'token'   => $email->getToken(),
                'version' => $version
            ], 'absolute');
        } else {
            $previewUrl = $routeGenerator->generate('build_email_preview', [
                'id'    => $id,
                'tid'   => $email->getTemplate()->getId(),
                'token' => $email->getToken()
            ], 'absolute');
        }
        if (!$oid) {
            $oid = $template->getOrganization()->getId();
        }

        $templateHtml = $templatesRepository->getHtml($template->getId(), $templateVersion);
        $emailHtml    = $emailRepository->getHtml($id, $version);
        $sources      = $this->getSources($email->getTemplate()->getId(), $oid);
        list($dom)    = $this->getHtmlDomAndGroups($emailHtml->getHtml());
        list(, $groups, $linkStyles) = $this->getHtmlDomAndGroups($templateHtml->getHtml());

        $origDom = $templateHtml->getDom();
        $origDom = $scriptify->restoreScriptTags($origDom);

        $empty = $dom->find('.block-section-empty');
        if ($empty) {
            $style = $empty[0]->getAttribute('style');
            $empty[0]->setAttribute('style', $style . '; height: 250px;');
        }

        // $imagify->convert($dom, $email->getToken());
        if ($version) {
            $imagify->addVersion($dom, $version);
        }
        $this->convertToImageId($id, $dom, $emailHtml->getVersion());

        $datedUpdated = '0';
        if ($template->getUpdatedAt()) {
            $datedUpdated = $template->getUpdatedAt()->format('YmdHis');
        }
        $libraries = $sectionLibraryRepository->findByTemplate($email->getTemplate()->getId());

        $templateParams = [];
        $linkParams     = $linkParamRepository->findByTemplate($email->getTemplate()->getId());
        foreach($linkParams as $linkParam) {
            $templateParams[$linkParam->getId()] = $linkParam->getParam();
        }

        $emailParams = [];
        $linkParams  = $emailLinkParamRepository->findByEmail($id);
        foreach($linkParams as $linkParam) {
            if (isset($templateParams[$linkParam->getTpaId()])) {
                $param               = $templateParams[$linkParam->getTpaId()];
                $emailParams[$param] = $linkParam->getValue();
            }
        }

        $isOwner  = $user && $organizationAccessRepository->isOwner($user->getId(), $template->getOrganization()->getId());
        $isAdmin  = $user && $organizationAccessRepository->isAdmin($user->getId(), $template->getOrganization()->getId());
        $isEditor = !$isOwner && !$isAdmin;

        $history = [];
        $emailHistories = $emailHistoryRepository->findByEmail($id);
        foreach($emailHistories as $emailHistory) {
            if ($emailHistory->getVersion() != 0) {
                $item = $serializer->serializeEmailHistory($emailHistory);
                $item['user']['isOwner'] = $isOwner;
                $item['user']['isAdmin'] = $isAdmin;
                $history[] = $item;
            }
        }

        $checklistItems = [];
        $checklistSettings = $template->getChecklistSettings();
        foreach($checklistItemRepository->findByEmail($email) as $item) {
            $checklistItems[] = $serializer->serializeChecklistItem($item);
        }
        if (empty($checklistItems)) {
            $checklistSettings['enabled'] = false;
        }

        $initialState = $this->getInitialState(
            $user,
            'email',
            $email->getId(),
            $email->getTemplate()->getId(),
            $oid
        );
        $initialState['builder']['grant'] = [
            'granted' => true
        ];
        if ($previewToken) {
            $initialState['builder']['mode'] = 'email_preview';
        }
        if ($user) {
            $initialState['builder']['mode'] = 'email';
        }

        $pinGroups = [];
        foreach($pinGroupRepository->findByTemplate($template) as $pinGroup) {
            $pinGroups[] = $serializer->serializePinGroup($pinGroup);
        }

        $comments = [];
        foreach($commentRepository->findByEmail($email) as $comment) {
            $mentions->updateAll($comment);
            $comments[] = $serializer->serializeComment($comment);
        }

        $people = [];
        foreach($this->getTemplatePeople($email->getTemplate()) as $person) {
            $people[] = $serializer->serializeUserLight($person);
        }

        return $this->json([
            'title'              => $email->getTitle(),
            'html'               => (string)$dom,
            'origHtml'           => (string)$origDom,
            'groups'             => (object)$groups,
            'linkStyles'         => (array)$linkStyles,
            'templateLinkParams' => array_values($templateParams),
            'emailLinkParams'    => (object)$emailParams,
            'tpaEnabled'         => $template->isTpaEnabled(),
            'epaEnabled'         => $email->getEpaEnabled(),
            'tmpAliasEnabled'    => $template->isAliasEnabled(),
            'emaAliasEnabled'    => $email->getAliasEnabled(),
            'tmhEnabled'         => $template->isTmhEnabled(),
            'isEmpty'            => count($empty) !== 0,
            'token'              => $email->getToken(),
            'sources'            => $sources,
            'previewUrl'         => $previewUrl,
            'version'            => ($email->getTmpVersion() ?? 0),
            'components'         => $this->filterComponents($components, $datedUpdated, $templateVersion),
            'sections'           => $this->filterSections($sections, $datedUpdated, $templateVersion),
            'libraries'          => $this->filterLibraries($libraries, $origDom),
            'layouts'            => $this->filterLayouts($layouts, $origDom),
            'checklistItems'     => $checklistItems,
            'checklistSettings'  => $checklistSettings,
            'pinGroups'          => $pinGroups,
            'isOwner'            => $isOwner,
            'isAdmin'            => $isAdmin,
            'isEditor'           => $isEditor,
            'history'            => $history,
            'comments'           => $comments,
            'people'             => $people,
            'templateVersion'    => $templateVersion,
            'emailVersion'       => $emailHtml->getVersion(),
            'initialState'       => $initialState
        ]);
    }

    /**
     * @IsGranted({"ANY"})
     * @Route("/preview/e/{tid<\d+>}/{id<\d+>}/{token}", name="build_email_preview")
     * @InjectEmail()
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function previewAction(Request $request): Response {
        return $this->renderFrontend($request);
    }

    /**
     * @IsGranted({"ANY"})
     * @Route("/preview/e/{tid<\d+>}/{id<\d+>}/{token}/{version<\d+>}", name="build_email_preview_version")
     * @InjectEmail()
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function previewVersionAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/email/{id<\d+>}/settings", name="build_email_settings")
     * @InjectEmail()
     *
     * @param array                       $email
     * @param Request                     $request
     * @param HookDispatcher              $dispatcher
     * @param EmailRepository             $emailRepository
     * @param EmailHistoryRepository      $emailHistoryRepository
     * @param TemplateLinkParamRepository $templateLinkParamRepository
     * @param EmailLinkParamRepository    $emailLinkParamRepository
     * @param TemplatesRepository         $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function settingsAction(
        array $email,
        Request $request,
        HookDispatcher $dispatcher,
        EmailRepository $emailRepository,
        EmailHistoryRepository $emailHistoryRepository,
        TemplateLinkParamRepository $templateLinkParamRepository,
        EmailLinkParamRepository $emailLinkParamRepository,
        TemplatesRepository $templatesRepository
    ): JsonResponse {
        $template = $templatesRepository->findByID($email['ema_tmp_id']);
        if (!$template) {
            $this->throwNotFound();
        }

        $version  = $request->query->getInt('version');
        $dom      = $emailRepository->getHtml($email['ema_id'], $version)->getDom();
        $titles   = $dom->find('title');
        $previews = DomParser::blockQuery($dom, 'block-preview', 0);

        $templateParams = [];
        $linkParams     = $templateLinkParamRepository->findByTemplate($email['ema_tmp_id']);
        foreach($linkParams as $linkParam) {
            $templateParams[$linkParam->getId()] = $linkParam->getParam();
        }

        $latestVersion = $emailHistoryRepository->findLatestVersion($email['ema_id']);
        if ($request->isPost() && $version === $latestVersion) {
            $title           = $request->json->get('title');
            $preview         = $request->json->get('preview');
            $emailLinkParams = $request->json->get('emailLinkParams');
            $epaEnabled      = $request->json->get('epaEnabled');
            $emaAliasEnabled = $request->json->get('emaAliasEnabled');

            if ($title !== null) {
                if ($titles) {
                    $titles[0]->innertext = $title;
                } else {
                    $el = $dom->createElement('title');
                    /** @noinspection PhpUndefinedFieldInspection */
                    $el->innertext = $title;
                    $heads         = $dom->find('head');
                    if ($heads) {
                        $heads[0]->appendChild($el);
                    }
                }
            }
            if ($preview !== null) {
                if ($previews) {
                    $previews->innertext = $preview;
                } else {
                    $el = DomParser::fromString('<div></div>')->firstChild();
                    /** @noinspection PhpUndefinedFieldInspection */
                    $el->innertext = $preview;
                    $el->setAttribute('style', 'display: none;');
                    $el->setAttribute('class', 'block-preview');
                    $body = $dom->find('body', 0);
                    if ($body) {
                        $body->innertext = $el->innertext() . $body->innertext();
                    }
                }
            }
            if ($emailLinkParams !== null) {
                $linkParams = $emailLinkParamRepository->findByEmail($email['ema_id']);
                foreach($emailLinkParams as $param => $value) {
                    $tpa = array_search($param, $templateParams);
                    if ($tpa !== false) {
                        $found = false;
                        foreach($linkParams as $linkParam) {
                            if ($linkParam->getTpaId() === $tpa) {
                                $linkParam->setValue($value);
                                $emailLinkParamRepository->update($linkParam);
                                $found = true;
                            }
                        }
                        if (!$found) {
                            $linkParam = (new EmailLinkParam())
                                ->setEmaId($email['ema_id'])
                                ->setTpaId($tpa)
                                ->setValue($value);
                            $emailLinkParamRepository->insert($linkParam);
                        }
                    }
                }
            }

            if ($epaEnabled !== null) {
                $emailRepository->updateEpaEnabled($email['ema_id'], $epaEnabled);
            }
            if ($emaAliasEnabled !== null) {
                $emailRepository->updateAliasEnabled($email['ema_id'], $emaAliasEnabled);
            }
            $emailRepository->setHtml($email['ema_id'], $version, (string)$dom);

            $hook = new Hook('email_settings_post', $request, $email, $template);
            $dispatcher->dispatch($hook);
        }

        if ($titles) {
            $title = $titles[0]->innertext();
        } else {
            $title = '';
        }
        if ($previews) {
            $preview = trim($previews->innertext());
        } else {
            $preview = '';
        }

        $emailParams = [];
        $linkParams  = $emailLinkParamRepository->findByEmail($email['ema_id']);
        foreach($linkParams as $linkParam) {
            if (isset($templateParams[$linkParam->getTpaId()])) {
                $param               = $templateParams[$linkParam->getTpaId()];
                $emailParams[$param] = $linkParam->getValue();
            }
        }

        return $this->json([
            'title'           => $title,
            'preview'         => $preview,
            'emailLinkParams' => (object)$emailParams
        ]);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/email/{id<\d+>}/upgrade", name="build_email_upgrade")
     * @InjectEmail()
     *
     * @return JsonResponse
     */
    public function upgradeAction(): JsonResponse
    {
        return $this->json('ok');
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/email/{id<\d+>}/history", name="build_email_history", methods={"GET"})
     * @InjectEmail()
     *
     * @param int                          $id
     * @param User|null                    $user
     * @param array                        $email
     * @param Serializer                   $serializer
     * @param EmailRepository              $emailRepository
     * @param TemplatesRepository          $templatesRepository
     * @param EmailHistoryRepository       $emailHistoryRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function historyAction(
        int $id,
        ?User $user,
        array $email,
        Serializer $serializer,
        EmailRepository $emailRepository,
        TemplatesRepository $templatesRepository,
        EmailHistoryRepository $emailHistoryRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): JsonResponse
    {
        $email = $emailRepository->findByID($id);
        if (!$email) {
            $this->throwNotFound();
        }
        $template = $templatesRepository->findByID($email['ema_tmp_id']);
        if (!$template) {
            $this->throwNotFound();
        }
        $isOwner  = $user && $organizationAccessRepository->isOwner($user->getId(), $template['tmp_org_id']);
        $isAdmin  = $user && $organizationAccessRepository->isAdmin($user->getId(), $template['tmp_org_id']);

        $history        = [];
        $emailHistories = $emailHistoryRepository->findByEmail($email['ema_id']);
        foreach ($emailHistories as $item) {
            if ($item->getVersion() !== 0) {
                $item = $serializer->serializeEmailHistory($item);
                $item['user']['isOwner'] = $isOwner;
                $item['user']['isAdmin'] = $isAdmin;
                $history[] = $item;
            }
        }

        $emailHistory = $emailHistoryRepository->findLatest($id);
        $version      = $emailHistory->getVersion();
        $previewUrl   = $this->url('build_email_preview_version', [
            'id'      => $email['ema_id'],
            'tid'     => $email['ema_tmp_id'],
            'token'   => $email['ema_token'],
            'version' => $version
        ]);

        return $this->json([
            'history'    => $history,
            'version'    => $version,
            'previewUrl' => $previewUrl
        ]);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/email/{id<\d+>}/duplicate", name="build_duplicate_email", methods={"PUT"})
     *
     * @param int             $id
     * @param int             $uid
     * @param Request         $request
     * @param Serializer      $serializer
     * @param EmailRepository $emailRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function duplicateAction(
        int $id,
        int $uid,
        Request $request,
        Serializer $serializer,
        EmailRepository $emailRepository
    ): JsonResponse {
        $email = $emailRepository->findByID($id);
        if (!$email) {
            $this->throwNotFound();
        }

        $newID = $emailRepository->duplicate(
            $uid,
            $id,
            $request->json->get('title')
        );
        $email = $emailRepository->findByID($newID);
        $email = $serializer->serializeEmail($email);

        return $this->json($email);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/build/email/{id<\d+>}", name="builder_delete_email", methods={"DELETE"})
     *
     * @param int             $id
     * @param EmailRepository $emailRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function removeAction(
        int $id,
        EmailRepository $emailRepository
    ): JsonResponse
    {
        $result = $emailRepository->deleteByID($id);

        return $this->json($result);
    }

    /**
     * Convert old emails to new system where data-be-img-id attribute is added to
     * uploaded images.
     *
     * @param int             $id
     * @param simple_html_dom $dom
     * @param int             $version
     *
     * @return void
     * @throws Exception
     */
    protected function convertToImageId(int $id, simple_html_dom $dom, int $version)
    {
        $imagesRepository = $this->container->get(ImagesRepository::class);
        foreach($dom->find('img') as $item) {
            $src = $item->getAttribute('src');
            if ($item->getAttribute('data-be-hosted') === '1' || strpos($src, 'http') !== 0) {
                if (!$item->getAttribute('data-be-img-id')) {
                    $filename = pathinfo($src, PATHINFO_BASENAME);
                    $image    = $imagesRepository->findByFilename($id, $version, $filename);
                    if ($image) {
                        $item->setAttribute('data-be-img-id', $image->getId());
                        // dump($image->getId());die();
                    } else {
                        $item->setAttribute('data-be-img-id', '0');
                    }
                }
            }
        }
    }
}
