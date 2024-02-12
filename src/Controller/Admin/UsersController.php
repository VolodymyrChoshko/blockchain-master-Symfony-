<?php
namespace Controller\Admin;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Util\Dates;
use Entity\OrganizationAccess;
use Exception;
use PHPGangsta_GoogleAuthenticator;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Repository\EmailRepository;
use Repository\Exception\ChangePasswordException;
use Repository\OrganizationsRepository;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"SITE_ADMIN_2FA"})
 * @Route("/admin/users", name="admin_users_")
 */
class UsersController extends Controller
{
    /**
     * @Route("", name="index")
     *
     * @param Request             $request
     * @param UserRepository      $userRepository
     * @param TemplatesRepository $templatesRepository
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(
        Request $request,
        UserRepository $userRepository,
        TemplatesRepository $templatesRepository
    ): Response
    {
        $limit  = 50;
        $search = $request->query->get('search');
        $page   = $request->query->getInt('page', 1);
        $offset = ($page - 1) * $limit;
        $total  = 0;

        if ($search) {
            if (is_numeric($search)) {
                $users = $userRepository->findByID($search);
                if ($users) {
                    return $this->redirectToRoute('admin_users_edit', ['id' => $users['usr_id']]);
                }
            } else if (strpos($search, '@') !== false) {
                $users = $userRepository->findByEmail($search);
                if ($users) {
                    return $this->redirectToRoute('admin_users_edit', ['id' => $users['usr_id']]);
                }
            } else {
                $users = $userRepository->findByName($search);
                $total = $userRepository->findFoundRows();
            }
        } else {
            $users = $userRepository->findAll($limit, $offset);
            $total = $userRepository->countAll();
        }

        foreach($users as &$user) {
            $user['count_templates'] = $templatesRepository->countByUser($user['usr_id']);
        }

        return $this->render('admin/users/index.html.twig', [
            'search'     => $search,
            'totalPages' => ceil($total / $limit),
            'total'      => $total,
            'users'      => $users,
            'page'       => $page
        ]);
    }

    /**
     * @Route("/{id}", name="edit")
     *
     * @param int                     $id
     * @param Request                 $request
     * @param UserRepository          $userRepository
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return Response
     * @throws Exception
     */
    public function editAction(
        int $id,
        Request $request,
        UserRepository $userRepository,
        OrganizationsRepository $organizationsRepository
    ): Response
    {
        $user = $userRepository->findByID($id);
        if (!$user) {
            $this->throwNotFound();
        }

        $timezones = Dates::getListOfTimezones();
        $orgs      = $organizationsRepository->findByUser($id);

        $parent = null;
        if ($user['usr_parent_id']) {
            $parent = $userRepository->findByID($user['usr_parent_id']);
        }

        if ($request->isPost()) {
            try {
                $name        = $request->request->get('name');
                $email       = $request->request->get('email');
                $job         = $request->request->get('job');
                $org         = $request->request->get('organization');
                $timezone    = $request->request->get('timezone');
                $isSiteAdmin = $request->request->get('isSiteAdmin') === 'on';
                $referral    = $request->request->get('referral');

                if ($email !== $user['usr_email']) {
                    $existing = $userRepository->findByEmail($email);
                    if ($existing && $existing['usr_id'] !== $user['usr_id']) {
                        $this->flash->error('Email address already in use.');
                        throw new Exception();
                    }
                }

                $userRepository->updateSingle($id, 'usr_name', $name);
                $userRepository->updateSingle($id, 'usr_email', $email);
                $userRepository->updateSingle($id, 'usr_job', $job);
                $userRepository->updateSingle($id, 'usr_timezone', $timezone);
                $userRepository->updateSingle($id, 'usr_organization', $org);
                $userRepository->updateSingle($id, 'usr_join_ref', $referral);
                $user = $userRepository->findByID($id);

                if ($isSiteAdmin && !$user['usr_is_site_admin']) {
                    $userRepository->updateSingle($id, 'usr_is_site_admin', '1');

                    return $this->redirectToRoute('admin_users_2fa', ['id' => $id]);
                } else if (!$isSiteAdmin && $user['usr_is_site_admin']) {
                    $userRepository->updateSingle($id, 'usr_is_site_admin', '0');
                    $userRepository->updateSingle($id, 'usr_2fa_secret', '');
                }

                $this->flash->success('User updated.');
                return $this->redirectToRoute('admin_users_edit', ['id' => $id]);
            } catch (Exception $e) {}
        }

        return $this->render('admin/users/edit.html.twig', [
            'user'      => $user,
            'orgs'      => $orgs,
            'parent'    => $parent,
            'timezones' => array_flip($timezones),
            'assets'    => [
                'js' => [
                    'build/js/admin.js'
                ]
            ]
        ]);
    }

