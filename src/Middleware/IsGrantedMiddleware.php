<?php
namespace Middleware;

use BlocksEdit\Http\Middleware;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Exception\UnauthorizedException;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Integrations\Services\SingleSignOnIntegration;
use BlocksEdit\Util\Tokens;
use Entity\Organization;
use Entity\User;
use Entity\OrganizationAccess;
use Exception;
use Redis;
use Repository\EmailRepository;
use Repository\OrganizationAccessRepository;
use Repository\SourcesRepository;
use Repository\TemplatesRepository;

/**
 * Class IsGrantedMiddleware
 */
class IsGrantedMiddleware extends Middleware
{
    const AUTH_GRANTS
        = [
            IsGranted::GRANT_USER,
            IsGranted::GRANT_ANY,
            IsGranted::GRANT_SITE_ADMIN
        ];

    /**
     * @var OrganizationAccess[]|null
     */
    protected $orgAccesses = null;

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 2;
    }

    /**
     * @param int               $uid
     * @param int               $oid
     * @param User|null         $user
     * @param Organization|null $organization
     * @param Request           $request
     * @param Tokens            $tokens
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function request(
        int $uid,
        int $oid,
        ?User $user,
        ?Organization $organization,
        Request $request,
        Tokens $tokens
    ): ?RedirectResponse {
        $requiredGrants = $request->route->getGrants();
        if (empty($requiredGrants)) {
            return null;
        }

        // Check for authentication credentials.
        if (!in_array(IsGranted::GRANT_ANY, $requiredGrants)) {
            $securityGrants = $request->session->get('security.grants', []);
            $matched        = false;
            foreach ($requiredGrants as $requiredGrant) {
                if (!is_array($requiredGrant) && in_array($requiredGrant, $securityGrants)) {
                    if ($requiredGrant === IsGranted::GRANT_SITE_ADMIN_2FA) {
                        $a2fa = $request->session->get('2fa');
                        if (!$a2fa) {
                            continue;
                        }
                        $key  = sprintf('2fa:%s', $a2fa);
                        $ruid = (int)$this->container->get(Redis::class)->get($key);
                        if (!$ruid || $ruid !== $uid) {
                            continue;
                        }
                    }

                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $uri = $request->getUri();
                if ($uri !== '/') {
                    $request->session->set('redirectAfter', $uri);
                }
                if (in_array(IsGranted::GRANT_SITE_ADMIN_2FA, $requiredGrants)) {
                    return $this->redirectToRoute('admin_auth_login');
                }

                if ($this->hasIdentityProviders($oid)) {
                    return $this->redirect(
                        $this->url('login', [], [], $oid)
                    );
                }

                return $this->redirect($this->url('login'));
            }
        }

        // Check for access to templates, emails, etc.
        foreach ($requiredGrants as $requiredGrant) {
            if (is_array($requiredGrant)) {
                $kinds     = $requiredGrant[0];
                $param     = $requiredGrant[1];
                $token     = $requiredGrant[2];
                $hasAccess = false;

                foreach ($kinds as $kind) {
                    if (!in_array($kind, self::AUTH_GRANTS)) {
                        switch ($kind) {
                            case IsGranted::KIND_TEMPLATE:
                                $hasAccess = $this->container->get(TemplatesRepository::class)
                                    ->hasAccess($uid, $request->route->params->get($param));
                                break;
                            case IsGranted::KIND_EMAIL:
                                $hasAccess = $this->container->get(EmailRepository::class)
                                    ->hasAccess($uid, $request->route->params->get($param));
                                break;
                            case IsGranted::KIND_PREVIEW:
                                $token       = str_replace('/', '', $request->route->params->get($token));
                                $verifyToken = $tokens->generateToken(
                                    $request->route->params->get($param),
                                    'original_public_url'
                                );
                                $hasAccess = ($token === $verifyToken);
                                break;
                            case IsGranted::GRANT_ORG_OWNER:
                                $hasAccess = $this->isOrgOwner($user, $organization);
                                break;
                            case IsGranted::GRANT_ORG_ADMIN:
                                $hasAccess = $this->isOrgAdmin($user, $organization);
                                break;
                            case IsGranted::GRANT_ORG_EDITOR:
                                $hasAccess = $this->isOrgEditor($user, $organization);
                                break;
                        }
                    }
                }

                if (!$hasAccess) {
                    throw new UnauthorizedException();
                }
            }
        }

        return null;
    }

    /**
     * @param int $oid
     *
     * @return bool
     * @throws Exception
     */
    protected function hasIdentityProviders(int $oid): bool
    {
        $sources = $this->container->get(SourcesRepository::class)
            ->findByIntegrationAndOrg(SingleSignOnIntegration::class, $oid);

        return count($sources) > 0;
    }

    /**
     * @param User|null         $user
     * @param Organization|null $organization
     *
     * @return bool
     * @throws Exception
     */
    protected function isOrgOwner(?User $user, ?Organization $organization): bool
    {
        foreach($this->getOrgAccesses($user, $organization) as $orgAccess) {
            if ($orgAccess->isOwner()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param User|null         $user
     * @param Organization|null $organization
     *
     * @return bool
     * @throws Exception
     */
    protected function isOrgAdmin(?User $user, ?Organization $organization): bool
    {
        foreach($this->getOrgAccesses($user, $organization) as $orgAccess) {
            if ($orgAccess->isOwner() || $orgAccess->isAdmin()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param User|null         $user
     * @param Organization|null $organization
     *
     * @return bool
     * @throws Exception
     */
    protected function isOrgEditor(?User $user, ?Organization $organization): bool
    {
        foreach($this->getOrgAccesses($user, $organization) as $orgAccess) {
            if ($orgAccess->isOwner() || $orgAccess->isAdmin() || $orgAccess->isEditor()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param User|null         $user
     * @param Organization|null $organization
     *
     * @return OrganizationAccess[]
     * @throws Exception
     */
    protected function getOrgAccesses(?User $user, ?Organization $organization): array
    {
        if (!$user || !$organization) {
            return [];
        }
        if ($this->orgAccesses === null) {
            $this->orgAccesses = $this->container->get(OrganizationAccessRepository::class)
                ->findByUserAndOrganization($user, $organization);
        }

        return $this->orgAccesses;
    }
}
