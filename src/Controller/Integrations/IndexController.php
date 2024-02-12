<?php
namespace Controller\Integrations;

use BlocksEdit\Http\Annotations\InjectEmail;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Integrations\FilesystemIntegrationInterface;
use BlocksEdit\Integrations\Hook;
use BlocksEdit\Integrations\HookDispatcher;
use BlocksEdit\Integrations\IntegrationInterface;
use BlocksEdit\System\Serializer;
use BlocksEdit\View\View;
use Entity\Source;
use Entity\OrganizationAccess;
use Exception;
use Repository\BillingPlanRepository;
use Repository\BillingPriceRepository;
use Repository\OrganizationAccessRepository;
use Repository\SourcesRepository;
use Repository\TemplatesRepository;
use Repository\OrganizationsRepository;

/**
 * @IsGranted({"USER"})
 */
class IndexController extends Controller
{
    /**
     * @Route("/integrations", name="integrations_index")
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(Request $request): Response
    {
        return $this->renderFrontend($request);
    }

    /**
     * @Route("/api/v1/integrations", name="api_v1_integrations")
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param Serializer                   $serializer
     * @param SourcesRepository            $sourcesRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     * @param BillingPriceRepository       $billingPriceRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function loadAction(
        int $uid,
        int $oid,
        Serializer $serializer,
        SourcesRepository $sourcesRepository,
        OrganizationAccessRepository $organizationAccessRepository,
        BillingPriceRepository $billingPriceRepository
    ): JsonResponse
    {
        if (!$organizationAccessRepository->hasRole($uid, $oid, [OrganizationAccess::OWNER, OrganizationAccess::ADMIN])) {
            return $this->json([]);
        }

        $sources = $sourcesRepository->findByOrg($oid);
        foreach($sources as $source) {
            $integration        = $source->getIntegration();
            $formValues         = $integration->getDefaultSettings();
            $formValues['name'] = $source->getName();
            if ($integration instanceof FilesystemIntegrationInterface) {
                $formValues['home_dir'] = !empty($source->getHomeDir())
                    ? $source->getHomeDir()
                    : $integration->getDefaultHomeDirectory();
            }
            if ($settings = $sourcesRepository->findSettings($source)) {
                $formValues = array_merge($formValues, $settings);
            }

            $integration->setUser($uid, $oid);
            $integration->setSource($source);
            $integration->setSettings($formValues);
            $source->setSettings($formValues);
        }

        $billingPlan = $this->getBillingPlan();
        $isIntegrationsDisabled = (
            $billingPlan->isPaused() || ($billingPlan->isTrialComplete() && !$this->getBillingCreditCard())
        );
        if ($billingPlan->isCustom() && !$billingPlan->isPaused()) {
            $isIntegrationsDisabled = false;
        }

        $integrationPrices = [];
        $integrations      = $sourcesRepository->getAvailableIntegrations();
        foreach($sourcesRepository->getAvailableIntegrations() as $int) {
            $slug        = $int->getSlug();
            $amountCents = $billingPriceRepository->getAmountCents("integration:$slug");
            if ($amountCents === -1) {
                $amountCents = $int->getPrice();
            }
            $integrationPrices[$slug] = $amountCents;
        }

        $sSources = [];
        foreach($sources as $source) {
            $sSources[] = $serializer->serializeSource($source);
        }

        $sIntegrations = [];
        foreach($integrations as $integration) {
            $serialized = $serializer->serializeIntegration($integration);
            $serialized['canEnable'] = $this->canEnableIntegration($integration, $sources);
            $sIntegrations[] = $serialized;

        }

        return $this->json([
            'integrations'           => $sIntegrations,
            'integrationPrices'      => $integrationPrices,
            'sources'                => $sSources,
            'billingPlan'            => $this->getBillingPlan(),
            'isIntegrationsDisabled' => $isIntegrationsDisabled,
            'nonce'                  => $this->nonce->generate('add_integration')
        ]);
    }

    /**
     * @Route("/integrations/{slug}/add", name="integrations_add")
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param string                       $slug
     * @param Request                      $request
     * @param SourcesRepository            $sourcesRepository
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     * @param BillingPlanRepository        $billingPlanRepository
     * @param Serializer                   $serializer
     *
     * @return Response
     * @throws Exception
     */
    public function addAction(
        int $uid,
        int $oid,
        string $slug,
        Request $request,
        SourcesRepository $sourcesRepository,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository,
        BillingPlanRepository $billingPlanRepository,
        Serializer $serializer
    ): Response {
        // $this->nonce->verifyRequest('add_integration', $request);
        $integration = $sourcesRepository->getIntegrationBySlug($slug);
        if (!$integration) {
            $this->throwNotFound();
        }

        if ($integration->hasRestriction(IntegrationInterface::ONE_PER_ORG)) {
            $found = $sourcesRepository->findByIntegrationAndOrg($integration, $oid);
            if ($found) {
                $this->flash->error(
                    sprintf('Only one instance of the %s integration can be enabled per organization.', $integration->getDisplayName())
                );

                return $this->redirectToRoute('index');
            }
        }

        $organization = $organizationsRepository->findByID($oid);
        if (!$organization) {
            $this->throwNotFound();
        }
        if (!$organizationAccessRepository->hasRole($uid, $oid, [OrganizationAccess::OWNER, OrganizationAccess::ADMIN])) {
            $this->throwUnauthorized();
        }

        $billingPlan = $this->getBillingPlan();
        if ($billingPlan->isSolo() && !$billingPlan->isTrialComplete()) {
            $billingPlanRepository->upgradeToTrialIntegration($billingPlan);
        }

        $source = (new Source())
            ->setName($integration->getDisplayName())
            ->setHomeDir('/')
            ->setOrgId($organization['org_id'])
            ->setClass(get_class($integration));
        if ($integration instanceof FilesystemIntegrationInterface) {
            $source->setHomeDir($integration->getDefaultHomeDirectory());
        }
        $sourcesRepository->insert($source);
        $source->setIntegration($integration);

        if ($request->isAjax()) {
            return $this->json($serializer->serializeSource($source));
        }

        return $this->redirectToRoute('integrations_settings', [
            'oid' => $oid,
            'sid' => $source->getId()
        ]);
    }

