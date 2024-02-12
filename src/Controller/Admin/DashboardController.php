<?php
namespace Controller\Admin;

use BlocksEdit\Analytics\PageViews;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\Request;
use BlocksEdit\IO\Paths;
use BlocksEdit\Util\Strings;
use DateTime;
use Entity\BillingPlan;
use Entity\Notice;
use Exception;
use Monolog\Logger;
use Redis;
use Repository\UserRepository;
use Repository\BillingLogRepository;
use Repository\BillingPlanRepository;
use Repository\EmailRepository;
use Repository\LogRecordRepository;
use Repository\NoticeRepository;
use Repository\OrganizationsRepository;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"SITE_ADMIN_2FA"})
 * @Route("/admin", name="admin_dashboard_")
 */
class DashboardController extends Controller
{
    /**
     * @Route(name="index")
     *
     * @param Paths                   $paths
     * @param PageViews               $pageViews
     * @param TemplatesRepository     $templatesRepository
     * @param UserRepository          $userRepository
     * @param EmailRepository         $emailRepository
     * @param BillingPlanRepository   $billingPlanRepository
     * @param BillingLogRepository    $billingLogRepository
     * @param OrganizationsRepository $organizationsRepository
     * @param LogRecordRepository     $logRecordRepository
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(
        Paths $paths,
        PageViews $pageViews,
        TemplatesRepository $templatesRepository,
        UserRepository $userRepository,
        EmailRepository $emailRepository,
        BillingPlanRepository $billingPlanRepository,
        BillingLogRepository $billingLogRepository,
        OrganizationsRepository $organizationsRepository,
        LogRecordRepository $logRecordRepository
    ): Response
    {
        $countPageViews    = $pageViews->getPageViewsTotal();
        $countBuilderViews = $pageViews->getPageViewsTotal(PageViews::PAGE_BUILDER);
        $countUsers        = $userRepository->countAll();
        $countTemplates    = $templatesRepository->countAll();
        $countEmails       = $emailRepository->countAll();
        $countTeamPlans    = $billingPlanRepository->countByType(BillingPlan::TYPE_TEAM);
        $billingLogs       = $billingLogRepository->findAll(10);
        $recentEmails      = $emailRepository->findAll(10);
        $recentErrorLogs   = $logRecordRepository->findByLevelOrGreater(Logger::WARNING, 10);

        $sevenDaysAgo            = new DateTime('7 days ago');
        $countRecentPageViews    = $pageViews->getPageViewsSince($sevenDaysAgo);
        $countRecentBuilderViews = $pageViews->getPageViewsSince($sevenDaysAgo, PageViews::PAGE_BUILDER);
        $countRecentUsers        = count($userRepository->findSince($sevenDaysAgo));
        $countRecentTemplates    = count($templatesRepository->findSince($sevenDaysAgo));
        $countRecentEmails       = count($emailRepository->findSince($sevenDaysAgo));
        $countRecentTeamPlans    = count($billingPlanRepository->findSince($sevenDaysAgo, BillingPlan::TYPE_TEAM));

        $users = [];
        $orgs  = [];

        foreach($recentEmails as $email) {
            $uid = $email['ema_updated_usr_id'];
            if ($uid && !isset($users[$uid])) {
                $user = $userRepository->findByID($uid);
                if ($user) {
                    $users[$uid] = $user;
                }
            }
        }

        foreach($billingLogs as $billingLog) {
            $oid = $billingLog->getOrgId();
            if ($oid && !isset($orgs[$oid])) {
                $org = $organizationsRepository->findByID($oid);
                if ($org) {
                    $orgs[$oid]= $org;
                }
            }
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'countPageViews'          => $countPageViews,
            'countBuilderViews'       => $countBuilderViews,
            'countUsers'              => $countUsers,
            'countEmails'             => $countEmails,
            'countTemplates'          => $countTemplates,
            'countTeamPlans'          => $countTeamPlans,
            'countRecentPageViews'    => $countRecentPageViews,
            'countRecentBuilderViews' => $countRecentBuilderViews,
            'countRecentTemplates'    => $countRecentTemplates,
            'countRecentEmails'       => $countRecentEmails,
            'countRecentUsers'        => $countRecentUsers,
            'countRecentTeamPlans'    => $countRecentTeamPlans,
            'billingLogs'             => $billingLogs,
            'recentEmails'            => $recentEmails,
            'recentErrorLogs'         => $recentErrorLogs,
            'users'                   => $users,
            'orgs'                    => $orgs
        ]);
    }

    /**
     * @Route("/notices", name="notices")
     *
     * @param NoticeRepository $noticeRepository
     *
     * @return Response
     * @throws Exception
     */
    public function noticesAction(NoticeRepository $noticeRepository): Response
    {
        $notices = $noticeRepository->find();

        return $this->render('admin/dashboard/notices.html.twig', [
            'notices' => $notices
        ]);
    }