    /**
     * @Route("/{id}/orgs", name="orgs", methods={"POST"})
     *
     * @param int                          $id
     * @param Request                      $request
     * @param UserRepository               $userRepository
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function orgsAction(
        int $id,
        Request $request,
        UserRepository $userRepository,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): RedirectResponse
    {
        $user = $userRepository->findByID($id, true);
        if (!$user) {
            $this->throwNotFound();
        }

        $oid      = $request->request->getInt('oid');
        $access   = $request->request->getInt('access');
        $orgsOn   = $request->request->getArray('orgs');
        $accesses = $request->request->getArray('accesses');

        if ($oid && $access) {
            $org = $organizationsRepository->findByID($oid, true);
            if (!$org) {
                $this->flash->error("Organization $oid not found.");
            } else {
                $orgAccess = (new OrganizationAccess())
                    ->setUser($user)
                    ->setOrganization($org)
                    ->setAccess($access);
                $organizationAccessRepository->insert($orgAccess);
                $orgsOn[$oid]   = 'on';
                $accesses[$oid] = $access;
            }
        }

        $orgs = $organizationsRepository->findByUser($id);
        foreach($orgs as $o) {
            $oid = $o['org_id'];
            $org = $organizationsRepository->findByID($oid, true);
            if (!isset($orgsOn[$oid])) {
                $organizationAccessRepository->deleteByUser($id, $oid);
            } else if ($accesses[$oid] !== (int)$o['rba_access']) {
                $organizationAccessRepository->deleteByUser($id, $oid);
                $orgAccess = (new OrganizationAccess())
                    ->setUser($user)
                    ->setOrganization($org)
                    ->setAccess($accesses[$oid]);
                $organizationAccessRepository->insert($orgAccess);
            }
        }

        $this->flash->success('User organizations updated');

        return $this->redirectToRoute('admin_users_edit', ['id' => $id]);
    }

    /**
     * @Route("/{id}/password", name="password_reset", methods={"POST"})
     *
     * @param int            $id
     * @param Request        $request
     * @param UserRepository $userRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function resetPasswordAction(
        int $id,
        Request $request,
        UserRepository $userRepository
    ): RedirectResponse
    {
        $user = $userRepository->findByID($id);
        if (!$user) {
            throw new Exception('User not found.');
        }

        try {
            $userRepository
                ->changePassword($user['usr_id'], $request->request->get('password'));
            $this->flash->success('Password updated.');

            return $this->redirectToRoute('admin_users_edit', ['id' => $id]);
        } catch (ChangePasswordException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @Route("/{id}/2fa", name="2fa")
     *
     * @param int            $id
     * @param Request        $request
     * @param UserRepository $userRepository
     *
     * @return Response
     * @throws Exception
     */
    public function twoFactorAuthAction(
        int $id,
        Request $request,
        UserRepository $userRepository
    ): Response
    {
        $user = $userRepository->findByID($id);
        if (!$user) {
            $this->throwNotFound();
        }

        if ($request->isPost()) {
            $secret = $request->request->get('secret');
            $userRepository->updateSingle($id, 'usr_2fa_secret', $secret);
            $this->flash->success('User updated.');

            return $this->redirectToRoute('admin_users_edit', ['id' => $id]);
        }

        $label = 'Blocks Edit';
        if (gethostname() !== 'ip-172-31-100-96') {
            if (strpos($request->getUri(), 'stagingapp.blocksedit.com') !== false) {
                $label .= ' Staging';
            } else {
                $label .= ' Dev';
            }
        }

        $ga        = new PHPGangsta_GoogleAuthenticator();
        $secret    = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($label, $secret);

        return $this->render('admin/users/2fa.html.twig', [
            'user'      => $user,
            'secret'    => $secret,
            'qrCodeUrl' => $qrCodeUrl
        ]);
    }

    /**
     * @Route("/{id}/login", name="login")
     *
     * @param int                     $id
     * @param Request                 $request
     * @param UserRepository          $userRepository
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function loginAsUserAction(
        int $id,
        Request $request,
        UserRepository $userRepository,
        OrganizationsRepository $organizationsRepository
    ): RedirectResponse {
        $user = $userRepository->findByID($id);
        if (!$user) {
            $this->throwNotFound();
        }

        $bestOrg = 0;
        foreach($organizationsRepository->findByUser($id) as $org) {
            $bestOrg = $org['rba_org_id'];
            if ($org['rba_access'] === '1') {
                break;
            }
        }

        $request->session->set('user', $user);
        $request->setCookie(
            'remember',
            base64_encode($id . '+' . md5($user['usr_pass'], PASSWORD_DEFAULT)),
            time() + 60 * 60 * 24 * 30
        );

        $url = $this->routeGenerator->generate('index', [], 'absolute', $bestOrg);

        return $this->redirect($url);
    }

    /**
     * @Route("/{id}/delete", name="delete", methods={"POST"})
     *
     * @param int                          $id
     * @param EmailRepository              $emailRepository
     * @param UserRepository               $userRepository
     * @param TemplatesRepository          $templatesRepository
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function deleteAction(
        int $id,
        EmailRepository $emailRepository,
        UserRepository $userRepository,
        TemplatesRepository $templatesRepository,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): RedirectResponse {
        $user = $userRepository->findByID($id);
        if (!$user) {
            $this->throwNotFound();
        }

        $newsletters = $templatesRepository->findByUser($id);
        foreach ($newsletters as $newsletter) {
            $emails = $emailRepository->findByTemplate($newsletter['tmp_id']);
            foreach ($emails as $email) {
                $emailRepository->deleteByID($email['ema_id']);
            }
            $templatesRepository->deleteByID($newsletter['tmp_id']);
        }
        $rbac = $organizationAccessRepository->findFirstByUserAndAccess($id, 1);
        if (!empty($rbac['rba_org_id'])) {
            $organizationsRepository->deleteOrganization($rbac['rba_org_id']);
        }
        $userRepository->deleteByID($id);
        $this->flash->success('User has been deleted.');

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * @Route("/email/match")
     *
     * @param Request        $request
     * @param UserRepository $userRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function matchAction(Request $request, UserRepository $userRepository): JsonResponse
    {
        $matches = $userRepository->findByMatchingEmail($request->query->get('search'));
        $values  = [];
        foreach($matches as $match) {
            $values[] = [
                'email' => $match['usr_email'],
                'name'  => $match['usr_name']
            ];
        }

        return $this->json($values);
    }
}