    /**
     * @Route("/integrations/{sid}/settings", name="integrations_settings")
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param int                          $sid
     * @param Request                      $request
     * @param SourcesRepository            $sourcesRepository
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function settingsAction(
        int $uid,
        int $oid,
        int $sid,
        Request $request,
        SourcesRepository $sourcesRepository,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    )
    {
        $source = $sourcesRepository->findByID($sid);
        if (!$source) {
            $this->throwNotFound();
        }

        $organization = $organizationsRepository->findByID($oid);
        if (!$organization) {
            $this->throwNotFound();
        }
        if (!$organizationAccessRepository->hasRole($uid, $oid, [OrganizationAccess::OWNER, OrganizationAccess::ADMIN])) {
            $this->throwUnauthorized();
        }

        $integration        = $source->getIntegration();
        $formValues         = $integration->getDefaultSettings();
        $formValues['name'] = $source->getName();
        if ($integration instanceof FilesystemIntegrationInterface) {
            $formValues['home_dir'] = !empty($source->getHomeDir())
                ? $source->getHomeDir()
                : $integration->getDefaultHomeDirectory();
        }
        if ($settings = $sourcesRepository->findSettings($source)) {
            $formValues = array_merge($formValues, $settings);
        }

        $integration->setUser($uid, $oid);
        $integration->setSource($source);
        $integration->setSettings($formValues);
        $source->setSettings($formValues);

        $errors = null;
        if ($request->isPost()) {
            try {
                $errors = $this->handleSettingsForm(
                    $source,
                    $integration,
                    $request,
                    $request->post->all()
                );
                if (!($errors instanceof FormErrors)) {
                    // $this->flash->success('Integration updated.');
                    // return $this->redirectToRoute('integrations_index');
                    return $this->json('ok');
                }

                return $this->json(['error' => 'Please fix the values below.']);
            } catch (Exception $e) {
                return $this->json(['error' => 'System error.']);
                // $this->flash->error($e->getMessage());
            }
        }

        $form = $integration->getSettingsForm($request, $formValues, $errors);
        if (is_string($form) || $form instanceof View) {
            if ($request->isAjax()) {
                return $this->json(['html' => (string)$form]);
            }
            return $this->render('integrations/form.html.twig', [
                'form'    => (string)$form,
                'scripts' => $integration->getSettingsScript()
            ]);
        }

        if ($request->isAjax()) {
            $resp = $this->render('integrations/form-body.html.twig', [
                'oid'          => $oid,
                'sid'          => $sid,
                'errors'       => $errors,
                'formValues'   => $formValues,
                'integration'  => $integration,
                'organization' => $organization,
                'form'         => $form
            ]);

            return $this->json([
                'html'    => $resp->getContent(),
                'scripts' => $integration->getSettingsScript()
            ]);
        }

        return $this->render('integrations/settings.html.twig', [
            'oid'          => $oid,
            'sid'          => $sid,
            'errors'       => $errors,
            'formValues'   => $formValues,
            'integration'  => $integration,
            'organization' => $organization,
            'form'         => $form
        ]);
    }

    /**
     * @Route("/integrations/{slug}/oauth_redirect", name="integrations_oauth_redirect")
     *
     * OAuth callback for integrations which required oauth
     *
     * User arrives here either while adding a new integration or while
     * using the integration.
     *
     * @param int                     $uid
     * @param string                  $slug
     * @param Request                 $request
     * @param SourcesRepository       $sourcesRepository
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function oauthAction(
        int $uid,
        string $slug,
        Request $request,
        SourcesRepository $sourcesRepository,
        OrganizationsRepository $organizationsRepository
    ): RedirectResponse {
        $code     = $request->query->get('code');
        $state    = $request->query->get('state');
        $error    = $request->query->get('error');
        $scope    = $request->query->get('scope');
        $oid      = $request->session->get('oauth_oid');
        $settings = $request->session->get('oauth_settings');
        $sid      = $request->session->get('oauth_sid');

        // @see Controller\Integrations\SourcesAction::index
        // @see Controller\Integrations\IndexAction::test
        $request->session->remove('oauth_oid');
        $request->session->remove('oauth_sid');
        $request->session->remove('oauth_settings');

        if ($error) {
            $this->throwBadRequest(urldecode($error));
        }

        $integration = $sourcesRepository->getIntegrationBySlug($slug);
        if (!$integration) {
            $this->throwNotFound();
        }
        $organization = $organizationsRepository->findByID($oid);
        if (!$organization) {
            $this->throwNotFound();
        }

        // We're re-authenticating an already installed integration.
        if ($sid) {
            $source = $sourcesRepository->findByID($sid);
            if (!$source) {
                $this->throwNotFound();
            }

            $integration->setUser($uid, $oid);
            $integration->setSettings($sourcesRepository->findSettings($source));
            $integration->setOauthResponse([
                'code'  => $code,
                'state' => $state,
                'scope' => $scope
            ]);
            if ($settings) {
                $this->handleSettingsForm($source, $integration, $request, $settings);
            }
            $this->flash->success('Integration updated.');

            return $this->redirectToRoute('account');
        }

        // We're adding a new integration.
        $integration->setSettings($settings);
        $integration->setUser($uid, $oid);
        $integration->setOauthResponse([
            'code'  => $code,
            'state' => $state,
            'scope' => $scope
        ]);

        $source = (new Source())
            ->setName($settings['name'])
            ->setHomeDir($settings['home_dir'] ?? '')
            ->setOrgId($organization['org_id'])
            ->setClass(get_class($integration));
        $this->container->get(SourcesRepository::class)->insert($source);
        if ($settings) {
            $this->handleSettingsForm($source, $integration, $request, $settings);
            $this->flash->success('Integration added.');
        } else {
            $this->flash->success('Integration updated.');
        }

        return $this->redirectToRoute('account');
    }

    /**
     * @IsGranted({"ORG_ADMIN"})
     * @Route("/integrations/{iid}/remove", name="integrations_remove", methods={"POST"})
     *
     * @param int               $uid
     * @param int               $oid
     * @param int               $iid
     * @param SourcesRepository $sourcesRepository
     *
     * @return Response
     * @throws Exception
     */
    public function removeAction(
        int $uid,
        int $oid,
        int $iid,
        SourcesRepository $sourcesRepository
    ): Response {
        $source      = $this->getSourceOrThrow($uid, $oid, $iid);
        $integration = $source->getIntegration();
        $integration->setUser($uid, $oid);
        $integration->postRemoveIntegration();

        return $this->json(
            $sourcesRepository->delete($source)
        );
    }

