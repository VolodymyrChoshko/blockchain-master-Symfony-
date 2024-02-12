<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Email\EmailSender;
use BlocksEdit\Email\MailerInterface;
use BlocksEdit\Http\RouteGenerator;
use BlocksEdit\Http\RouteGeneratorInterface;
use BlocksEdit\System\Required;
use Entity\Invitation;
use Entity\Organization;
use Entity\OrganizationAccess;
use Entity\User;
use Exception;
use InvalidArgumentException;
use Tag\OrganizationTag;

/**
 * Class OrganizationsRepository
 */
class OrganizationsRepository extends Repository
{
    /**
     * @var array
     */
    protected $cacheArray = [];

    /**
     * @var array
     */
    protected $cacheEntities = [];

    /**
     * @param object|Organization $entity
     *
     * @return int
     * @throws Exception
     */
    public function update(object $entity): int
    {
        $rows = parent::update($entity);
        unset($this->cacheArray[$entity->getId()]);
        unset($this->cacheEntities[$entity->getId()]);

        return $rows;
    }

    /**
     * @param object $entity
     *
     * @return int
     * @throws Exception
     */
    public function delete(object $entity): int
    {
        $rows = parent::delete($entity);
        unset($this->cacheArray[$entity->getId()]);
        unset($this->cacheEntities[$entity->getId()]);

        return $rows;
    }

    /**
     * @param int $oid
     *
     * @return int
     * @throws Exception
     */
    public function deleteOrganization(int $oid): int
    {
        $this->organizationAccessRepository->deleteByOrganization($oid);
        $stmt = $this->pdo->prepare('DELETE FROM org_organization WHERE org_id = ?');
        $stmt->execute([$oid]);

        $this->imagesRepository->deleteByOrg($oid);
        $this->cache->deleteByTag(new OrganizationTag($oid));
        unset($this->cacheArray[$oid]);
        unset($this->cacheEntities[$oid]);

        return $stmt->rowCount();
    }

    /**
     * @param int  $oid
     * @param bool $asEntity
     *
     * @return array|Organization
     * @throws Exception
     */
    public function findByID(int $oid, bool $asEntity = false)
    {
        if ($asEntity) {
            if (!isset($this->cacheEntities[$oid])) {
                $this->cacheEntities[$oid] = $this->findOne([
                    'id' => $oid
                ]);
            }

            return $this->cacheEntities[$oid];
        }

        if (!isset($this->cacheArray[$oid])) {
            $stmt = $this->prepareAndExecute('SELECT * FROM `org_organization` WHERE `org_id` = ? LIMIT 1', [$oid]);
            $this->cacheArray[$oid] = $this->fetch($stmt);
        }

        return $this->cacheArray[$oid];
    }

