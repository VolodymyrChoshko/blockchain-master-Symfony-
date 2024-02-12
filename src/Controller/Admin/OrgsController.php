<?php
namespace Controller\Admin;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use Entity\Organization;
use Entity\OrganizationAccess;
use Exception;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Repository\OrganizationsRepository;
use Repository\SourcesRepository;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"SITE_ADMIN_2FA"})
 * @Route("/admin/orgs", name="admin_orgs_")
 */
class OrgsController extends Controller
{
    /**
     * @Route("", name="index")
     *
     * @param Request                 $request
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(
        Request $request,
        OrganizationsRepository $organizationsRepository
    ): Response {
        $limit  = 50;
        $search = $request->query->get('search');
        $page   = $request->query->getInt('page', 1);
        $offset = ($page - 1) * $limit;
        $total  = 0;
        $orgs   = [];

        if ($search) {
            if (is_numeric($search)) {
                $org = $organizationsRepository->findByID($search);
                if ($org) {
                    return $this->redirectToRoute('admin_orgs_edit', ['id' => $org['org_id']]);
                }
            } else {
                $orgs  = $organizationsRepository->findByName($search, $limit, $offset);
                $all   = $organizationsRepository->findByName($search);
                $total = count($all);
            }
        } else {
            $orgs  = $organizationsRepository->findAll($limit, $offset);
            $total = $organizationsRepository->countAll();
        }

        return $this->render('admin/orgs/index.html.twig', [
            'orgs'       => $orgs,
            'search'     => $search,
            'totalPages' => ceil($total / $limit),
            'total'      => $total,
            'page'       => $page
        ]);
    }

    /**
     * @Route("/create", name="create")
     *
     * @param Request                      $request
     * @param UserRepository               $userRepository
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return Response
     * @throws Exception
     */
    public function createAction(
        Request $request,
        UserRepository $userRepository,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): Response {
        $formValues = [
            'name'  => '',
            'owner' => '',
            'admin' => ''
        ];

        if ($request->isPost()) {
            try {
                $formValues = $request->post->all();
                if (empty($formValues['name']) || empty($formValues['owner'])) {
                    throw new Exception('Missing required values.');
                }

                $user = $userRepository->findByEmail($formValues['owner'], true);
                if (!$user) {
                    throw new Exception('Owner not found.');
                }

                $org = (new Organization())
                    ->setName($formValues['name']);
                $organizationsRepository->insert($org);

                $orgAccess = (new OrganizationAccess())
                    ->setUser($user)
                    ->setOrganization($org)
                    ->setAccess(OrganizationAccess::OWNER);
                $organizationAccessRepository->insert($orgAccess);
                if (!empty($formValues['admin'])) {
                    $user = $userRepository->findByEmail($formValues['admin'], true);
                    if (!$user) {
                        throw new Exception('Admin not found.');
                    }

                    $orgAccess = (new OrganizationAccess())
                        ->setUser($user)
                        ->setOrganization($org)
                        ->setAccess(OrganizationAccess::ADMIN);
                    $organizationAccessRepository->insert($orgAccess);
                }

                $this->flash->success('Organization created.');

                return $this->redirectToRoute('admin_orgs_edit', ['id' => $org->getId()]);
            } catch (Exception $e) {
                $this->flash->error($e->getMessage());
            }
        }

        return $this->render('admin/orgs/create.html.twig', [
            'formValues' => $formValues
        ]);
    }

    /**
     * @Route("/{id}", name="edit")
     *
     * @param int                          $id
     * @param Request                      $request
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     * @param TemplatesRepository          $templatesRepository
     * @param SourcesRepository            $sourcesRepository
     *
     * @return Response
     * @throws Exception
     */
    public function editAction(
        int $id,
        Request $request,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository,
        TemplatesRepository $templatesRepository,
        SourcesRepository $sourcesRepository
    ): Response
    {
        $org = $organizationsRepository->findByID($id, true);
        if (!$org) {
            $this->throwNotFound();
        }

        $accesses  = $organizationAccessRepository->findByOrganization($org);
        $templates = $templatesRepository->findByOrg($id);
        $sources   = $sourcesRepository->findByOrg($id);

        if ($request->isPost()) {
            $org->setName($request->request->get('name'));
            $organizationsRepository->update($org);
            $this->flash->success('Organization updated.');

            return $this->redirectToRoute('admin_orgs_edit', ['id' => $id]);
        }

        return $this->render('admin/orgs/edit.html.twig', [
            'org'       => $org,
            'sources'   => $sources,
            'templates' => $templates,
            'accesses'  => $accesses
        ]);
    }

    /**
     * @Route("/{id}/members", name="members", methods={"POST"})
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
    public function membersAction(
        int $id,
        Request $request,
        UserRepository $userRepository,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): RedirectResponse {
        $org = $organizationsRepository->findByID($id, true);
        if (!$org) {
            $this->throwNotFound();
        }

        $email    = $request->request->get('email');
        $access   = $request->request->getInt('access');
        $orgsOn   = $request->request->getArray('orgs');
        $accesses = $request->request->getArray('accesses');

        if ($email && $access) {
            $user = $userRepository->findByEmail($email, true);
            if (!$user) {
                $this->flash->error('User not found.');
                return $this->redirectToRoute('admin_orgs_edit', ['id' => $id]);
            }

            $orgAccess = (new OrganizationAccess())
                ->setUser($user)
                ->setOrganization($org)
                ->setAccess($access);
            $organizationAccessRepository->insert($orgAccess);
            $orgsOn[$user->getId()]   = 'on';
            $accesses[$user->getId()] = $access;
        }

        $orgAccesses = $organizationAccessRepository->findByOrganization($org);
        foreach($orgAccesses as $orgAccess) {
            if ($orgAccess->getUser()) {
                $uid = $orgAccess->getUser()->getId();
                if (!isset($orgsOn[$uid])) {
                    $organizationAccessRepository->deleteByUser($uid, $id);
                } else if ($accesses[$uid] !== $orgAccess->getAccess()) {
                    $u = $userRepository->findByID($uid, true);
                    $orgAccess = (new OrganizationAccess())
                        ->setUser($u)
                        ->setOrganization($org)
                        ->setAccess($accesses[$uid]);
                    $organizationAccessRepository->insert($orgAccess);
                }
            }
        }

        $this->flash->success('Organization members updated.');

        return $this->redirectToRoute('admin_orgs_edit', ['id' => $id]);
    }

    /**
     * @Route("/{id}/delete", name="delete", methods={"POST"})
     *
     * @param int                     $id
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function deleteAction(int $id, OrganizationsRepository $organizationsRepository): RedirectResponse
    {
        $org = $organizationsRepository->findByID($id);
        if (!$org) {
            $this->throwNotFound();
        }

        $organizationsRepository->deleteOrganization($id);
        $this->flash->success('Organization deleted.');

        return $this->redirectToRoute('admin_orgs_index');
    }
}