    /**
     * @Route("/integrations/test", name="integrations_test", methods={"POST"})
     *
     * @param int               $uid
     * @param Request           $request
     * @param SourcesRepository $sourcesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function testAction(int $uid, Request $request, SourcesRepository $sourcesRepository): JsonResponse
    {
        $slug        = $request->post->getOrBadRequest('slug');
        $oid         = $request->post->getOrBadRequest('oid');
        $sid         = $request->post->getInt('sid');
        $integration = $sourcesRepository->getIntegrationBySlug($slug);

        $settings = $request->post->all();
        foreach($request->files->all() as $name => $file) {
            if (!$file['error']) {
                $settings[$name] = file_get_contents($file['tmp_name']);
            }
        }

        $prevSettings = [];
        if ($sid) {
            $source = $sourcesRepository->findByID($sid);
            if ($source) {
                $prevSettings = $sourcesRepository->findSettings($source);
                $settings = array_merge($prevSettings, $settings);
            }
        }
        $integration->setSettings($settings, $prevSettings);
        $integration->setUser($uid, $oid);

        // Some integrations require oauth login before continuing. The redirect
        // response tells the frontend to redirect to the oauth login.
        if ($integration->requiresOauthRedirect() && !$integration->isOauthAuthenticated()) {
            $request->session->set('oauth_settings', $settings);
            $request->session->set('oauth_oid', $oid);
            $request->session->set('oauth_sid', $sid);

            return $this->json([
                'redirect' => $integration->getOauthURL()
            ]);
        }

        // Tell the frontend whether the settings worked to connect or not. The frontend
        // won't let the user continue if the settings are incorrect.
        try {
            if ($integration instanceof FilesystemIntegrationInterface) {
                $integration->connect();
            }
        } catch (Exception $e) {
            return $this->json(['connected' => false]);
        }

        return $this->json(['connected' => true]);
    }

    /**
     * @Route("/integrations/authenticate", name="integrations_authenticate", methods={"POST"})
     *
     * @param int               $uid
     * @param int               $oid
     * @param Request           $request
     * @param SourcesRepository $sourcesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function authenticateAction(
        int $uid,
        int $oid,
        Request $request,
        SourcesRepository $sourcesRepository
    ): JsonResponse {
        $slug        = $request->post->getOrBadRequest('slug');
        $sid         = $request->post->getOrBadRequest('sid');
        $integration = $sourcesRepository->getIntegrationBySlug($slug);
        if (!$integration) {
            $this->throwBadRequest();
        }

        $source   = $sourcesRepository->findByID($sid);
        $settings = $sourcesRepository->findSettings($source);
        $integration->setSettings($settings);
        $integration->setUser($uid, $oid);

        $request->session->set('oauth_oid', $oid);
        $request->session->set('oauth_sid', $sid);

        return $this->json([
            'redirect' => $integration->getOauthURL()
        ]);
    }

    /**
     * @Route("/integrations/{oid}/{sid}/settings", name="integrations_settings_get", methods={"GET"})
     *
     * @param int               $uid
     * @param Request           $request
     * @param SourcesRepository $sourcesRepository
     *
     * @return Response
     * @throws Exception
     */
    public function getSettingsAction(
        int $uid,
        Request $request,
        SourcesRepository $sourcesRepository
    ): Response {
        $oid      = $request->route->params->getInt('oid');
        $sid      = $request->route->params->getInt('sid');
        $source   = $this->getSourceOrThrow($uid, $oid, $sid);
        $settings = $sourcesRepository->findSettings($source);

        return $this->json($settings);
    }