    /**
     * @param int $uid
     * @param int $access
     *
     * @return array
     */
    public function findByUserAndAccess(int $uid, int $access): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM rba_rbac WHERE rba_usr_id = ? AND rba_access = ?");
        $stmt->execute([$uid, $access]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param int $uid
     *
     * @return array
     */
    public function findByUser(int $uid): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM `rba_rbac`
            LEFT JOIN `org_organization` AS `o` ON `o`.`org_id` = `rba_org_id`
            WHERE rba_usr_id = ?");
        $stmt->execute([$uid]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param string   $name
     * @param int|null $limit
     * @param int      $offset
     *
     * @return array
     * @throws Exception
     */
    public function findByName(string $name, ?int $limit = null, int $offset = 0): array
    {
        if ($limit) {
            $stmt = $this->prepareAndExecute(
                sprintf(
                    "SELECT SQL_CALC_FOUND_ROWS * FROM `org_organization` WHERE `org_name` LIKE ? ORDER BY `org_id` DESC LIMIT %d, %d",
                    $offset,
                    $limit
                ),
                ['%' . $name . '%']
            );
        } else {
            $stmt = $this->prepareAndExecute(
                "SELECT SQL_CALC_FOUND_ROWS * FROM `org_organization` WHERE `org_name` LIKE ? ORDER BY `org_id` DESC",
                ['%' . $name . '%']
            );
        }

        return $this->fetchAll($stmt);
    }

    /**
     * @param int|null $limit
     * @param int      $offset
     *
     * @return array
     * @throws Exception
     */
    public function findAll(?int $limit = null, int $offset = 0): array
    {
        if ($limit !== null) {
            $stmt = $this->prepareAndExecute(
                sprintf("SELECT SQL_CALC_FOUND_ROWS * FROM `org_organization` ORDER BY `org_id` DESC LIMIT %d, %d", $offset, $limit)
            );
        } else {
            $stmt = $this->prepareAndExecute("SELECT SQL_CALC_FOUND_ROWS * FROM `org_organization` ORDER BY `org_id` DESC");
        }

        return $this->fetchAll($stmt);
    }

    /**
     * @return int
     * @throws Exception
     */
    public function countAll(): int
    {
        $stmt = $this->prepareAndExecute('SELECT COUNT(*) FROM `org_organization`');
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * @param int $oid
     *
     * @return array
     */
    public function getOwners(int $oid): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM rba_rbac r
                  LEFT JOIN org_organization o ON(r.rba_org_id = o.org_id)
                  LEFT JOIN usr_users u ON (u.usr_id = r.rba_usr_id)
                  WHERE r.rba_org_id = ?
                  AND r.rba_access = 1"
        );
        $stmt->execute([$oid]);

        return $this->fetchAll($stmt);
    }

    /**
     * @param string $orgName
     * @param string $usrName
     *
     * @return string
     */
    public function prepareName(string $orgName, string $usrName): string
    {
        if (!empty($orgName)) {
            return $orgName;
        }
        if (!empty($usrName)) {
            $usrNameArray = explode(' ',trim($usrName));
            return $usrNameArray[0] . "'s Organization";
        }

        return 'My Organization';
    }

    /**
     * @param int       $uid
     * @param int       $oid
     * @param User|null $user
     * @param int       $access
     * @param string    $fromName
     * @param string    $name
     * @param string    $email
     *
     * @throws Exception
     */
    public function invite(
        int $uid,
        int $oid,
        ?User $user,
        int $access,
        string $fromName = '',
        string $name = '',
        string $email = ''
    )
    {
        if (!OrganizationAccess::isValidAccess($access)) {
            throw new InvalidArgumentException('Invalid access value.');
        }

        $org     = $this->findByID($oid, true);
        $isOwner = $this->organizationAccessRepository->isOwner($uid, $oid);
        $isAdmin = $this->organizationAccessRepository->isAdmin($uid, $oid);
        $accessName = '';
        switch($access) {
            case OrganizationAccess::OWNER:
                if (!$isOwner) {
                    throw new InvalidArgumentException('Must be an owner to complete the request.');
                }
                $accessName = 'owner';
                break;
            case OrganizationAccess::ADMIN:
                if (!$isAdmin && !$isOwner) {
                    throw new InvalidArgumentException('Must be an admin to complete the request.');
                }
                $accessName = 'admin';
                break;
            case OrganizationAccess::EDITOR:
                $accessName = 'editor';
                return;
        }

        if (!$user) {
            if (!$email) {
                throw new Exception('Missing email.');
            }

            $invite = $this->invitationsRepository->findByEmailAndOrganization($email, $oid);
            if ($invite) {
                $this->invitationsRepository->delete($invite);
            }

            $invite = (new Invitation())
                ->setInviterId($uid)
                ->setName($name)
                ->setEmail($email)
                ->setJob('')
                ->setOrg('')
                ->setOrgId($oid)
                ->setIsAccepted(0)
                ->setOrgAccess($access)
                ->setType('organization');
            $this->invitationsRepository->insert($invite);

            $this->inviteGuest(
                $fromName,
                $email,
                $org->getName(),
                $accessName,
                $invite->getToken()
            );
        } else {
            $orgAccess = (new OrganizationAccess())
                ->setUser($user)
                ->setOrganization($org)
                ->setAccess($access);
            $this->organizationAccessRepository->insert($orgAccess);
        }
    }

    /**
     * @param string $from
     * @param string $userEmail
     * @param string $orgName
     * @param string $access
     * @param string $token
     *
     * @throws Exception
     */
    public function inviteGuest(string $from, string $userEmail, string $orgName, string $access, string $token)
    {
        $urlAccept = $this->routeGenerator->generate('invite_organization', [
            'token' => $token
        ], 'absolute');

        $this->emailSender->sendOrganizationInvite(
            $userEmail,
            $from,
            $urlAccept,
            $orgName,
            $access
        );
    }

    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var RouteGeneratorInterface
     */
    protected $routeGenerator;

    /**
     * @var InvitationsRepository
     */
    protected $invitationsRepository;

    /**
     * @var ImagesRepository
     */
    protected $imagesRepository;

    /**
     * @var EmailSender
     */
    protected $emailSender;

    /**
     * @Required()
     * @param InvitationsRepository $invitationsRepository
     */
    public function setInvitationsRepository(InvitationsRepository $invitationsRepository)
    {
        $this->invitationsRepository = $invitationsRepository;
    }

    /**
     * @Required()
     * @param RouteGenerator $routeGenerator
     */
    public function setRouteGenerator(RouteGeneratorInterface $routeGenerator)
    {
        $this->routeGenerator = $routeGenerator;
    }

    /**
     * @Required()
     * @param MailerInterface $mailer
     */
    public function setMailerInterface(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @Required()
     * @param EmailSender $emailSender
     */
    public function setEmailSender(EmailSender $emailSender)
    {
        $this->emailSender = $emailSender;
    }

    /**
     * @Required()
     * @param ImagesRepository $imagesRepository
     */
    public function setImagesRepository(ImagesRepository $imagesRepository)
    {
        $this->imagesRepository = $imagesRepository;
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
}
