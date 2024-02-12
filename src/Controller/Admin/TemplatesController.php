<?php
namespace Controller\Admin;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Util\Tokens;
use BlocksEdit\Util\TokensTrait;
use Exception;
use Repository\AccessRepository;
use Repository\EmailHistoryRepository;
use Repository\TemplateHistoryRepository;
use Repository\UserRepository;
use Repository\EmailRepository;
use Repository\Exception\CreateException;
use Repository\OrganizationsRepository;
use Service\PeopleService;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"SITE_ADMIN_2FA"})
 * @Route("/admin/templates", name="admin_templates_")
 */
class TemplatesController extends Controller
{
    use TokensTrait;

    /**
     * @Route(name="index")
     *
     * @param Request                 $request
     * @param UserRepository          $userRepository
     * @param TemplatesRepository     $templatesRepository
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(
        Request $request,
        UserRepository $userRepository,
        TemplatesRepository $templatesRepository,
        OrganizationsRepository $organizationsRepository
    ): Response
    {
        $limit     = 50;
        $search    = $request->query->get('search');
        $searchOrg = $request->query->get('org');
        $page      = $request->query->getInt('page', 1);
        $offset    = ($page - 1) * $limit;
        $templates = [];
        $total     = 0;

        if ($search) {
            if (is_numeric($search)) {
                $template = $templatesRepository->findByID($search);
                if ($template) {
                    return $this->redirectToRoute('admin_templates_edit', ['id' => $template['tmp_id']]);
                }
            } else {
                $templates = $templatesRepository->findByTitle($search, $limit, $offset);
                $all       = $templatesRepository->findByTitle($search);
                $total     = count($all);
            }
        } else if ($searchOrg) {
            $org = $organizationsRepository->findByID($searchOrg);
            if (!$org) {
                $this->flash->error('Organization not found.');
            } else {
                $templates = $templatesRepository->findByOrg($org['org_id'], $limit, $offset);
                $all       = $templatesRepository->findByOrg($org['org_id']);
                $total     = count($all);
            }
        } else {
            $templates = $templatesRepository->findAll($limit, $offset);
            $all       = $templatesRepository->findAll();
            $total     = count($all);
        }

        $users = [];
        $orgs  = [];
        foreach($templates as $template) {
            $uid = $template['tmp_usr_id'];
            if (!isset($users[$uid])) {
                $user = $userRepository->findByID($uid);
                if ($user) {
                    $users[$uid] = $user;
                }
            }

            $oid = $template['tmp_org_id'];
            if (!isset($orgs[$oid])) {
                $org = $organizationsRepository->findByID($oid);
                if ($org) {
                    $orgs[$oid]= $org;
                }
            }
        }

        return $this->render('admin/templates/index.html.twig', [
            'templates'  => $templates,
            'users'      => $users,
            'orgs'       => $orgs,
            'search'     => $search,
            'searchOrg'  => $searchOrg,
            'totalPages' => ceil($total / $limit),
            'total'      => $total,
            'page'       => $page
        ]);
    }

    /**
     * @Route("/html", name="html")
     *
     * @param Request                   $request
     * @param EmailRepository           $emailRepository
     * @param TemplatesRepository       $templatesRepository
     * @param TemplateHistoryRepository $templateHistoryRepository
     * @param EmailHistoryRepository    $emailHistoryRepository
     *
     * @return Response
     * @throws Exception
     */
    public function htmlAction(
        Request $request,
        EmailRepository $emailRepository,
        TemplatesRepository $templatesRepository,
        TemplateHistoryRepository $templateHistoryRepository,
        EmailHistoryRepository $emailHistoryRepository
    ): Response
    {
        $type = $request->query->get('type');
        $id   = $request->query->getInt('id');

        if ($request->getMethod() === 'POST') {
            $html = $request->request->get('html');
            $action = $request->request->get('action');

            if ($type === 'email') {
                $version      = $emailHistoryRepository->findLatestVersion($id);
                $emailHistory = $emailHistoryRepository->findByEmailVersion($id, $version);
                if ($emailHistory) {
                    $emailHistory->setHtml($html);
                    $emailHistoryRepository->update($emailHistory);
                }
                $email = $emailRepository->findByID($id);
                $tid = $email['ema_tmp_id'];
            } else {
                $templateHistory = $templateHistoryRepository->findLatest($id);
                if ($templateHistory) {
                    $templateHistory->setHtml($html);
                    $templateHistoryRepository->update($templateHistory);
                }
                $tid = $id;
            }

            $this->flash->success('Updated!');

            if ($action === 'SAVE & NEXT' && $type === 'email') {
                $next = $emailRepository->findNextEmail($id);
                if ($next) {
                    return $this->redirectToRoute('admin_templates_html', [], ['id' => $next['ema_id'], 'type' => 'email']);
                }
            }

            return $this->redirectToRoute('admin_templates_edit', ['id' => $tid]);
        }

        if ($type === 'email') {
            $email        = $emailRepository->findByID($id, true);
            $version      = $emailHistoryRepository->findLatestVersion($id);
            $emailHistory = $emailHistoryRepository->findByEmailVersion($id, $version);
            $html         = $emailHistory->getHtml();
            $title        = date('F j, Y', $email->getCreatedAt());
        } else {
            $template        = $templatesRepository->findByID($id, true);
            $templateHistory = $templateHistoryRepository->findLatest($id);
            $html            = $templateHistory->getHtml();
            $title           = date('F j, y', $template->getCreatedAt());
        }

        return $this->render('admin/templates/html.html.twig', [
            'title' => $title,
            'html'  => $html,
            'type'  => $type,
            'id'    => $id
        ]);
    }

