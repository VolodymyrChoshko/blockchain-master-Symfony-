<?php
namespace Middleware;

use BlocksEdit\Http\Middleware;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\System\Serializer;
use BlocksEdit\View\View;
use Exception;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Service\AuthService;
use Repository\OrganizationsRepository;

/**
 * Class AuthMiddleware
 */
class AuthMiddleware extends Middleware
{
    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 1;
    }

    /**
     * @param Request                      $request
     * @param AuthService                  $authService
     * @param Serializer                   $serializer
     * @param UserRepository               $userRepository
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function request(
        Request $request,
        AuthService $authService,
        Serializer $serializer,
        UserRepository $userRepository,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): ?RedirectResponse
    {
        $user         = null;
        $organization = null;
        $isLoggedIn   = $authService->isLogged($request);
        if ($isLoggedIn) {
            $user         = $userRepository->findByID($authService->getLoginId(), true);
            $organization = $organizationsRepository->findByID($request->getOrgSubdomain(), true);
        }

        $grants = $request->session->get('security.grants', []);
        if ($isLoggedIn && $user && $organization) {
            if (!in_array(IsGranted::GRANT_USER, $grants)) {
                $grants[] = IsGranted::GRANT_USER;
            }
            if (!empty($user['usr_is_site_admin']) && !in_array(IsGranted::GRANT_SITE_ADMIN, $grants)) {
                $grants[] = IsGranted::GRANT_SITE_ADMIN;
            }
            $request->session->set('security.grants', $grants);

            $orgs     = [];
            $accesses = $organizationAccessRepository->findByUser($user);
            foreach($accesses as $access) {
                $owner = $organizationAccessRepository->findInitialOwnerID(
                    $access->getOrganization()->getId()
                );
                $orgs[] = [
                    'org_id'   => $access->getOrganization()->getId(),
                    'domain'   => $request->getDomainForOrg($access->getOrganization()->getId()),
                    'is_owner' => ($owner && $user->getId() === $owner)
                ];
            }

            $owners   = [];
            $admins   = [];
            $editors  = [];
            $accesses = $organizationAccessRepository->findByOrganization($organization);
            foreach($accesses as $access) {
                if ($access->getUser()) {
                    if ($access->isOwner()) {
                        $owners[$access->getUser()->getId()] = $access->getUser();
                    } elseif ($access->isAdmin()) {
                        $admins[$access->getUser()->getId()] = $access->getUser();
                    } elseif ($access->isEditor()) {
                        $editors[$access->getUser()->getId()] = $access->getUser();
                    }
                }
            }

            $userState             = $serializer->serializeUser($user);
            $userState['isOwner']  = array_key_exists($user->getId(), $owners);
            $userState['isAdmin']  = array_key_exists($user->getId(), $admins);
            $userState['isEditor'] = array_key_exists($user->getId(), $editors);
            View::setGlobal('user', $user);
            View::setGlobal('userState', $userState);
            View::setGlobal('oid', $request->getOrgSubdomain());
            View::setGlobal('organizations', $orgs);
        } else {
            if (!in_array(IsGranted::GRANT_ANY, $grants)) {
                $grants[] = IsGranted::GRANT_ANY;
            }
            $request->session->set('security.grants', $grants);
        }

        return null;
    }
}
