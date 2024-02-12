<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "email_history",
 *     prefix="emh_",
 *     repository="Repository\EmailHistoryRepository",
 *     indexes={
 *          "emh_parent_id"={"parentId"}
 *     },
 *     uniqueIndexes={
 *          "email_version_index"={"emaId", "version"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class EmailHistory
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
     * @DB\Column("ema_id")
     * @DB\Sql("BIGINT NOT NULL")
     * @DB\ForeignKey("Entity\Email", references="id")
     */
    protected $emaId = 0;

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
     * @DB\ForeignKey("Entity\EmailHistory", references="id")
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
     * @return EmailHistory
     */
    public function setId(int $id): EmailHistory
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getEmaId(): int
    {
        return $this->emaId;
    }

    /**
     * @param int $emaId
     *
     * @return EmailHistory
     */
    public function setEmaId(int $emaId): EmailHistory
    {
        $this->emaId = $emaId;

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
     * @return EmailHistory
     */
    public function setUsrId(int $usrId): EmailHistory
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
     * @param int|null $parentId
     *
     * @return EmailHistory
     */
    public function setParentId(?int $parentId): EmailHistory
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
     * @return EmailHistory
     */
    public function setVersion(int $version): EmailHistory
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
     * @return EmailHistory
     */
    public function setMessage(string $message): EmailHistory
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
     * @return EmailHistory
     */
    public function setHtml(string $html): EmailHistory
    {
        $this->html = $html;

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
     * @return EmailHistory
     */
    public function setDateCreated(DateTime $dateCreated): EmailHistory
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
