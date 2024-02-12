<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "template_history",
 *     prefix="tmh_",
 *     repository="Repository\TemplateHistoryRepository",
 *     uniqueIndexes={
 *          "template_version_index"={"tmpId", "version"}
 *     },
 *     indexes={"tmh_parent_id"={"parentId"}},
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class TemplateHistory
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(12) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var int
     * @DB\Column("tmp_id")
     * @DB\Sql("BIGINT NOT NULL")
     * @DB\ForeignKey("Entity\Template", references="id", onDelete="CASCADE")
     */
    protected $tmpId = 0;

    /**
     * @var int
     * @DB\Column("usr_id")
     * @DB\Sql("INT NOT NULL")
     */
    protected $usrId = 0;

    /**
     * @var int
     * @DB\Column("parent_id")
     * @DB\Sql("INT(12) DEFAULT NULL")
     * @DB\ForeignKey("Entity\TemplateHistory", references="id", onDelete="CASCADE")
     */
    protected $parentId = null;

    /**
     * @var int
     * @DB\Column("version")
     * @DB\Sql("SMALLINT(2) UNSIGNED NOT NULL")
     */
    protected $version;

    /**
     * @var string
     * @DB\Column("message")
     * @DB\Sql("VARCHAR(120) NOT NULL DEFAULT ''")
     */
    protected $message = '';

    /**
     * @var string
     * @DB\Column("html")
     * @DB\Sql("MEDIUMTEXT NOT NULL")
     */
    protected $html = '';

    /**
     * @var string
     * @DB\Column("thumb_normal")
     */
    protected $thumbNormal = '';

    /**
     * @var string
     * @DB\Column("thumb_mobile")
     */
    protected $thumbMobile = '';

    /**
     * @var string
     * @DB\Column("thumb_200")
     */
    protected $thumb200 = '';

    /**
     * @var string
     * @DB\Column("thumb_360")
     */
    protected $thumb360 = '';

    /**
     * @var DateTime
     * @DB\Column("date_created")
     * @DB\Sql("DATETIME NOT NULL")
     */
    protected $dateCreated;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
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
     * @return TemplateHistory
     */
    public function setId(int $id): TemplateHistory
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getTmpId(): int
    {
        return $this->tmpId;
    }

    /**
     * @param int $tmpId
     *
     * @return TemplateHistory
     */
    public function setTmpId(int $tmpId): TemplateHistory
    {
        $this->tmpId = $tmpId;

        return $this;
    }

    /**
     * @return int
     */
    public function getUsrId(): int
    {
        return $this->usrId;
    }

    /**
     * @param int $usrId
     *
     * @return TemplateHistory
     */
    public function setUsrId(int $usrId): TemplateHistory
    {
        $this->usrId = $usrId;

        return $this;
    }

    /**
     * @return int
     */
    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    /**
     * @param int $parentId
     *
     * @return TemplateHistory
     */
    public function setParentId(?int $parentId): TemplateHistory
    {
        $this->parentId = $parentId;

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
     * @return TemplateHistory
     */
    public function setVersion(int $version): TemplateHistory
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return TemplateHistory
     */
    public function setMessage(string $message): TemplateHistory
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * @param string $html
     *
     * @return TemplateHistory
     */
    public function setHtml(string $html): TemplateHistory
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumbNormal(): string
    {
        return $this->thumbNormal;
    }

    /**
     * @param string $thumbNormal
     *
     * @return TemplateHistory
     */
    public function setThumbNormal(string $thumbNormal): TemplateHistory
    {
        $this->thumbNormal = $thumbNormal;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumbMobile(): string
    {
        return $this->thumbMobile;
    }

    /**
     * @param string $thumbMobile
     *
     * @return TemplateHistory
     */
    public function setThumbMobile(string $thumbMobile): TemplateHistory
    {
        $this->thumbMobile = $thumbMobile;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumb200(): string
    {
        return $this->thumb200;
    }

    /**
     * @param string $thumb200
     *
     * @return TemplateHistory
     */
    public function setThumb200(string $thumb200): TemplateHistory
    {
        $this->thumb200 = $thumb200;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumb360(): string
    {
        return $this->thumb360;
    }

    /**
     * @param string $thumb360
     *
     * @return TemplateHistory
     */
    public function setThumb360(string $thumb360): TemplateHistory
    {
        $this->thumb360 = $thumb360;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param DateTime $dateCreated
     *
     * @return TemplateHistory
     */
    public function setDateCreated(DateTime $dateCreated): TemplateHistory
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
