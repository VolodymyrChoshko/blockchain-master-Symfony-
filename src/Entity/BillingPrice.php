<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "billing_prices",
 *     prefix="bpc_",
 *     repository="Repository\BillingPriceRepository",
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class BillingPrice
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var string
     * @DB\Column("target")
     * @DB\Sql("VARCHAR(40) NOT NULL")
     * @DB\UniqueIndex()
     */
    protected $target = '';

    /**
     * @var string
     * @DB\Column("label")
     * @DB\Sql("VARCHAR(40) NOT NULL")
     */
    protected $label = '';

    /**
     * @var int
     * @DB\Column("amount_cents")
     * @DB\Sql("INT(11) NOT NULL")
     */
    protected $amountCents = 0;

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
     * @return BillingPrice
     */
    public function setId(int $id): BillingPrice
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $target
     *
     * @return BillingPrice
     */
    public function setTarget(string $target): BillingPrice
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return BillingPrice
     */
    public function setLabel(string $label): BillingPrice
    {
        $this->label = $label;

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
     * @return BillingPrice
     */
    public function setAmountCents(int $amountCents): BillingPrice
    {
        $this->amountCents = $amountCents;

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
     * @return BillingPrice
     */
    public function setDateCreated(DateTime $dateCreated): BillingPrice
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
