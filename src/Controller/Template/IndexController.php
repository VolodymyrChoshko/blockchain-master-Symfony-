<?php
namespace Controller\Template;

use BlocksEdit\Cache\CacheInterface;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Controller\Forward;
use BlocksEdit\Http\Annotations\InjectEmail;
use BlocksEdit\Http\Annotations\InjectTemplate;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Integrations\FilesystemIntegrationInterface;
use BlocksEdit\System\Serializer;
use BlocksEdit\View\View;;
use Entity\Email;
use Entity\Organization;
use Entity\OrganizationAccess;
use Entity\Template;
use Entity\User;
use Exception;
use Redis;
use Repository\AccessRepository;
use Repository\EmailRepository;
use Repository\Exception\CreateTemplateException;
use Repository\FoldersRepository;
use Repository\NoticeSeenRepository;
use Repository\OrganizationAccessRepository;
use Repository\OrganizationsRepository;
use Repository\SourcesRepository;
use Repository\TemplatesRepository;
use Service\Export\HtmlToText;
use Service\WebPush;
use Tag\EmailTag;
use Tag\FolderTag;
use Tag\OrganizationTag;
use Tag\TemplateTag;
use Tag\UserTag;

/**
 * @IsGranted({"USER"})
 */
class IndexController extends Controller
{
    /**
     * @Route("/", name="index")
     *
     * @param int               $oid
     * @param User              $user
     * @param Request           $request
     * @param Redis             $redis
     * @param Organization|null $organization
     * @param Template|null     $forwardTemplate
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(
        int $oid,
        User $user,
        Request $request,
        Redis $redis,
        HtmlToText $htmlToText,
        ?Organization $organization,
        ?Template $forwardTemplate = null
    ): Response
    {
        $oid = $this->setupOrganization($oid, $user, $request);
        if ($oid instanceof Response) {
            return $oid;
        }

        $initialState   = $this->getInitialTemplates($user, $organization);
        $initialNotices = $this->getInitialNotices($user, $request);
        $initialState   = array_merge($initialState, $initialNotices);
        if ($forwardTemplate) {
            $initialEmails = $this->getInitialEmails($organization, $forwardTemplate);
            $initialState  = array_merge($initialState, $initialEmails);
        }

        $lastTemplateID = (int)$redis->get(sprintf('templates.last.%d.%d', $user->getId(), $oid));
        $billingPlan    = $this->getFrontendBillingPlan($user, $oid);
        $initialState   = array_merge($initialState, [
            'lastTemplateID' => $lastTemplateID,
            'billingPlan'    => $billingPlan,
            'isLoaded'       => true
        ]);

        return $this->renderFrontend($request, [
            'template' => $initialState
        ]);
    }

    /**
     * @Route("/webpush", name="webpush")
     *
     * @param User    $user
     * @param WebPush $webPush
     *
     * @return void
     */
    public function webpushAction(User $user, WebPush $webPush)
    {
        $webPush->sendOne($user, 'This is a test message!');
        die('sent');
    }

    /**
     * @IsGranted({"template"})
     * @InjectTemplate()
     * @Route("/t/{id}", name="index_template")
     *
     * @param Template $template
     *
     * @return Forward
     */
    public function templateAction(Template $template): Forward
    {
        return $this->forward(__CLASS__, 'indexAction', [
            'forwardTemplate' => $template
        ]);
    }