    /**
     * @Route("/integrations/{sid}/settings", name="integrations_settings_update", methods={"POST"})
     *
     * @param int               $uid
     * @param int               $oid
     * @param int               $sid
     * @param Request           $request
     * @param SourcesRepository $sourcesRepository
     *
     * @return Response
     * @throws Exception
     */
    public function updateSettingsAction(
        int $uid,
        int $oid,
        int $sid,
        Request $request,
        SourcesRepository $sourcesRepository
    ): Response {
        $source   = $this->getSourceOrThrow($uid, $oid, $sid);
        $settings = $sourcesRepository->findSettings($source);
        $update   = $request->json->all();
        $settings = array_merge($settings, $update);

        $sourcesRepository->updateSettings($source, $settings);
        $sourcesRepository->update($source);

        return new JsonResponse('ok');
    }

    /**
     * @Route("/integrations/enabled", name="integrations_enabled")
     *
     * @param int               $oid
     * @param SourcesRepository $sourcesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function enabledAction(int $oid, SourcesRepository $sourcesRepository): JsonResponse
    {
        $sources = [];
        foreach($sourcesRepository->findByOrg($oid) as $source) {
            $integration = $source->getIntegration();
            if ($integration instanceof FilesystemIntegrationInterface) {
                $sources[] = [
                    'id'       => $source->getId(),
                    'name'     => $source->getName(),
                    'thumb'    => $integration->getIconURL(),
                    'settings' => $integration->getFrontendSettings()
                ];
            }
        }

        return $this->json($sources);
    }

    /**
     * @IsGranted({"email"})
     * @Route("/integrations/hook/email/{id}/{name}", name="integrations_email_hook", methods={"POST"})
     * @InjectEmail()
     *
     * @param array               $email
     * @param string              $name
     * @param Request             $request
     * @param HookDispatcher      $dispatcher
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function hookAction(
        array $email,
        string $name,
        Request $request,
        HookDispatcher $dispatcher,
        TemplatesRepository $templatesRepository
    ): JsonResponse {
        $template = $templatesRepository->findByID($email['ema_tmp_id']);
        $sid      = $request->json->getInt('sid');
        $hook     = new Hook($name, $request, $email, $template);
        $dispatcher->dispatch($hook, $sid);

        return $this->json($hook->getResponses());
    }

    /**
     * @param int $uid
     * @param int $oid
     * @param int $iid
     *
     * @return Source
     * @throws Exception
     */
    private function getSourceOrThrow(int $uid, int $oid, int $iid): Source
    {
        $orgAccessRepo     = $this->container->get(OrganizationAccessRepository::class);
        $organizationsRepo = $this->container->get(OrganizationsRepository::class);
        $organization      = $organizationsRepo->findByID($oid);
        if (!$organization) {
            $this->throwNotFound();
        }
        if (!$orgAccessRepo->hasRole($uid, $oid, [OrganizationAccess::OWNER, OrganizationAccess::ADMIN])) {
            $this->throwUnauthorized();
        }

        $sourcesRepo = $this->container->get(SourcesRepository::class);
        $source      = $sourcesRepo->findByID($iid);
        if (!$source || $source->getOrgId() != $organization['org_id']) {
            $this->throwNotFound();
        }

        return $source;
    }

