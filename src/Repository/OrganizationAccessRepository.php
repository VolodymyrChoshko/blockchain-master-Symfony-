<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Database\Where;
use BlocksEdit\System\Required;
use Entity\Organization;
use Entity\OrganizationAccess;
use Entity\User;
use Exception;
use Tag\OrganizationTag;

/**
 * Class OrganizationAccessRepository
 */
class OrganizationAccessRepository extends Repository
{
    /**
     * @var array
     */
    protected $accessCache = [];

    /**
     * @param object|OrganizationAccess $entity
     *
     * @return void
     * @throws Exception
     */
    public function insert(object $entity)
    {
        $existing = $this->findOne([
            'user'         => $entity->getUser(),
            'organization' => $entity->getOrganization()
        ]);
        if ($existing) {
            $existing->setAccess($entity->getAccess());
            $this->update($existing);
        } else {
            parent::insert($entity);
        }
        $this->cache->deleteByTag(new OrganizationTag($entity->getOrganization()->getId()));
    }

    /**
     * @param object|OrganizationAccess $entity
     *
     * @return int
     * @throws Exception
     */
    public function update(object $entity): int
    {
        $rows = parent::update($entity);
        if ($entity->getUser()) {
            unset($this->accessCache[$entity->getOrganization()->getId()][$entity->getUser()->getId()]);
        }

        return $rows;
    }

    /**
     * @param object|OrganizationAccess $entity
     *
     * @return int
     * @throws Exception
     */
    public function delete(object $entity): int
    {
        $rows = parent::delete($entity);
        if ($entity->getUser()) {
            unset($this->accessCache[$entity->getOrganization()->getId()][$entity->getUser()->getId()]);
        }

        return $rows;
    }

    /**
     * @param int $uid
     * @param int $oid
     *
     * @return int
     */
    public function deleteByUser(int $uid, int $oid): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM rba_rbac WHERE rba_usr_id = ? AND rba_org_id = ?");
        $stmt->execute([$uid, $oid]);
        $this->cache->deleteByTag(new OrganizationTag($oid));
        unset($this->accessCache[$oid][$uid]);