    /**
     * @Route("/{id}", name="edit")
     *
     * @param int                 $id
     * @param Request             $request
     * @param UserRepository      $userRepository
     * @param AccessRepository    $accessRepository
     * @param EmailRepository     $emailRepository
     * @param TemplatesRepository $templatesRepository
     *
     * @return Response
     * @throws Exception
     */
    public function editAction(
        int $id,
        Request $request,
        UserRepository $userRepository,
        AccessRepository $accessRepository,
        EmailRepository $emailRepository,
        TemplatesRepository $templatesRepository
    ): Response
    {
        $template = $templatesRepository->findByID($id);
        if (!$template) {
            $this->throwNotFound();
        }

        $owner      = $userRepository->findByID($template['tmp_usr_id']);
        $editors    = $accessRepository->findCollaboratorsByTemplate($id);
        $token      = $this->tokens->generateToken($template['tmp_id'], Tokens::TOKEN_PUBLIC);
        $previewUrl = $this->url('build_template_preview', [
            'id'    => $template['tmp_id'],
            'token' => $token
        ]);

        $emails = $emailRepository->findByTemplate($id, 'ema_created_at DESC');
        foreach($emails as &$email) {
            $email['previewUrl'] = $this->routeGenerator->generate('build_email_preview', [
                'id'    => $email['ema_id'],
                'token' => $email['ema_token']
            ], 'absolute');
        }

        if ($request->isPost()) {
            $title        = $request->request->get('title');
            $owner        = $request->request->get('owner');
            $ownerChanged = $request->request->getInt('ownerChanged');
            $oid          = $request->request->getInt('oid');

            if ($ownerChanged) {
                $user = $userRepository->findByEmail($owner);
                if (!$user) {
                    $this->flash->error('User not found.');
                    return $this->redirectToRoute('admin_templates_edit', ['id' => $id]);
                }

                $templatesRepository->updateOwner($id, $user['usr_id']);
            }

            $templatesRepository->updateOrgId($id, $oid);
            $templatesRepository->updateTitle(0, $id, $title, true);

            $this->flash->success('Template updated.');
            return $this->redirectToRoute('admin_templates_edit', ['id' => $id]);
        }

        return $this->render('admin/templates/edit.html.twig', [
            'socket'     => $this->config->socket,
            'template'   => $template,
            'owner'      => $owner,
            'emails'     => $emails,
            'editors'    => $editors,
            'previewUrl' => $previewUrl
        ]);
    }

    /**
     * @Route("/{id}/members", name="members", methods={"POST"})
     *
     * @param int                 $id
     * @param int                 $uid
     * @param Request             $request
     * @param PeopleService       $peopleService
     * @param UserRepository      $userRepository
     * @param AccessRepository    $accessRepository
     * @param TemplatesRepository $templatesRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function membersAction(
        int $id,
        int $uid,
        Request $request,
        PeopleService $peopleService,
        UserRepository $userRepository,
        AccessRepository $accessRepository,
        TemplatesRepository $templatesRepository
    ): RedirectResponse {
        $template = $templatesRepository->findByID($id);
        if (!$template) {
            $this->throwNotFound();
        }

        $owner = $userRepository->findByID($template['tmp_usr_id']);
        if (!$owner) {
            $templatesRepository->updateOwner($id, $uid);
            $owner = $this->getUser();
        }

        $name      = $request->request->get('name');
        $email     = $request->request->get('email');
        $editorsOn = $request->request->getArray('editors');

        if ($name && $email) {
            $user = $userRepository->findByEmail($email);
            if ($user) {
                try {
                    $inviteData = [
                        'uid'       => 0,
                        'usr_name'  => $user['usr_name'],
                        'usr_email' => $user['usr_email']
                    ];
                    $peopleService->add(
                        $owner['usr_id'],
                        $template['tmp_id'],
                        $template['tmp_org_id'],
                        $inviteData
                    );
                } catch (CreateException $e) {
                    $this->flash->error($e->getMessage());

                    return $this->redirectToRoute('admin_templates_edit', ['id' => $id]);
                }

                $editorsOn[(int)$user['usr_id']] = 'on';
            }
        }

        $editors = $accessRepository->findCollaboratorsByTemplate($id);
        foreach($editors as $uid => $editor) {
            if (!isset($editorsOn[$uid])) {
                $accessRepository->adminRemoveAccess($id, $uid, $template['tmp_org_id']);
            }
        }

        $this->flash->success('Editors updated.');

        return $this->redirectToRoute('admin_templates_edit', ['id' => $id]);
    }

    /**
     * @Route("/{id}/delete", name="delete")
     *
     * @param int                 $id
     * @param TemplatesRepository $templatesRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function deleteAction(int $id, TemplatesRepository $templatesRepository): RedirectResponse
    {
        $template = $templatesRepository->findByID($id);
        if (!$template) {
            $this->throwNotFound();
        }

        $templatesRepository->deleteByID($id);
        $this->flash->success('Template deleted.');

        return $this->redirectToRoute('admin_templates_index');
    }

    /**
     * @Route("/{id}/emails/{eid}", name="delete_email")
     *
     * @param int             $id
     * @param int             $eid
     * @param EmailRepository $emailRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function deleteEmailAction(
        int $id,
        int $eid,
        EmailRepository $emailRepository
    ): RedirectResponse {
        $email = $emailRepository->findByID($eid);
        if (!$email) {
            $this->throwNotFound();
        }

        $emailRepository->deleteByID($eid);
        $this->flash->success('Email deleted.');

        return $this->redirectToRoute('admin_templates_edit', ['id' => $id]);
    }
}
