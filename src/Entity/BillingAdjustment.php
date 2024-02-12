<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;
use InvalidArgumentException;

/**
 * @DB\Table(
 *     "billing_adjustment",
 *     prefix="badj_",
 *     repository="Repository\BillingAdjustmentRepository",
 *     indexes={
 *          "badj_org_id"={"orgId"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class BillingAdjustment
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPLIED_FULLY = 'applied_fully';
    const STATUS_APPLIED_PARTIALLY = 'applied_partially';

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
     * @DB\ForeignKey(
     *     "Entity\Organization",
     *     references="id",
     *     onDelete="RESTRICT",
     *     onUpdate="RESTRICT"
     * )
     */
    protected $orgId = 0;

    /**
     * @var string
     * @DB\Column("description")
     * @DB\Sql("VARCHAR(500) NOT NULL")
     */
    protected $description = '';

    /**
     * @var string
     * @DB\Column("reason")
     * @DB\Sql("VARCHAR(500) NOT NULL")
     */
    protected $reason = '';

    /**
     * @var int
     * @DB\Column("amount_cents")
     * @DB\Sql("INT(11) NOT NULL")
     */
    protected $amountCents = 0;

    /**
     * @var int
     * @DB\Column("remaining_cents")
     * @DB\Sql("INT(11) NOT NULL")
     */
    protected $remainingCents = 0;

    /**
     * @var string
     * @DB\Column("status")
     * @DB\Sql("ENUM('pending', 'applied_fully', 'applied_partially') DEFAULT 'pending'")
     */
    protected $status = 'pending';

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
     * @return BillingAdjustment
     */
    public function setId(int $id): BillingAdjustment
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
     * @return BillingAdjustment
     */
    public function setOrgId(int $orgId): BillingAdjustment
    {
        $this->orgId = $orgId;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return BillingAdjustment
     */
    public function setDescription(string $description): BillingAdjustment
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     *
     * @return BillingAdjustment
     */
    public function setReason(string $reason): BillingAdjustment
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return int
     */
    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    /**
     * @param int $amountCents
     *
     * @return BillingAdjustment
     */
    public function setAmountCents(int $amountCents): BillingAdjustment
    {
        $this->amountCents = $amountCents;

        return $this;
    }

    /**
     * @return int
     */
    public function getRemainingCents(): int
    {
        return $this->remainingCents;
    }

    /**
     * @param int $remainingCents
     *
     * @return BillingAdjustment
     */
    public function setRemainingCents(int $remainingCents): BillingAdjustment
    {
        $this->remainingCents = $remainingCents;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return BillingAdjustment
     */
    public function setStatus(string $status): BillingAdjustment
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPLIED_FULLY, self::STATUS_APPLIED_PARTIALLY])) {
            throw new InvalidArgumentException("Invalid adjustment status $status");
        }
        $this->status = $status;

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
     * @return BillingAdjustment
     */
    public function setDateCreated(DateTime $dateCreated): BillingAdjustment
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