    /**
     * @param Source               $source
     * @param IntegrationInterface $integration
     * @param Request              $request
     * @param array                $values
     *
     * @return array|FormErrors
     * @throws Exception
     */
    private function handleSettingsForm(
        Source $source,
        IntegrationInterface $integration,
        Request $request,
        array $values
    )
    {
        foreach($request->files->all() as $name => $file) {
            if ($file['error'] === 0) {
                $values[$name] = file_get_contents($file['tmp_name']);
            }
        }

        $source->setName($values['name']);
        if ($integration instanceof FilesystemIntegrationInterface) {
            $source->setHomeDir($values['home_dir'] ?? '');
        }

        $sourcesRepo  = $this->container->get(SourcesRepository::class);
        $origSettings = $sourcesRepo->findSettings($source);
        if ($origSettings) {
            $values = array_merge($origSettings, $values);
        }

        $values = $integration->preSaveSettings($values);
        if ($values instanceof FormErrors) {
            return $values;
        }
        $sourcesRepo->updateSettings($source, $values);
        $sourcesRepo->update($source);

        $data         = $sourcesRepo->findSettings($source);
        $data['name'] = $source->getName();
        if ($integration instanceof FilesystemIntegrationInterface) {
            $data['home_dir'] = $source->getHomeDir() ?? '';
        }

        return $data;
    }

    /**
     * @param IntegrationInterface $integration
     * @param array                $sources
     *
     * @return bool
     */
    private function canEnableIntegration(IntegrationInterface $integration, array $sources): bool
    {
        if (!$integration->hasRestriction(IntegrationInterface::ONE_PER_ORG)) {
            return true;
        }
        foreach($sources as $source) {
            if ($source->getClass() === get_class($integration)) {
                return false;
            }
        }

        return true;
    }
}
