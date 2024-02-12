<?php
namespace Entity;

use BlocksEdit\Database\ArrayEntity;
use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "templates",
 *     prefix="tmp_",
 *     repository="Repository\TemplatesRepository",
 *     indexes={
 *          "tmp_usr_id"={"user"},
 *          "tmp_org_id"={"organization"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 * @DB\CacheTag("Tag\TemplateTag")
 * @DB\CacheTag("Tag\OrganizationTag", column="organization")
 */
class Template extends ArrayEntity
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("bigint NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var User
     * @DB\Column("usr_id")
     * @DB\Join("Entity\User", references="id")
     * @DB\Sql("bigint NOT NULL AUTO_INCREMENT")
     */
    protected $user;

    /**
     * @var Organization
     * @DB\Column("org_id")
     * @DB\Join("Entity\Organization", references="id")
     * @DB\Sql("int unsigned NOT NULL DEFAULT '0'")
     */
    protected $organization;

    /**
     * @var string
     * @DB\Column("title")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $title = '';

    /**
     * @var string
     * @DB\Column("location")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $location = '';

    /**
     * @var int|null
     * @DB\Column("parent")
     * @DB\Sql("bigint DEFAULT NULL")
     */
    protected $parent = null;

    /**
     * @var string
     * @DB\Column("location")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $imgBaseUrl = '';

    /**
     * @var int
     * @DB\Column("is_temp")
     * @DB\Sql("tinyint(1) DEFAULT '0'")
     */
    protected $isTemp = 0;

    /**
     * @var int
     * @DB\Column("version")
     * @DB\Sql("tinyint DEFAULT '1'")
     */
    protected $version = 1;

    /**
     * @var int
     * @DB\Column("tpa_enabled")
     * @DB\Sql("tinyint(1) DEFAULT '0'")
     */
    protected $tpaEnabled = 0;

    /**
     * @var int
     * @DB\Column("tmh_enabled")
     * @DB\Sql("tinyint(1) DEFAULT '0'")
     */
    protected $tmhEnabled = 0;

    /**
     * @var int
     * @DB\Column("alias_enabled")
     * @DB\Sql("tinyint(1) DEFAULT '0'")
     */
    protected $aliasEnabled = 0;

    /**
     * @var array
     * @DB\Column("checklist_settings", nullable=false, json=true)
     * @DB\Sql("VARCHAR(2000) NOT NULL DEFAULT '[]'")
     */
    protected $checklistSettings = [];

    /**
     * @var string
     * @DB\Column("created_at", castTo="int")
     * @DB\Sql("varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $createdAt = '';

    /**
     * @var DateTime|null
     * @DB\Column("updated_at")
     * @DB\Sql("datetime DEFAULT NULL")
     */
    protected $updatedAt = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('tmp_');
    }

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
     * @return Template
     */
    public function setId(int $id): Template
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Template
     */
    public function setUser(User $user): Template
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
     * @return Template
     */
    public function setOrganization(Organization $organization): Template
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Template
     */
    public function setTitle(string $title): Template
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     *
     * @return Template
     */
    public function setLocation(string $location): Template
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getParent(): ?int
    {
        return $this->parent;
    }

    /**
     * @param int|null $parent
     *
     * @return Template
     */
    public function setParent(?int $parent): Template
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return string
     */
    public function getImgBaseUrl(): string
    {
        return $this->imgBaseUrl;
    }

    /**
     * @param string $imgBaseUrl
     *
     * @return Template
     */
    public function setImgBaseUrl(string $imgBaseUrl): Template
    {
        $this->imgBaseUrl = $imgBaseUrl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTemp(): bool
    {
        return (bool)$this->isTemp;
    }

    /**
     * @param bool $isTemp
     *
     * @return Template
     */
    public function setIsTemp(bool $isTemp): Template
    {
        $this->isTemp = (int)$isTemp;

        return $this;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return Template
     */
    public function setVersion(int $version): Template
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTpaEnabled(): bool
    {
        return (bool)$this->tpaEnabled;
    }

    /**
     * @param bool $tpaEnabled
     *
     * @return Template
     */
    public function setTpaEnabled(bool $tpaEnabled): Template
    {
        $this->tpaEnabled = (int)$tpaEnabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTmhEnabled(): bool
    {
        return (bool)$this->tmhEnabled;
    }

    /**
     * @param bool $tmhEnabled
     *
     * @return Template
     */
    public function setTmhEnabled(bool $tmhEnabled): Template
    {
        $this->tmhEnabled = (int)$tmhEnabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAliasEnabled(): bool
    {
        return (bool)$this->aliasEnabled;
    }

    /**
     * @param bool $aliasEnabled
     *
     * @return Template
     */
    public function setAliasEnabled(bool $aliasEnabled): Template
    {
        $this->aliasEnabled = (int)$aliasEnabled;

        return $this;
    }

    /**
     * @return array
     */
    public function getChecklistSettings(): array
    {
        return $this->checklistSettings;
    }

    /**
     * @param array $checklistSettings
     *
     * @return Template
     */
    public function setChecklistSettings(array $checklistSettings): Template
    {
        $this->checklistSettings = $checklistSettings;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedAt(): int
    {
        return (int)$this->createdAt;
    }

    /**
     * @param int $createdAt
     *
     * @return Template
     */
    public function setCreatedAt(int $createdAt): Template
    {
        $this->createdAt = (string)$createdAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime|null $updatedAt
     *
     * @return Template
     */
    public function setUpdatedAt(?DateTime $updatedAt): Template
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
