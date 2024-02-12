<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "billing_logs",
 *     prefix="bll_",
 *     repository="Repository\BillingLogRepository",
 *     indexes={"bll_org_id"={"orgId"}},
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class BillingLog
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var int
     * @DB\Column("org_id")
     * @DB\Sql("INT(11) NOT NULL")
     * @DB\ForeignKey("Entity\Organization", references="id")
     */
    protected $orgId = 0;

    /**
     * @var string
     * @DB\Column("message")
     * @DB\Sql("VARCHAR(255) NOT NULL")
     */
    protected $message = '';

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
     * @return BillingLog
     */
    public function setId(int $id): BillingLog
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrgId(): int
    {
        return $this->orgId;
    }

    /**
     * @param int $orgId
     *
     * @return BillingLog
     */
    public function setOrgId(int $orgId): BillingLog
    {
        $this->orgId = $orgId;

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
     * @return BillingLog
     */
    public function setMessage(string $message): BillingLog
    {
        $this->message = $message;

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
     * @return BillingLog
     */
    public function setDateCreated(DateTime $dateCreated): BillingLog
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