    /**
     * @Route("/notices/create", name="notices_create")
     *
     * @param Request          $request
     * @param NoticeRepository $noticeRepository
     *
     * @return Response
     * @throws Exception
     */
    public function createNoticeAction(Request $request, NoticeRepository $noticeRepository): Response
    {
        $notice = new Notice();

        if ($request->isPost()) {
            $content  = $request->post->get('content');
            $location = $request->post->get('location');
            $name     = $request->post->get('name');
            if (empty($name) || empty($content)) {
                $this->flash->error('Missing required values.');
            } else {
                $notice
                    ->setName($name)
                    ->setContent($content)
                    ->setLocation($location);
                $noticeRepository->insert($notice);
                $this->flash->success('Notice created.');

                return $this->redirectToRoute('admin_dashboard_notices');
            }
        }

        return $this->render('admin/dashboard/notice_create.html.twig', [
            'notice'    => $notice,
            'isEditing' => false
        ]);
    }

    /**
     * @Route("/notices/preview", name="notices_preview", method={"POST"})
     *
     * @param Request $request
     * @param Redis   $redis
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function previewNoticeAction(Request $request, Redis $redis): JsonResponse
    {
        $content = $request->json->get('content');
        $key = Strings::uuid();
        $redis->set($key, $content);

        return $this->json([
            'redirect' => $this->routeGenerator->generate('index') . '?preview_notice=' . $key
        ]);
    }

    /**
     * @Route("/notices/{id}", name="notices_edit")
     *
     * @param int              $id
     * @param Request          $request
     * @param NoticeRepository $noticeRepository
     *
     * @return Response
     * @throws Exception
     */
    public function editNoticeAction(int $id, Request $request, NoticeRepository $noticeRepository): Response
    {
        $notice = $noticeRepository->findByID($id);
        if (!$notice) {
            $this->throwNotFound();
        }

        if ($request->isPost()) {
            $content  = $request->post->get('content');
            $location = $request->post->get('location');
            $name     = $request->post->get('name');
            if (empty($name) || empty($content)) {
                $this->flash->error('Missing required values.');
            } else {
                $notice
                    ->setName($name)
                    ->setContent($content)
                    ->setLocation($location);
                $noticeRepository->update($notice);
                $this->flash->success('Notice updated.');

                return $this->redirectToRoute('admin_dashboard_notices');
            }
        }

        return $this->render('admin/dashboard/notice_create.html.twig', [
            'notice'    => $notice,
            'isEditing' => true
        ]);
    }

    /**
     * @Route("/notices/{id}/remove", name="notices_remove")
     *
     * @param int              $id
     * @param NoticeRepository $noticeRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function deleteNoticeAction(int $id, NoticeRepository $noticeRepository): RedirectResponse
    {
        $notice = $noticeRepository->findByID($id);
        if (!$notice) {
            $this->throwNotFound();
        }

        $noticeRepository->delete($notice);
        $this->flash->success('Notice deleted.');

        return $this->redirectToRoute('admin_dashboard_notices');
    }
}