    /**
     * @Route("/api/v1/templates", name="api_v1_templates", methods={"GET"})
     *
     * @param Organization $organization
     * @param User         $user
     * @param Request      $request
     * @param Redis        $redis
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function templatesAction(
        Organization $organization,
        User $user,
        Request $request,
        Redis $redis
    ): JsonResponse {
        $initialState   = $this->getInitialTemplates($user, $organization);
        $initialNotices = $this->getInitialNotices($user, $request);
        $initialState   = array_merge($initialState, $initialNotices);
        $lastTemplateID = (int)$redis->get(sprintf('templates.last.%d.%d', $user->getId(), $organization->getId()));
        $billingPlan    = $this->getFrontendBillingPlan($user, $organization->getId());
        $initialState   = array_merge($initialState, [
            'lastTemplateID' => $lastTemplateID,
            'billingPlan'    => $billingPlan,
            'isLoaded'       => true
        ]);

        return $this->json($initialState);
    }

    /**
     * @Route("/api/v1/templates/search", name="templates_search", methods={"POST"})
     *
     * @param User                    $user
     * @param Request                 $request
     * @param Serializer              $serializer
     * @param EmailRepository         $emailRepository
     * @param TemplatesRepository     $templatesRepository
     * @param AccessRepository        $accessRepository
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function searchAction(
        User $user,
        Request $request,
        Serializer $serializer,
        EmailRepository $emailRepository,
        TemplatesRepository $templatesRepository,
        AccessRepository $accessRepository,
        OrganizationsRepository $organizationsRepository
    ): JsonResponse
    {
        $term = trim($request->json->get('term'));
        if (!$term) {
            return $this->json([]);
        }

        $results = $emailRepository->findByTerm($term, $user->getId());
        foreach($results as &$email) {
            $template          = $templatesRepository->findByID($email['ema_tmp_id']);
            $people            = $accessRepository->findUsersByTemplate($email['ema_tmp_id']);
            $org               = $organizationsRepository->findByID($template['tmp_org_id']);
            $email             = $serializer->serializeEmail($email);
            $email['template'] = $serializer->serializeTemplate($template);
            $email['org']      = $serializer->serializeOrganization($org);
            foreach($people as &$person) {
                $person = $serializer->serializeUser($person);
            }
            $email['people']   = $people;
        }

        return $this->json($results);
    }

    /**
     * @IsGranted("template")
     * @Route("/api/v1/templates/{id}", name="templates_update", methods={"POST"})
     * @InjectTemplate()
     *
     * @param Template            $template
     * @param Request             $request
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function updateAction(
        Template $template,
        Request $request,
        TemplatesRepository $templatesRepository
    ): JsonResponse {
        $title = trim($request->json->get('title'));
        if (!$title) {
            return $this->json([
                'error' => 'Title cannot be empty.'
            ]);
        }

        $template->setTitle($title);
        $templatesRepository->update($template);

        return $this->json(true);
    }

    /**
     * @IsGranted("template")
     * @InjectTemplate()
     * @Route("/api/v1/templates/{id}", name="templates_delete", methods={"DELETE"})
     *
     * @param Template            $template
     * @param TemplatesRepository $templatesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteAction(
        Template $template,
        TemplatesRepository $templatesRepository
    ): JsonResponse
    {
        $templatesRepository->delete($template);

        return $this->json('ok');
    }

    /**
     * @IsGranted("template")
     * @Route("/api/v1/templates/{id}/emails", name="templates_emails", methods={"GET"})
     * @InjectTemplate()
     *
     * @param Template     $template
     * @param Organization $organization
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function emailsAction(
        Template $template,
        Organization $organization
    ): JsonResponse {
        $id = $template->getId();
        $initialEmails = $this->getInitialEmails($organization, $template);

        return $this->json([
            'emails'  => $initialEmails['emails'][$id],
            'folders' => $initialEmails['folders'][$id]
        ]);
    }

    /**
     * @IsGranted("template")
     * @Route("/api/v1/templates/{id}/emails", name="templates_emails_create", methods={"PUT"})
     * @InjectTemplate()
     *
     * @param int             $uid
     * @param int             $oid
     * @param int             $id
     * @param Request         $request
     * @param Serializer      $serializer
     * @param EmailRepository $emailRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function createEmailAction(
        int $uid,
        int $oid,
        int $id,
        Request $request,
        Serializer $serializer,
        EmailRepository $emailRepository
    ): JsonResponse
    {
        try {
            $title = $request->json->get('title');
            if (empty($title)) {
                return $this->json([
                    'error'   => true,
                    'message' => 'An email name is required.'
                ]);
            }

            $eid = $emailRepository->create(
                $uid,
                $id,
                $oid,
                $title
            );
            $email = $emailRepository->findByID($eid);
            $email = $serializer->serializeEmail($email);

            return $this->json($email);
        } catch (CreateTemplateException $e) {
            return $this->json([
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @IsGranted("template")
     * @InjectEmail("eid")
     * @Route("/api/v1/templates/{id}/emails/{eid}", name="templates_emails_update", methods={"POST"})
     *
     * @param Email           $email
     * @param Request         $request
     * @param EmailRepository $emailRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function updateEmailAction(
        Email $email,
        Request $request,
        EmailRepository $emailRepository
    ): JsonResponse
    {
        $title = trim($request->json->get('title'));
        if (!$title) {
            return $this->json('ok');
        }

        $email->setTitle($title);
        $emailRepository->update($email);

        return $this->json(true);
    }

    /**
     * @Route("/api/v1/templates/notices/firstUse", name="templates_fu_notice", methods={"POST"})
     *
     * @param int                  $uid
     * @param Request              $request
     * @param Redis                $redis
     * @param NoticeSeenRepository $noticeSeenRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function closeFUNoticeAction(
        int $uid,
        Request $request,
        Redis $redis,
        NoticeSeenRepository $noticeSeenRepository
    ): JsonResponse
    {
        $id = $request->json->get('id');
        if ($id) {
            if ($id != -1) {
                $seen = $noticeSeenRepository->findByNoticeAndUser($id, $uid);
                if ($seen) {
                    $seen->setIsClosed(true);
                    $noticeSeenRepository->update($seen);
                }
            }
        } else {
            $redis->set('db.firstUseNotice.' . $uid, 1);
        }

        return $this->json('ok');
    }

    /**
     * @Route("/api/v1/templates/settings/last", name="templates_last", methods={"POST"})
     *
     * @param int     $uid
     * @param int     $oid
     * @param Request $request
     * @param Redis   $redis
     *
     * @return JsonResponse
     */
    public function lastTemplateAction(int $uid, int $oid, Request $request, Redis $redis): JsonResponse
    {
        $redis->set(sprintf('templates.last.%d.%d', $uid, $oid), $request->json->get('id'));

        return $this->json('ok');
    }

