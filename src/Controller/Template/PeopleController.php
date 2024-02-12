<?php
namespace Controller\Template;

use BlocksEdit\Http\Annotations\InjectTemplate;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\System\Serializer;
use Entity\Organization;
use Entity\Template;
use Entity\User;
use Exception;
use Repository\AccessRepository;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Repository\BillingPlanRepository;
use Repository\Exception\CreateException;
use Repository\InvitationsRepository;
use Service\PeopleService;

/**
 * @IsGranted({"USER"})
 */
class PeopleController extends Controller
{
    /**
     * @Route("/people/{id}", name="people")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(
        Request $request
    ): Response {
        return $this->renderFrontend($request);
    }

    /**
     * @IsGranted({"template"})
     * @Route("/api/v1/people/{id}", name="api_v1_people", methods={"GET"})
     * @InjectTemplate()
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param User                         $user
     * @param Organization                 $organization
     * @param Template                     $template
     * @param Serializer                   $serializer
     * @param UserRepository               $userRepository
     * @param OrganizationAccessRepository $orgAccessRepository
     * @param AccessRepository             $accessRepository
     * @param InvitationsRepository        $invitationsRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function loadAction(
        int $uid,
        int $oid,
        User $user,
        Organization $organization,
        Template $template,
        Serializer $serializer,
        UserRepository $userRepository,
        OrganizationAccessRepository $orgAccessRepository,
        AccessRepository $accessRepository,
        InvitationsRepository $invitationsRepository
    ): JsonResponse
    {
        $billingPlan = $this->getBillingPlan();
        // $showUpgradeError = false;

        try {
            $collaborators = $this->getCollaborators(
                $template,
                $serializer,
                $accessRepository,
                $orgAccessRepository
            );

            // Find sub-accounts within the organization.
            $accountUsers = $this->getAccountUsers(
                $template->getUser(),
                $organization,
                $serializer,
                $userRepository,
                $orgAccessRepository
            );

            $sInvites = [];
            $invites = $invitationsRepository->findByTemplate($template->getId());
            foreach($invites as $invite) {
                $sInvites[] = $serializer->serializeInvite($invite);
            }

            $isOwner = $orgAccessRepository->isOwner($uid, $oid);

            return $this->json([
                'users'            => $collaborators,
                'accountUsers'     => $accountUsers,
                'showUpgradeError' => false,
                'invites'          => $sInvites,
                'tmpTitle'         => $template['tmp_title'],
                'billingPlan'      => $serializer->serializeBillingPlan($billingPlan, $user),
                'isOwner'          => $isOwner
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * @IsGranted({"template"})
     * @Route("/api/v1/people/{id}", name="api_v1_people_invite", methods={"PUT"})
     * @InjectTemplate()
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param Organization                 $organization
     * @param Template                     $template
     * @param Request                      $request
     * @param Serializer                   $serializer
     * @param PeopleService                $peopleService
     * @param BillingPlanRepository        $billingPlanRepository
     * @param UserRepository               $userRepository
     * @param AccessRepository             $accessRepository
     * @param OrganizationAccessRepository $orgAccessRepository
     * @param InvitationsRepository        $invitationsRepository
     *
     * @return JsonResponse
     */
    public function inviteAction(
        int $uid,
        int $oid,
        Organization $organization,
        Template $template,
        Request $request,
        Serializer $serializer,
        PeopleService $peopleService,
        BillingPlanRepository $billingPlanRepository,
        UserRepository $userRepository,
        AccessRepository $accessRepository,
        OrganizationAccessRepository $orgAccessRepository,
        InvitationsRepository $invitationsRepository
    ): JsonResponse
    {
        try {
            $billingPlan = $this->getBillingPlan();
            if ($billingPlan->isSolo()) {
                if ($billingPlan->isTrialComplete()) {
                    $this->throwBadRequest();
                } else {
                    $billingPlanRepository->upgradeToTrial($billingPlan);
                }
            }

            try {
                $inviteData = [
                    'uid'       => $request->json->get('uid'),
                    'usr_name'  => trim($request->json->get('name')),
                    'usr_email' => trim($request->json->get('email'))
                ];
                $peopleService->add($uid, $template->getId(), $oid, $inviteData);
            } catch (CreateException $e) {
                return $this->json([
                    'error' => $e->getMessage()
                ]);
            }

            $collaborators = $this->getCollaborators(
                $template,
                $serializer,
                $accessRepository,
                $orgAccessRepository
            );

            // Find sub-accounts within the organization.
            $accountUsers = $this->getAccountUsers(
                $template->getUser(),
                $organization,
                $serializer,
                $userRepository,
                $orgAccessRepository
            );

            $sInvites = [];
            $invites = $invitationsRepository->findByTemplate($template->getId());
            foreach($invites as $invite) {
                $sInvites[] = $serializer->serializeInvite($invite);
            }

            if ($inviteData['uid']) {
                return $this->json([
                    'success' => 'Team member added.',
                    'templatePeople' => [
                        'users'        => $collaborators,
                        'accountUsers' => $accountUsers,
                        'invites'      => $sInvites
                    ]
                ]);
            } else {
                return $this->json([
                    'success' => 'An invitation has been sent.',
                    'templatePeople' => [
                        'users'        => $collaborators,
                        'accountUsers' => $accountUsers,
                        'invites'      => $sInvites
                    ]
                ]);
            }
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
            return $this->json([
                'error' => 'System error.'
            ]);
        }
    }

