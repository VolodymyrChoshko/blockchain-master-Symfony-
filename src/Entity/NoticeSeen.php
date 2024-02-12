<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "notice_seen",
 *     prefix="nts_",
 *     repository="Repository\NoticeSeenRepository",
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class NoticeSeen
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("nts_id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var int
     * @DB\Column("nts_ntc_id")
     * @DB\Sql("BIGINT(12) NOT NULL")
     */
    protected $ntcId;

    /**
     * @var int
     * @DB\Column("nts_usr_id")
     * @DB\Sql("INT NOT NULL")
     */
    protected $usrId;

    /**
     * @var bool
     * @DB\Column("nts_is_closed")
     * @DB\Sql("TINYINT(1) NOT NULL")
     */
    protected $isClosed = false;

    /**
     * @var DateTime
     * @DB\Column("nts_date_created")
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
     * @return NoticeSeen
     */
    public function setId(int $id): NoticeSeen
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getNtcId(): int
    {
        return $this->ntcId;
    }

    /**
     * @param int $ntcId
     *
     * @return NoticeSeen
     */
    public function setNtcId(int $ntcId): NoticeSeen
    {
        $this->ntcId = $ntcId;

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
     * @return NoticeSeen
     */
    public function setUsrId(int $usrId): NoticeSeen
    {
        $this->usrId = $usrId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    /**
     * @param bool $isClosed
     *
     * @return NoticeSeen
     */
    public function setIsClosed(bool $isClosed): NoticeSeen
    {
        $this->isClosed = $isClosed;

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
     * @return NoticeSeen
     */
    public function setDateCreated(DateTime $dateCreated): NoticeSeen
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
