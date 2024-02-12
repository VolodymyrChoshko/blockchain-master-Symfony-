<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use InvalidArgumentException;

/**
 * @DB\Table(
 *     "rbac",
 *     prefix="rba_",
 *     repository="Repository\OrganizationAccessRepository",
 *     indexes={
 *          "rba_org_id"={"organization"},
 *          "rba_access"={"access"},
 *          "rba_usr_id"={"user"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 * @DB\CacheTag(mergeTags="Entity\User", column="user")
 * @DB\CacheTag(mergeTags="Entity\Organization", column="organization")
 */
class OrganizationAccess
{
    const OWNER  = 1;
    const ADMIN  = 2;
    const EDITOR = 3;

    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("int NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var User|null
     * @DB\Column("usr_id")
     * @DB\Join("Entity\User", references="id")
     * @DB\Sql("int unsigned NOT NULL")
     */
    protected $user;

    /**
     * @var Organization
     * @DB\Column("org_id")
     * @DB\Join("Entity\Organization", references="id")
     * @DB\Sql("int unsigned NOT NULL")
     */
    protected $organization;

    /**
     * @var int
     * @DB\Column("access")
     * @DB\Sql("tinyint DEFAULT NULL")
     */
    protected $access = null;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return OrganizationAccess
     */
    public function setId(int $id): OrganizationAccess
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return OrganizationAccess
     */
    public function setUser(User $user): OrganizationAccess
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     *
     * @return OrganizationAccess
     */
    public function setOrganization(Organization $organization): OrganizationAccess
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return int
     */
    public function getAccess(): ?int
    {
        return $this->access;
    }

    /**
     * @param int|null $access
     *
     * @return OrganizationAccess
     */
    public function setAccess(?int $access): OrganizationAccess
    {
        if (!self::isValidAccess($access)) {
            throw new InvalidArgumentException(
                "Invalid access value $access."
            );
        }

        $this->access = $access;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOwner(): bool
    {
        return $this->access === self::OWNER;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->access === self::ADMIN;
    }

    /**
     * @return bool
     */
    public function isEditor(): bool
    {
        return $this->access === self::EDITOR;
    }

    /**
     * @param int $access
     *
     * @return bool
     */
    public static function isValidAccess(int $access): bool
    {
        return in_array($access, [
            self::ADMIN,
            self::OWNER,
            self::EDITOR
        ]);
    }
}