    /**
     * @IsGranted({"template"})
     * @Route("/api/v1/people/{id}/invites/{rid}", name="api_v1_people_invite_remove", methods={"DELETE"})
     * @InjectTemplate()
     *
     * @param int                          $id
     * @param int                          $rid
     * @param int                          $uid
     * @param int                          $oid
     * @param Organization                 $organization
     * @param Template                     $template
     * @param Request                      $request
     * @param Serializer                   $serializer
     * @param UserRepository               $userRepository
     * @param AccessRepository             $accessRepository
     * @param OrganizationAccessRepository $orgAccessRepository
     * @param InvitationsRepository        $invitationsRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function removeInviteAction(
        int $id,
        int $rid,
        int $uid,
        int $oid,
        Organization $organization,
        Template $template,
        Request $request,
        Serializer $serializer,
        UserRepository $userRepository,
        AccessRepository $accessRepository,
        OrganizationAccessRepository $orgAccessRepository,
        InvitationsRepository $invitationsRepository
    ): JsonResponse
    {
        $iid = $request->query->get('iid');
        $accessRepository->removeAccess($uid, $id, $rid, $iid, $oid);

        $collaborators = $this->getCollaborators(
            $template,
            $serializer,
            $accessRepository,
            $orgAccessRepository
        );

        // Find sub-accounts within the organization.
        $accountUsers = $this->getAccountUsers(
            $template->getUser(),
            $organization,
            $serializer,
            $userRepository,
            $orgAccessRepository
        );

        $sInvites = [];
        $invites  = $invitationsRepository->findByTemplate($template->getId());
        foreach($invites as $invite) {
            $sInvites[] = $serializer->serializeInvite($invite);
        }

        return $this->json([
            'success' => 'User removed!',
            'templatePeople' => [
                'users'        => $collaborators,
                'accountUsers' => $accountUsers,
                'invites'      => $sInvites
            ]
        ]);
    }

    /**
     * @param Template                     $template
     * @param Serializer                   $serializer
     * @param AccessRepository             $accessRepository
     * @param OrganizationAccessRepository $orgAccessRepository
     *
     * @return array
     * @throws Exception
     */
    protected function getCollaborators(
        Template $template,
        Serializer $serializer,
        AccessRepository $accessRepository,
        OrganizationAccessRepository $orgAccessRepository
    ): array
    {
        $collaborators = [];
        foreach($orgAccessRepository->findOwners($template->getOrganization()) as $roleAccess) {
            $user = $roleAccess->getUser();
            if ($user) {
                $user->setIsResponded(true);
                $user->setIsOwner(true);
                if (!isset($collaborators[$user->getId()])) {
                    $collaborators[$user->getId()] = $user;
                }
            }
        }
        foreach($orgAccessRepository->findAdmins($template->getOrganization()) as $roleAccess) {
            $user = $roleAccess->getUser();
            if ($user) {
                $user->setIsResponded(true);
                $user->setIsAdmin(true);
                if (!isset($collaborators[$user->getId()])) {
                    $collaborators[$user->getId()] = $user;
                }
            }
        }

        foreach($accessRepository->findByTemplate($template) as $access) {
            $author = $access->getUser();
            if (isset($collaborators[$author->getId()])) {
                continue;
            }
            $collaborators[$author->getId()] = $author;
            if ($access->isResponded()) {
                $author->setIsResponded(true);
            }

            if (!$access->isResponded()) {
                $roles = $orgAccessRepository->findByUser($author);
                foreach($roles as $role) {
                    if (
                        $orgAccessRepository->isEditor(
                            $access->getUser()->getId(),
                            $role->getOrganization()->getId()
                        )
                        || $orgAccessRepository->isAdmin(
                            $access->getUser()->getId(),
                            $role->getOrganization()->getId()
                        )
                    ) {
                        $access->setResponded(true);
                    }
                }
            }
        }

        $users = [];
        foreach($collaborators as $collaborator) {
            $users[] = $serializer->serializeUser($collaborator);
        }

        return $users;
    }

    /**
     * @param User                         $user
     * @param Organization                 $organization
     * @param Serializer                   $serializer
     * @param UserRepository               $userRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return array
     * @throws Exception
     */
    protected function getAccountUsers(
        User $user,
        Organization $organization,
        Serializer $serializer,
        UserRepository $userRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): array
    {
        $aid = $user;
        if ($user->getParent()) {
            $aid = $user->getParent();
        }

        $orgUsers = [];
        foreach($organizationAccessRepository->findByOrganization($organization) as $access) {
            if ($access->getUser()) {
                $orgUsers[] = $access->getUser()->getId();
            }
        }

        $accountUsers = [];
        foreach ($userRepository->findAccountUsers($aid) as $accountUser) {
            if (in_array($accountUser->getId(), $orgUsers)) {
                $accountUsers[] = $serializer->serializeUser($accountUser);
            }
        }

        return $accountUsers;
    }
}
