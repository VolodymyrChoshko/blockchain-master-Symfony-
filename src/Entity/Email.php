<?php
namespace Entity;

use BlocksEdit\Database\ArrayEntity;
use BlocksEdit\Database\Annotations as DB;

/**
 * @DB\Table(
 *     "emails",
 *     prefix="ema_",
 *     repository="Repository\EmailRepository",
 *     indexes={
 *          "ema_tmp_id"={"template"},
 *          "ema_created_usr_id"={"createdUsrId"},
 *          "ema_updated_usr_id"={"updatedUsrId"},
 *          "ema_tmp_version"={"tmpVersion"},
 *          "ema_folder_id"={"folderId"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 * @DB\CacheTag("Tag\EmailTag")
 * @DB\CacheTag(mergeTags="Entity\Template", column="template")
 */
class Email extends ArrayEntity
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("bigint NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var Template
     * @DB\Column("tmp_id", nullable=true)
     * @DB\Join("Entity\Template", references="id")
     * @DB\Sql("bigint DEFAULT NULL")
     */
    protected $template;

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
     * @DB\Column("created_usr_id")
     * @DB\Sql("int DEFAULT NULL")
     */
    protected $createdUsrId = null;

    /**
     * @var int|null
     * @DB\Column("updated_usr_id")
     * @DB\Sql("int DEFAULT NULL")
     */
    protected $updatedUsrId = 0;

    /**
     * @var string
     * @DB\Column("token")
     * @DB\Sql("varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $token = '';

    /**
     * @var int|null
     * @DB\Column("archived")
     * @DB\Sql("tinyint DEFAULT NULL")
     */
    protected $archived = null;

    /**
     * @var int
     * @DB\Column("tmp_version")
     * @DB\Sql("int NOT NULL DEFAULT '0'")
     */
    protected $tmpVersion = 0;

    /**
     * @var string
     * @DB\Column("tmp_location")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $tmpLocation = '';

    /**
     * @var int|null
     * @DB\Column("folder_id")
     * @DB\Sql("int unsigned DEFAULT NULL")
     * @DB\ForeignKey("Entity\Folder", references="id", onDelete="CASCADE", onUpdate="RESTRICT")
     */
    protected $folderId = null;

    /**
     * @var int
     * @DB\Column("epa_enabled")
     * @DB\Sql("tinyint(1) DEFAULT '0'")
     */
    protected $epaEnabled = 0;

    /**
     * @var int
     * @DB\Column("alias_enabled")
     * @DB\Sql("tinyint(1) DEFAULT '0'")
     */
    protected $aliasEnabled = 0;

    /**
     * @var string
     * @DB\Column("created_at", castTo="int")
     * @DB\Sql("varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $createdAt = '';

    /**
     * @var string
     * @DB\Column("updated_at", castTo="int")
     * @DB\Sql("varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $updatedAt = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('ema_');
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
     * @return Email
     */
    public function setId(int $id): Email
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Template
     */
    public function getTemplate(): Template
    {
        return $this->template;
    }

    /**
     * @param Template $template
     *
     * @return Email
     */
    public function setTemplate(Template $template): Email
    {
        $this->template = $template;

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
     * @return Email
     */
    public function setTitle(string $title): Email
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
     * @return Email
     */
    public function setLocation(string $location): Email
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedUsrId(): int
    {
        return (int)$this->createdUsrId;
    }

    /**
     * @param int $createdUsrId
     *
     * @return Email
     */
    public function setCreatedUsrId(int $createdUsrId): Email
    {
        $this->createdUsrId = $createdUsrId;

        return $this;
    }

    /**
     * @return int
     */
    public function getUpdatedUsrId(): int
    {
        return (int)$this->updatedUsrId;
    }

    /**
     * @param int $updatedUsrId
     *
     * @return Email
     */
    public function setUpdatedUsrId(int $updatedUsrId): Email
    {
        $this->updatedUsrId = $updatedUsrId;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return Email
     */
    public function setToken(string $token): Email
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return bool
     */
    public function getArchived(): bool
    {
        return (bool)$this->archived;
    }

    /**
     * @param bool $archived
     *
     * @return Email
     */
    public function setArchived(bool $archived): Email
    {
        $this->archived = (int)$archived;

        return $this;
    }

    /**
     * @return int
     */
    public function getTmpVersion(): int
    {
        return $this->tmpVersion;
    }

    /**
     * @param int $tmpVersion
     *
     * @return Email
     */
    public function setTmpVersion(int $tmpVersion): Email
    {
        $this->tmpVersion = $tmpVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getTmpLocation(): string
    {
        return $this->tmpLocation;
    }

    /**
     * @param string $tmpLocation
     *
     * @return Email
     */
    public function setTmpLocation(string $tmpLocation): Email
    {
        $this->tmpLocation = $tmpLocation;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFolderId(): ?int
    {
        return $this->folderId;
    }

    /**
     * @param int|null $folderId
     *
     * @return Email
     */
    public function setFolderId(?int $folderId): Email
    {
        $this->folderId = $folderId;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEpaEnabled(): bool
    {
        return (bool)$this->epaEnabled;
    }

    /**
     * @param bool $epaEnabled
     *
     * @return Email
     */
    public function setEpaEnabled(bool $epaEnabled): Email
    {
        $this->epaEnabled = (int)$epaEnabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAliasEnabled(): bool
    {
        return (bool)$this->aliasEnabled;
    }

    /**
     * @param bool $aliasEnabled
     *
     * @return Email
     */
    public function setAliasEnabled(bool $aliasEnabled): Email
    {
        $this->aliasEnabled = (int)$aliasEnabled;

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
     * @return Email
     */
    public function setCreatedAt(int $createdAt): Email
    {
        $this->createdAt = (string)$createdAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getUpdatedAt(): int
    {
        return (int)$this->updatedAt;
    }

    /**
     * @param int $updatedAt
     *
     * @return Email
     */
    public function setUpdatedAt(int $updatedAt): Email
    {
        $this->updatedAt = (string)$updatedAt;

        return $this;
    }
}