    /**
     * @param int     $oid
     * @param User    $user
     * @param Request $request
     *
     * @return Response|int
     * @throws Exception
     */
    protected function setupOrganization(int $oid, User $user, Request $request)
    {
        $orgAccessRepository = $this->container->get(OrganizationAccessRepository::class);
        $redis               = $this->container->get(Redis::class);

        $uid      = $user->getId();
        $cacheKey = sprintf('last.organization.%d', $uid);
        if (empty($oid)) {
            $cacheOid = (int)$redis->get($cacheKey);
            if ($cacheOid) {
                return $this->redirect(
                    $request->getDomainForOrg($cacheOid)
                );
            }

            $oidRow = $orgAccessRepository->findFirstByUserAndAccess($uid, OrganizationAccess::OWNER);
            if (empty($oidRow)) {
                $oidRow = $orgAccessRepository->findFirstByUser($uid);
                if (empty($oidRow)) {
                    $this->throwBadRequest();
                }
            }
            $oid = $oidRow['rba_org_id'];
            $redis->set($cacheKey, $oid);

            return $this->redirect(
                $request->getDomainForOrg($oid)
            );
        }

        /*$isOwner  = $orgAccessRepository->isOrganizationOwner($uid, $oid);
        $isAdmin  = $orgAccessRepository->isOrganizationAdmin($uid, $oid);
        $isEditor = $orgAccessRepository->isOrganizationEditor($uid, $oid);
        if (!$isOwner && !$isAdmin && !$isEditor) {
            $oidRow = $orgAccessRepository->findFirstByUserAndAccess($uid, OrganizationAccess::OWNER);
            if (!empty($oidRow)) {
                return $this->redirect(
                    $request->getDomainForOrg($oidRow['rba_org_id'])
                );
            }

            $this->throwNotFound();
        }*/

        $redis->set($cacheKey, $oid);

        return $oid;
    }

    /**
     * @param User    $user
     * @param Request $request
     *
     * @return array
     * @throws Exception
     */
    protected function getInitialNotices(User $user, Request $request): array
    {
        $redis      = $this->container->get(Redis::class);
        $serializer = $this->container->get(Serializer::class);

        $firstUseNotice = (bool)$redis->get('db.firstUseNotice.' . $user->getId());
        if ($user->getCreatedAt() <= 1597352770) {
            $firstUseNotice = true;
        }

        $notices = [];
        if ($request->query->get('preview_notice')) {
            $previewHash   = $request->query->get('preview_notice');
            $previewNotice = $redis->get($previewHash);
            if ($previewNotice) {
                $notices[] = [
                    'id'      => -1,
                    'content' => $previewNotice
                ];
            }
        } else {
            foreach (View::getGlobals('notices') as $notice) {
                $notices[] = $serializer->serializeNotice($notice);
            }
        }

        return [
            'firstUseNotice' => $firstUseNotice,
            'notices'        => $notices
        ];
    }