        return $stmt->rowCount();
    }

    /**
     * @param int $oid
     *
     * @return int
     */
    public function deleteByOrganization(int $oid): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM rba_rbac WHERE rba_org_id = ?');
        $stmt->execute([$oid]);
        unset($this->accessCache[$oid]);

        return $stmt->rowCount();
    }

    /**
     * @param int $id
     *
     * @return OrganizationAccess|null
     * @throws Exception
     */
    public function findByID(int $id): ?OrganizationAccess
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param Organization $organization
     *
     * @return OrganizationAccess[]
     * @throws Exception
     */
    public function findByOrganization(Organization $organization): array
    {
        return $this->find([
            'organization' => $organization
        ]);
    }

    /**
     * @param User $user
     *
     * @return OrganizationAccess[]
     * @throws Exception
     */
    public function findByUser(User $user): array
    {
        return $this->find([
            'user' => $user
        ]);
    }

    /**
     * @param User         $user
     * @param Organization $organization
     *
     * @return OrganizationAccess[]
     * @throws Exception
     */
    public function findByUserAndOrganization(User $user, Organization $organization): array
    {
        return $this->find([
            'user'         => $user,
            'organization' => $organization
        ]);
    }

    /**
     * @param Organization $org
     *
     * @return OrganizationAccess[]
     * @throws Exception
     */
    public function findOwners(Organization $org): array
    {
        return $this->find([
            'organization' => $org,
            'access'       => OrganizationAccess::OWNER
        ]);
    }

    /**
     * @param Organization $org
     *
     * @return OrganizationAccess[]
     * @throws Exception
     */
    public function findAdmins(Organization $org): array
    {
        return $this->find([
            'organization' => $org,
            'access'       => OrganizationAccess::ADMIN
        ]);
    }

    /**
     * @param int $uid
     * @param int $access
     *
     * @return array
     * @throws Exception
     */
    public function findFirstByUserAndAccess(int $uid, int $access): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rba_rbac WHERE rba_usr_id = ? AND rba_access = ? ORDER BY rba_id ASC LIMIT 1');
        $stmt->execute([$uid, $access]);

        return $this->fetch($stmt);
    }

    /**
     * @param int $uid
     *
     * @return array
     * @throws Exception
     */
    public function findFirstByUser(int $uid): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rba_rbac WHERE rba_usr_id = ? ORDER BY rba_id ASC LIMIT 1');
        $stmt->execute([$uid]);

        return $this->fetch($stmt);
    }

    /**
     * @param int $oid
     *
     * @return int
     * @throws Exception
     */
    public function findInitialOwnerID(int $oid): int
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rba_rbac WHERE rba_org_id = ? AND rba_access = ? ORDER BY rba_id ASC LIMIT 1');
        $stmt->execute([$oid, OrganizationAccess::OWNER]);
        if ($stmt->rowCount() === 0) {
            return 0;
        }

        return (int)$this->fetch($stmt)['rba_usr_id'];
    }

    /**
     * @param int $oid
     *
     * @return array
     * @throws Exception
     */
    public function findInitialOwner(int $oid): array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*
            FROM rba_rbac
            LEFT JOIN usr_users u ON u.usr_id = rba_rbac.rba_usr_id
            WHERE rba_org_id = ?
            AND rba_access = ?
            ORDER BY rba_id ASC
            LIMIT 1"
        );
        $stmt->execute([$oid, OrganizationAccess::OWNER]);
        if (0 === $stmt->rowCount()) {
            return ['usr_id' => 0, 'usr_email' => ''];
        }

        return $this->fetch($stmt);
    }

    /**
     * @param int $uid
     * @param int $oid
     *
     * @return bool
     */
    public function exists(int $uid, int $oid): bool
    {
        $stmt = $this->pdo->prepare('SELECT rba_id FROM rba_rbac WHERE rba_usr_id = ? AND rba_org_id = ?');
        $stmt->execute([$uid, $oid]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param int       $uid
     * @param int       $oid
     * @param int|int[] $accesses
     *
     * @return bool
     * @throws Exception
     */
    public function hasRole(int $uid, int $oid, $accesses): bool
    {
        if (!is_array($accesses)) {
            $accesses = [$accesses];
        }

        foreach($accesses as $access) {
            if ($access === OrganizationAccess::OWNER && $this->isOwner($uid, $oid)) {
                return true;
            }
            if ($access === OrganizationAccess::ADMIN && $this->isAdmin($uid, $oid)) {
                return true;
            }
            if ($access === OrganizationAccess::EDITOR && $this->isEditor($uid, $oid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $uid
     * @param int $oid
     *
     * @return bool
     * @throws Exception
     */
    public function isOwner(int $uid, int $oid): bool
    {
        foreach($this->getUserAccesses($uid, $oid) as $access) {
            if ($access->isOwner()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $uid
     * @param int $oid
     *
     * @return bool
     * @throws Exception
     */
    public function isAdmin(int $uid, int $oid): bool
    {
        foreach($this->getUserAccesses($uid, $oid) as $access) {
            if ($access->isAdmin()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $uid
     * @param int $oid
     *
     * @return bool
     * @throws Exception
     */
    public function isEditor(int $uid, int $oid): bool
    {
        foreach($this->getUserAccesses($uid, $oid) as $access) {
            if ($access->isEditor()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $uid
     * @param int $oid
     *
     * @return OrganizationAccess[]
     * @throws Exception
     */
    protected function getUserAccesses(int $uid, int $oid): array
    {
        if (isset($this->accessCache[$oid][$uid])) {
            return $this->accessCache[$oid][$uid];
        }
        if (!isset($this->accessCache[$oid])) {
            $this->accessCache[$oid] = [];
        }

        $user = $this->userRepository->findByID($uid, true);
        $org  = $this->organizationsRepository->findByID($oid, true);
        if ($user && $org) {
            $this->accessCache[$oid][$uid] = $this->findByUserAndOrganization($user, $org);
        } else {
            $this->accessCache[$oid][$uid] = [];
        }

        return $this->accessCache[$oid][$uid];
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

    /**
     * @var OrganizationsRepository
     */
    protected $organizationsRepository;

    /**
     * @Required()
     * @param OrganizationsRepository $organizationsRepository
     */
    public function setOrganizationsRepository(OrganizationsRepository $organizationsRepository)
    {
    	$this->organizationsRepository = $organizationsRepository;
    }
}
