<?php
namespace Controller\Authentication;

use BlocksEdit\Email\EmailSender;
use BlocksEdit\Security\PasswordGeneratorInterface;
use BlocksEdit\Util\Tokens;
use BlocksEdit\Util\TokensTrait;
use Entity\Source;
use BlocksEdit\Http\Annotations\IsGranted;
use BlocksEdit\Http\Annotations\Route;
use BlocksEdit\Controller\Controller;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Integrations\Services\SingleSignOnIntegration;
use Exception;
use Redis;
use Repository\Exception\AuthException;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Service\AuthService;
use Repository\OrganizationsRepository;
use Repository\SourcesRepository;
use Repository\TokensRepository;

/**
 * @IsGranted({"ANY"})
 */
class IndexController extends Controller
{
    use TokensTrait;

    /**
     * @Route("/api/v1/auth/login", name="api_v1_auth_login", methods={"POST"})
     *
     * @param int                          $oid
     * @param Redis                        $redis
     * @param Request                      $request
     * @param AuthService                  $authService
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function authenticateAction(
        int $oid,
        Redis $redis,
        Request $request,
        AuthService $authService,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    ): JsonResponse
    {
        try {
            $email    = $request->json->get('email');
            $password = $request->json->get('password');
            $authService->login(
                $request,
                $email,
                $password,
                $oid
            );
        } catch (AuthException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ]);
        }

        // $request->session->remove('redirectAfter');
        if ($uri = $request->session->get('redirectAfter')) {
            $request->session->remove('redirectAfter');
            return $this->json([
                'redirect' => $uri
            ]);
        } else {
            if ($oid) {
                $org = $organizationsRepository->findByID($oid);
                if (!$org) {
                    $this->throwNotFound();
                }
            } else {
                $uid      = $authService->getLoginId();
                $cacheKey = sprintf('last.organization.%d', $uid);
                $oid      = $redis->get($cacheKey);
                if (!$oid) {
                    $oidRow = $organizationAccessRepository->findFirstByUserAndAccess($uid, 1);
                    if (empty($oidRow)) {
                        $oidRow = $organizationAccessRepository->findFirstByUser($uid);
                        if (empty($oidRow)) {
                            $this->throwBadRequest();
                        }
                    }
                    $oid = $oidRow['rba_org_id'];
                }
            }

            return $this->json([
                'redirect' => $request->getDomainForOrg((int)$oid)
            ]);
        }
    }

    /**
     * @Route("/api/v1/auth/idProviders", name="api_v1_auth_id_providers", methods={"POST"})
     *
     * @param Request           $request
     * @param SourcesRepository $sourcesRepository
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function idProvidersAction(
        Request $request,
        SourcesRepository $sourcesRepository
    ): JsonResponse
    {
        $oid       = $request->json->get('oid');
        $logins    = [];
        $providers = $this->getIdentityProviders($sourcesRepository, 0, $oid);
        foreach($providers as $provider) {
            /** @var SingleSignOnIntegration $it */
            $it = $provider->getIntegration();
            $logins[] = [
                'url'   => $it->getLoginPath($request),
                'label' => $it->getLoginButtonLabel()
            ];
        }

        return $this->json($logins);
    }

    /**
     * @Route("/api/v1/auth/forgotpassword", name="api_v1_auth_forgot_password", methods={"POST"})
     *
     * @param int                          $oid
     * @param Request                      $request
     * @param TokensRepository             $tokensRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     * @param UserRepository               $userRepository
     * @param EmailSender                  $emailSender
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function forgotPasswordAction(
        int $oid,
        Request $request,
        TokensRepository $tokensRepository,
        OrganizationAccessRepository $organizationAccessRepository,
        UserRepository $userRepository,
        EmailSender $emailSender
    ): JsonResponse
    {
        $email = trim($request->json->get('email'));
        if (!$email) {
            $this->throwBadRequest();
        }

        if ($oid) {
            $aid = $organizationAccessRepository->findInitialOwnerID($oid);
            if (!$aid) {
                $this->throwNotFound();
            }

            $found = $userRepository->findAccountUserByEmail($aid, $email);
            if (!$found) {
                return $this->json([
                    'error' => 'Account with given email not found.'
                ]);
            }

            if (empty($found['usr_pass'])) {
                return $this->json([
                    'error' => 'Account does not have a password. Contact your IT admin.'
                ]);
            }

            $uid = $found['usr_id'];
        } else {
            $user = $userRepository->findByEmail($email);
            if (!$user) {
                return $this->json([
                    'error' => 'Account with given email not found.'
                ]);
            }
            $uid = $user['usr_id'];
        }

        $token    = $tokensRepository->addForUser($uid, Tokens::TOKEN_RESET_PASSWORD);
        $urlReset = $this->url('reset_password', [
            'token' => $token
        ]);
        $emailSender->sendPasswordReset($email, $urlReset);

        return $this->json([
            'success' => 'If you setup an account before, you should receive an email to reset your password.'
        ]);
    }

    /**
     * @Route("/api/v1/auth/resetpassword", name="api_v1_auth_reset_password", methods={"POST"})
     *
     * @param Request                    $request
     * @param UserRepository             $userRepository
     * @param TokensRepository           $tokensRepository
     * @param PasswordGeneratorInterface $passwordGenerator
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function resetPasswordAction(
        Request $request,
        UserRepository $userRepository,
        TokensRepository $tokensRepository,
        PasswordGeneratorInterface $passwordGenerator
    ): JsonResponse
    {
        $token    = $request->json->get('token');
        $password = $request->json->get('password');
        if (empty($password)) {
            return $this->json([
                'error' => 'A password is required.'
            ]);
        } elseif (strlen($password) < 6) {
            return $this->json([
                'error' => 'The password must be at least 6 characters long.'
            ]);
        }

        $data = $tokensRepository->findByTokenAndScope($token, Tokens::TOKEN_RESET_PASSWORD);
        if (!$data) {
            return $this->json([
                'error' => 'The password link has expired.'
            ]);
        }
        if (!$this->tokens->verifyToken($data['utk_usr_id'], Tokens::TOKEN_RESET_PASSWORD, $token)) {
            return $this->json([
                'error' => 'The password cannot be reset. Please double check the email address for your account to make sure you entered it correctly.'
            ]);
        }
        $difference = time() - $data['utk_created_at'];
        if ($difference > 1800) {
            return $this->json([
                'error' => 'The password link has expired.'
            ]);
        }

        $password = $passwordGenerator->generate($password);
        $userRepository->updateSingle($data['utk_usr_id'], 'usr_pass', $password);
        $tokensRepository->deleteByUser($data['utk_id'], $token);

        return $this->json('ok');
    }

    /**
     * @Route("/login", name="login")
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function indexAction(Request $request): Response {
        return $this->renderFrontend($request);
    }

    /**
     * @Route("/logout", name="logout")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function logoutAction(Request $request): RedirectResponse
    {
        $request->session->destroy();
        $request->removeCookie(session_name());
        $request->removeCookie('remember');

        return $this->redirectToRoute('index');
    }

    /**
     * @param SourcesRepository $sourcesRepository
     * @param int               $uid
     * @param int               $oid
     *
     * @return array|Source[]
     * @throws Exception
     */
    protected function getIdentityProviders(SourcesRepository $sourcesRepository, int $uid, int $oid): array
    {
        $idProviders = [];
        $sources     = $sourcesRepository->findByIntegrationAndOrg(SingleSignOnIntegration::class, $oid);
        if ($sources) {
            foreach($sources as $source) {
                $integration = $source->getIntegration();
                $integration->setUser($uid, $oid);
                $integration->setSettings($sourcesRepository->findSettings($source));
                $idProviders[] = $source;
            }
        }

        return $idProviders;
    }
}