    /**
     * @param User         $user
     * @param Organization $organization
     *
     * @return array
     * @throws Exception
     */
    protected function getInitialTemplates(User $user, Organization $organization): array
    {
        $orgAccessRepository = $this->container->get(OrganizationAccessRepository::class);
        $templatesRepository = $this->container->get(TemplatesRepository::class);
        $accessRepository    = $this->container->get(AccessRepository::class);
        $serializer          = $this->container->get(Serializer::class);

        $uid      = $user->getId();
        $oid      = $organization->getId();
        $response = $this->cache->get("dashboard:$oid:$uid", []);
        $response = false;
        if (!$response) {
            try {
                $this->cache->batchStart();

                $people    = [];
                $layouts   = [];
                $templates = [];
                $cacheTags = [
                    new OrganizationTag($oid)
                ];

                $isOwner = $orgAccessRepository->isOwner($uid, $oid);
                foreach ($templatesRepository->findForDashboard($isOwner, $user, $organization) as $template) {
                    $tid             = $template->getId();
                    $templates[$tid] = $serializer->serializeTemplate($template);
                    $cacheTags[]     = new TemplateTag($tid);

                    $layouts[$tid] = [];
                    foreach ($templatesRepository->findLayoutsByTemplate($tid) as $layout) {
                        $layouts[$tid] = $serializer->serializeTemplate($layout);
                        $cacheTags[]   = new TemplateTag($layout['tmp_id']);
                    }

                    $people[$tid] = [];
                    foreach ($orgAccessRepository->findOwners($template->getOrganization()) as $access) {
                        $found = false;
                        foreach ($people[$tid] as $p) {
                            if ($access->getUser()) {
                                if ((int)$p['usr_id'] === $access->getUser()->getId()) {
                                    $found = true;
                                }
                            }
                        }
                        if (!$found) {
                            $access->getUser()->setIsOwner(true);
                            $people[$tid][] = $access->getUser();
                        }
                    }
                    foreach ($orgAccessRepository->findAdmins($template->getOrganization()) as $access) {
                        $found = false;
                        foreach ($people[$tid] as $p) {
                            if ($access->getUser()) {
                                if ((int)$p['usr_id'] === $access->getUser()->getId()) {
                                    $found = true;
                                }
                            }
                        }
                        if (!$found) {
                            $access->getUser()->setIsAdmin(true);
                            $people[$tid][] = $access->getUser();
                        }
                    }
                    foreach($accessRepository->findUsersByTemplate($tid) as $u) {
                        $found = false;
                        foreach($people[$tid] as $p) {
                            if ((int)$p['usr_id'] === (int)$u['usr_id']) {
                                $found = true;
                            }
                        }
                        if (!$found) {
                            $people[$tid][] = $u;
                        }
                    }
                    foreach ($people[$tid] as &$person) {
                        $person      = $serializer->serializeUser($person);
                        $cacheTags[] = new UserTag($person['id']);
                    }
                }

                $response = [
                    'templates'  => $templates,
                    'layouts'    => $layouts,
                    'people'     => $people,
                    'hasSources' => $this->hasSources($oid),
                ];

                $this->cache->set("dashboard:$oid:$uid", $response, CacheInterface::ONE_WEEK, $cacheTags);
                $this->cache->batchCommit();
            } finally {
                $this->cache->batchRollback();
            }
        }

        return $response;
    }

    /**
     * @param Organization $organization
     * @param Template     $template
     *
     * @return array[]
     * @throws Exception
     */
    protected function getInitialEmails(Organization $organization, Template $template): array
    {
        $emailRepository   = $this->container->get(EmailRepository::class);
        $foldersRepository = $this->container->get(FoldersRepository::class);
        $serializer        = $this->container->get(Serializer::class);

        $tid = $template->getId();
        $initialEmails = $this->cache->get("dashboard:emails:$tid", []);
        $initialEmails = false;
        if (!$initialEmails || !$tid || !isset($initialEmails['emails'][$tid]) || !isset($initialEmails['folders'][$tid])) {
            try {
                $this->cache->batchStart();

                $cacheTags = [
                    new OrganizationTag($organization->getId()),
                    new TemplateTag($tid)
                ];

                $emails = $emailRepository->findByTemplate($tid);
                foreach ($emails as &$email) {
                    $email       = $serializer->serializeEmail($email);
                    $cacheTags[] = new EmailTag($email['id']);
                }
                $folders = $foldersRepository->fetchByTemplateId($tid);
                foreach ($folders as &$folder) {
                    $folder      = $serializer->serializeFolder($folder);
                    $cacheTags[] = new FolderTag($folder['id']);
                }

                $initialEmails = [
                    'emails'  => [
                        $tid => $emails
                    ],
                    'folders' => [
                        $tid => $folders
                    ]
                ];

                $this->cache->set("dashboard:emails:$tid", $initialEmails, CacheInterface::ONE_WEEK, $cacheTags);
                $this->cache->batchCommit();
            } finally {
                $this->cache->batchRollback();
            }
        }

        return $initialEmails;
    }

    /**
     * @param int $oid
     *
     * @return bool
     * @throws Exception
     */
    protected function hasSources(int $oid): bool
    {
        $billingPlan = $this->getBillingPlan();
        $isIntegrationsDisabled = (
            $billingPlan->isPaused() || ($billingPlan->isTrialComplete() && !$this->getBillingCreditCard())
        );
        if ($isIntegrationsDisabled) {
            return false;
        }

        $hasSources  = false;
        $sourcesRepo = $this->container->get(SourcesRepository::class);
        $sources     = $sourcesRepo->findByOrg($oid);
        foreach($sources as $source) {
            if (!($source->getIntegration() instanceof FilesystemIntegrationInterface)) {
                continue;
            }

            /** @phpstan-ignore-next-line */
            $frontendSettings = $source->getIntegration()->getFrontendSettings();
            if ($frontendSettings['rules']['can_list_files']) {
                $hasSources = true;
                break;
            }
        }

        return $hasSources;
    }
}
