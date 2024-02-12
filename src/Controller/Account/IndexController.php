<?php
namespace Controller\Account;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\System\Serializer;
use Entity\Organization;
use Entity\OrganizationAccess;
use Exception;
use InvalidArgumentException;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Service\AuthService;
use Repository\BillingPlanRepository;
use Repository\EmailRepository;
use Repository\OrganizationsRepository;
use Repository\TemplatesRepository;

/**
 * @IsGranted({"USER"})
 */
class IndexController extends Controller
{
    /**
     * @Route("/account", name="account")
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
     * @Route("/api/v1/account", name="api_v1_account")
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param Organization                 $organization
     * @param Serializer                   $serializer
     * @param OrganizationsRepository      $organizationsRepo
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return Response
     * @throws Exception
     */
    public function detailsAction(
        int $uid,
        int $oid,
        Organization $organization,
        Serializer $serializer,
        OrganizationsRepository $organizationsRepo,
        OrganizationAccessRepository $organizationAccessRepository
    ): Response {
        $owners  = [];
        $admins  = [];
        $editors = [];

        $accesses = $organizationAccessRepository->findByOrganization($organization);
        foreach($accesses as $access) {
            $user = $access->getUser();
            if ($user) {
                if ($access->isOwner()) {
                    $owners[$user->getId()] = $serializer->serializeUser($user);
                } elseif ($access->isAdmin()) {
                    $admins[$user->getId()] = $serializer->serializeUser($user);
                } elseif ($access->isEditor()) {
                    $editors[$user->getId()] = $serializer->serializeUser($user);
                }
            }
        }

        $billingPlan = $this->getBillingPlan();
        $isIntegrationsDisabled = (
            $billingPlan->isPaused() || ($billingPlan->isTrialComplete() && !$this->getBillingCreditCard())
        );
        if ($billingPlan->isCustom()) {
            $isIntegrationsDisabled = false;
        }

        return $this->json([
            'owners'                 => array_values($owners),
            'admins'                 => array_values($admins),
            'editors'                => array_values($editors),
            'isOwner'                => array_key_exists($uid, $owners),
            'isAdmin'                => array_key_exists($uid, $admins),
            'isEditor'               => array_key_exists($uid, $editors),
            'organization'           => $organizationsRepo->findByID($oid),
            'billingPlan'            => $this->getBillingPlan(),
            'isIntegrationsDisabled' => $isIntegrationsDisabled
        ]);
    }

    /**
     * @IsGranted({"ORG_ADMIN"})
     * @Route("/api/v1/account/organization", name="api_v1_account_organization", methods={"POST"})
     *
     * @param Organization            $organization
     * @param Request                 $request
     * @param OrganizationsRepository $organizationsRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function organizationAction(
        Organization $organization,
        Request $request,
        OrganizationsRepository $organizationsRepository
    ): JsonResponse {
        $values = $request->json->all();
        foreach($values as $key => $value) {
            if ($key === 'name') {
                $organization->setName($value);
                $organizationsRepository->update($organization);
            }
        }

        return $this->json($organization->getId());
    }

    /**
     * @Route("/api/v1/account/organization/{id}", name="api_v1_account_organization_revoke", methods={"DELETE"})
     *
     * @param int                          $id
     * @param int                          $uid
     * @param int                          $oid
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function revokePowersAction(
        int $id,
        int $uid,
        int $oid,
        OrganizationAccessRepository $organizationAccessRepository
    ): JsonResponse
    {
        if ($organizationAccessRepository->isOwner($uid, $oid)) {
            $organizationAccessRepository->deleteByUser(
                $id,
                $oid
            );
        } elseif ($organizationAccessRepository->isAdmin($uid, $oid)) {
            $organizationAccessRepository->deleteByUser(
                $id,
                $oid
            );
        }

        return $this->json('ok');
    }

    /**
     * @IsGranted({"ORG_ADMIN"})
     * @Route("/api/v1/account/organization", name="api_v1_account_organization_invite", methods={"PUT"})
     *
     * @param int                          $uid
     * @param int                          $oid
     * @param array                        $user
     * @param Request                      $request
     * @param Serializer                   $serializer
     * @param UserRepository               $userRepository
     * @param Organization                 $organization
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function inviteAction(
        int $uid,
        int $oid,
        array $user,
        Request $request,
        Serializer $serializer,
        UserRepository $userRepository,
        Organization $organization,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): JsonResponse
    {
        $name   = trim($request->json->get('name'));
        $email  = trim($request->json->get('email'));
        $access = $request->json->get('access');
        if (!$name || !$email || !$access || !in_array($access, [OrganizationAccess::OWNER, OrganizationAccess::ADMIN])) {
            return $this->json([
                'error' => 'Bad Request'
            ]);
        }

        $billingPlan = $this->getBillingPlan();
        if ($billingPlan->isSolo() && $billingPlan->isTrialComplete()) {
            return $this->json([
                'error' => 'Cannot add members to solo plan.'
            ]);
        } else if ($billingPlan->isSolo()) {
            $this->container->get(BillingPlanRepository::class)
                ->upgradeToTrial($billingPlan);
        }

        try {
            $emailUser = $userRepository->findByEmail($email, true);
            $organizationsRepository->invite(
                $uid,
                $oid,
                $emailUser,
                $access,
                $user['usr_name'],
                $name,
                $email
            );

            $owners   = [];
            $admins   = [];
            $editors  = [];
            $accesses = $organizationAccessRepository->findByOrganization($organization);
            foreach($accesses as $access) {
                $user = $access->getUser();
                if ($access->isOwner()) {
                    $owners[] = $serializer->serializeUser($user);
                } elseif ($access->isAdmin()) {
                    $admins[] = $serializer->serializeUser($user);
                } elseif ($access->isEditor()) {
                    $editors[] = $serializer->serializeUser($user);
                }
            }

            if (!$emailUser) {
                return $this->json([
                    'success' => 'Invitation sent!',
                    'editors' => $editors,
                    'admins'  => $admins,
                    'owners'  => $owners
                ]);
            } else {
                return $this->json([
                    'success' => 'User added to your organization.',
                    'editors' => $editors,
                    'admins'  => $admins,
                    'owners'  => $owners
                ]);
            }
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->json('ok');
    }

    /**
     * @IsGranted({"ORG_OWNER"})
     * @Route("/api/v1/account", name="api_v1_account_cancel", methods={"DELETE"})
     *
     * @param int                          $uid
     * @param AuthService                  $authService
     * @param UserRepository               $userRepository
     * @param TemplatesRepository          $templatesRepository
     * @param EmailRepository              $emailRepository
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function cancelAccountAction(
        int $uid,
        AuthService $authService,
        UserRepository $userRepository,
        TemplatesRepository $templatesRepository,
        EmailRepository $emailRepository,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): JsonResponse {
        $newsletters = $templatesRepository->findByUser($uid);
        foreach ($newsletters as $newsletter) {
            $emails = $emailRepository->findByTemplate($newsletter['tmp_id']);
            foreach ($emails as $email) {
                $emailRepository->deleteByID($email['ema_id']);
            }
            $templatesRepository->deleteByID($newsletter['tmp_id']);
        }
        $rbac = $organizationAccessRepository->findFirstByUserAndAccess($uid, 1);
        if (!empty($rbac['rba_org_id'])) {
            $organizationsRepository->deleteOrganization($rbac['rba_org_id']);
        }
        $userRepository->deleteByID($uid);
        $authService->logout();

        return $this->json('ok');
    }
}
