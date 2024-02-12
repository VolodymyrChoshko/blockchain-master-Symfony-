<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Email\MailerInterface;
use BlocksEdit\Security\PasswordGeneratorInterface;
use BlocksEdit\Util\Strings;
use Entity\Organization;
use Entity\OrganizationAccess;
use Service\AuthService;
use Tag\UserTag;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\System\Required;
use DateTime;
use Exception;
use Repository\Exception\CreateException;
use Repository\Exception\UpdateException;
use Repository\Exception\ChangePasswordException;
use Entity\User;

/**
 * Class UserRepository
 */
class UserRepository extends Repository
{
    /**
     * @var array
     */
    protected $users = [];

    /**
     * @var User[]
     */
    protected $userEntities = [];

    /**
     * @param object|User $entity
     *
     * @return void
     * @throws CreateException
     * @throws Exception
     */
    public function insert(object $entity)
    {
        if (!$entity->getName()) {
            throw new CreateException('Include your name.');
        }
        if (!$entity->getEmail()) {
            throw new CreateException('An Email address is required.');
        } elseif (!filter_var($entity->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new CreateException('The Email address is invalid.');
        }
        if (!$entity->getPassPlain()) {
            throw new CreateException('A password is required.');
        } elseif (strlen($entity->getPassPlain()) < 6) {
            throw new CreateException('The password must be at least 6 characters long.');
        }
        if ($this->findByEmail($entity->getEmail())) {
            throw new CreateException('There is already an account with that Email address.');
        }

        if ($entity->isNewsletter()) {
            $body = '<p>Hi Ovi,</p><br>';
            $body .= '<p>User ' . $entity->getEmail() . ' signed up for newsletter</p><br>';
            $body .= '<p>Thank you</p>';

            $this->mailer->quickSend('ovi@blocksedit.com', 'Blocks Edit newsletter signup', $body);
        }

        $entity->setPass($this->passwordGenerator->generate($entity->getPassPlain()));
        $entity->setOrganization(
            $this->organizationsRepository
                ->prepareName($entity->getOrganization(), $entity->getName())
        );

        try {
            $this->beginTransaction();

            $entity->setCreatedAt(time());
            $entity->setDatePrevLogin(new DateTime());
            $entity->setDateLastLogin(new DateTime());
            parent::insert($entity);
            $org = (new Organization())
                ->setName($entity->getOrganization())
                ->setToken(Strings::random(32));
            $this->organizationsRepository->insert($org);
            $orgAccess = (new OrganizationAccess())
                ->setUser($entity)
                ->setOrganization($org)
                ->setAccess(OrganizationAccess::OWNER);
            $this->organizationAccessRepository->insert($orgAccess);

            $this->commit();

            $info = $this->findByID($entity->getId());
            $this->authService->setUserSession($info);
            $this->templatesRepository->cloneStarter($entity->getId(), $org->getId());
        } catch (Exception $e) {
            $this->rollback();
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param object|User $entity
     *
     * @return int
     * @throws UpdateException
     * @throws Exception
     */
    public function update(object $entity): int
    {
        if (empty($entity->getName())) {
            throw new UpdateException('A name needs to be included.');
        }
        if (empty($entity->getEmail())) {
            throw new UpdateException('An email address needs to be used.');
        } elseif (!filter_var($entity->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new UpdateException('The Email address is not valid.');
        }

        if ($entity->getParent()) {
            $found = $this->findAccountUserByEmail($entity->getParent()->getId(), $entity->getEmail());
            if ($found && ((int)$found['usr_id']) !== $entity->getId()) {
                throw new UpdateException('There is another account using that Email address!');
            }
        } else {
            $found = $this->findByEmail($entity->getEmail(), true);
            if ($found && $found->getId() !== $entity->getId()) {
                throw new UpdateException('There is another account using that Email address!');
            }
        }

        return parent::update($entity);
    }

    /**
     * @param int    $aid
     * @param int    $oid
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     *
     * @return array
     * @throws Exception
     */
    public function provisionUser(int $aid, int $oid, string $email, string $firstName, string $lastName): array
    {
        $sql = "
            INSERT INTO
            usr_users (usr_parent_id, usr_pass, usr_email, usr_name, usr_job, usr_organization, usr_created_at, usr_newsletter)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $this->prepareAndExecute($sql, [
            $aid,
            '',
            $email,
            $firstName . ' ' . $lastName,
            '',
            '',
            time(),
            0
        ]);
        if (!$stmt->rowCount()) {
            throw new Exception($stmt->errorInfo()[0]);
        }

        $uid  = $this->getLastInsertID();
        $user = $this->findByID($uid, true);
        $org  = $this->organizationsRepository->findByID($oid, true);
        $orgAccess = (new OrganizationAccess())
            ->setUser($user)
            ->setOrganization($org)
            ->setAccess(OrganizationAccess::EDITOR);
        $this->organizationAccessRepository->insert($orgAccess);

        return $this->findByID($uid);
    }

    /**
     * @param int  $uid
     * @param bool $asEntity
     *
     * @return array|User|null
     * @throws Exception
     */
    public function findByID(int $uid, bool $asEntity = false)
    {
        if ($asEntity) {
            if (isset($this->userEntities[$uid])) {
                return $this->userEntities[$uid];
            }
            $this->userEntities[$uid] = $this->findOne([
                'id' => $uid
            ]);

            return $this->userEntities[$uid];
        }

        if (isset($this->users[$uid])) {
            return $this->users[$uid];
        }

        $stmt = $this->pdo->prepare('SELECT * FROM usr_users WHERE usr_id = ? LIMIT 1');
        $stmt->execute([$uid]);
        $this->users[$uid] = $this->fetch($stmt);

        return $this->users[$uid];
    }

    /**
     * @return int
     * @throws Exception
     */
    public function countAll(): int
    {
        $stmt = $this->prepareAndExecute('SELECT COUNT(*) FROM `usr_users`');
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function findBySiteAdmin(): array
    {
        $stmt = $this->prepareAndExecute('SELECT * FROM `usr_users` WHERE `usr_is_site_admin` = 1');

        return $this->fetchAll($stmt);
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return array
     * @throws Exception
     */
    public function findAll(?int $limit = null, ?int $offset = 0): array
    {
        if ($limit !== null) {
            $stmt = $this->prepareAndExecute(
                sprintf('SELECT SQL_CALC_FOUND_ROWS * FROM `usr_users` ORDER BY `usr_id` DESC LIMIT %d, %d', $offset, $limit)
            );
        } else {
            $stmt = $this->prepareAndExecute('SELECT SQL_CALC_FOUND_ROWS * FROM `usr_users` ORDER BY `usr_id` DESC');
        }

        return $this->fetchAll($stmt);
    }

    /**
     * @param DateTime $date
     *
     * @return array
     * @throws Exception
     */
    public function findSince(DateTime $date): array
    {
        $stmt = $this->prepareAndExecute(
            'SELECT * FROM `usr_users` WHERE `usr_created_at` > ?',
            [$date->getTimestamp()]
        );

        return $this->fetchAll($stmt);
    }

    /**
     * @param string $email
     * @param bool   $asEntity
     *
     * @return array|User
     * @throws Exception
     */
    public function findByEmail(string $email, bool $asEntity = false)
    {
        if ($asEntity) {
            return $this->findOne([
                'email' => $email
            ]);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM usr_users WHERE usr_email = ? LIMIT 1");
        $stmt->execute([$email]);

        return $this->fetch($stmt);
    }

    /**
     * @param string $email
     * @param int    $limit
     *
     * @return array
     * @throws Exception
     */
    public function findByMatchingEmail(string $email, int $limit = 5): array
    {
        $stmt = $this->prepareAndExecute(
            sprintf('SELECT `usr_email`, `usr_name` FROM `usr_users` WHERE `usr_email` LIKE ? LIMIT %d', $limit),
            ['%' . $email . '%']
        );

        return $this->fetchAll($stmt);
    }

    /**
     * @param int    $aid
     * @param string $email
     *
     * @return array
     * @throws Exception
     */
    public function findAccountUserByEmail(int $aid, string $email): array
    {
        $sql = "
            SELECT `b`.*
            FROM `usr_users` AS `a`
            LEFT JOIN `usr_users` AS `b` ON `b`.`usr_parent_id` = `a`.`usr_id`
            WHERE (`a`.`usr_id` = ? AND `b`.`usr_email` = ?)
            LIMIT 1
        ";
        $stmt = $this->prepareAndExecute($sql, [$aid, $email]);
        $row  = $this->fetch($stmt);
        if ($row) {
            return $row;
        }

        $sql = "
            SELECT *
            FROM `usr_users` AS `a`
            WHERE (`a`.`usr_id` = ? AND `a`.`usr_email` = ? AND `a`.`usr_parent_id` IS NULL)
            LIMIT 1
        ";
        $stmt = $this->prepareAndExecute($sql, [$aid, $email]);

        return $this->fetch($stmt);
    }

    /**
     * @param DateTime $date
     *
     * @return array
     */
    public function findByJoinDate(DateTime $date): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usr_users WHERE DATE(FROM_UNIXTIME(usr_created_at)) = ?');
        $stmt->execute([$date->format('Y-m-d')]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param User $user
     *
     * @return User[]
     * @throws Exception
     */
    public function findAccountUsers(User $user): array
    {
        return $this->find([
            'parent' => $user
        ]);
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws Exception
     */
    public function findByName(string $name): array
    {
        $stmt = $this->prepareAndExecute(
            'SELECT SQL_CALC_FOUND_ROWS * FROM `usr_users` WHERE `usr_name` LIKE ?',
            ['%' . $name . '%']
        );

        return $this->fetchAll($stmt);
    }

    /**
     * @return int
     * @throws Exception
     */
    public function findFoundRows(): int
    {
        $stmt = $this->prepareAndExecute('SELECT FOUND_ROWS()');
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * @param int    $uid
     * @param string $timezone
     *
     * @return int
     */
    public function updateTimezone(int $uid, string $timezone): int
    {
        $query = $this->pdo->prepare(
            'UPDATE usr_users SET usr_timezone = ? WHERE usr_id = ? LIMIT 1'
        );
        $query->execute([$timezone, $uid]);
        $this->cache->deleteByTag(new UserTag($uid));

        return $query->rowCount();
    }

    /**
     * @param int    $id
     * @param string $when
     *
     * @return int
     * @throws Exception
     */
    public function updateDatePrevLogin(int $id, string $when = ''): int
    {
        if ($when === 'now') {
            $stmt = $this->prepareAndExecute(
                "UPDATE usr_users SET usr_date_prev_login = NOW() WHERE usr_id = ?",
                [$id]
            );
        } else {
            $stmt = $this->prepareAndExecute(
                "UPDATE usr_users SET usr_date_prev_login = usr_date_last_login WHERE usr_id = ?",
                [$id]
            );
        }

        return $stmt->rowCount();
    }

    /**
     * @param int $id
     *
     * @return int
     * @throws Exception
     */
    public function updateDateLastLogin(int $id): int
    {
        $stmt = $this->prepareAndExecute(
            "UPDATE usr_users SET usr_date_last_login = NOW() WHERE usr_id = ?",
            [$id]
        );

        return $stmt->rowCount();
    }

    /**
     * @param int $uid
     * @param string $password
     *
     * @return bool
     * @throws ChangePasswordException
     */
    public function changePassword(int $uid, string $password): bool
    {
        if (empty($password)) {
            throw new ChangePasswordException('You need to enter a password.');
        } elseif (strlen($password) < 6) {
            throw new ChangePasswordException('The password needs to be at least 6 characters long.');
        }

        $password = $this->passwordGenerator->generate($password);
        $this->updateSingle($uid, 'usr_pass', $password);

        return true;
    }

    /**
     * @param int    $uid
     * @param string $column
     * @param string $value
     */
    public function updateSingle(int $uid, string $column, string $value)
    {
        $stmt = $this->pdo->prepare("UPDATE usr_users SET $column = ? WHERE usr_id = ?");
        $stmt->execute([$value, $uid]);
        $this->cache->deleteByTag(new UserTag($uid));
    }

    /**
     * @param int $tid
     *
     * @return array
     */
    public function getAuthor(int $tid): array
    {
        $stmt  = $this->pdo->prepare(
            'SELECT u.* FROM tmp_templates t LEFT JOIN usr_users u ON(t.tmp_usr_id = u.usr_id) WHERE t.tmp_id = ?'
        );
        $stmt->execute([$tid]);

        return $this->fetch($stmt);
    }

    /**
     * @param int $uid
     *
     * @return int
     */
    public function deleteByID(int $uid): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM acc_access WHERE acc_usr_id = ?');
        $stmt->execute([$uid]);
        $stmt = $this->pdo->prepare('DELETE FROM usr_users WHERE usr_id = ?');
        $stmt->execute([$uid]);
        $this->cache->deleteByTag(new UserTag($uid));

        return $stmt->rowCount();
    }

    /**
     * @param User $user
     * @param int  $size
     *
     * @return string
     * @throws Exception
     */
    public function getAnyAvatar(User $user, int $size): string
    {
        switch($size) {
            case 60:
                $avatar = $user->getAvatar60();
                break;
            case 120:
                $avatar = $user->getAvatar120();
                break;
            case 240:
                $avatar = $user->getAvatar240();
                break;
            default:
                throw new Exception("Invalid avatar size $size. Must be one of 60, 120, or 240.");
        }

        if (!$avatar) {
            $parts = pathinfo($user->getAvatar());
            if (empty($parts['extension'])) {
                $parts['extension'] = 'jpg';
            }
            $avatar = $this->paths->urlAvatar($parts['filename'] . '-60x60.' . $parts['extension']);
        }

        return $avatar;
    }

    /**
     * @var CDNInterface
     */
    protected $cdn;

    /**
     * @var OrganizationsRepository
     */
    protected $organizationsRepository;

    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @var EmailRepository
     */
    protected $emailRepository;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * @Required()
     * @param AuthService $authService
     */
    public function setAuthService(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @Required()
     * @param EmailRepository $emailRepository
     */
    public function setEmailRepository(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    /**
     * @Required()
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templatesRepository = $templatesRepository;
    }

    /**
     * @Required()
     * @param MailerInterface $mailer
     */
    public function setMailer(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
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
     * @Required()
     * @param CDNInterface $cdn
     */
    public function setCDN(CDNInterface $cdn)
    {
        $this->cdn = $cdn;
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
