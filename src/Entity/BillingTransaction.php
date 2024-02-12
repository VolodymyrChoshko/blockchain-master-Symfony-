<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table("billing_transactions", prefix="blt_", repository="Repository\BillingTransactionRepository")
 */
class BillingTransaction
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
     * @var int
     * @DB\Column("credit_card_id")
     * @DB\Sql("INT(11) NOT NULL")
     * @DB\ForeignKey("Entity\CreditCard", references="id")
     */
    protected $creditCardId = 0;

    /**
     * @var int
     * @DB\Column("amount_cents")
     * @DB\Sql("INT(11) NOT NULL")
     */
    protected $amountCents = 0;

    /**
     * @var string
     * @DB\Column("transaction_id")
     * @DB\Sql("VARCHAR(32) NOT NULL")
     */
    protected $transactionId = '';

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
     * @return BillingTransaction
     */
    public function setId(int $id): BillingTransaction
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
     * @return BillingTransaction
     */
    public function setOrgId(int $orgId): BillingTransaction
    {
        $this->orgId = $orgId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreditCardId(): int
    {
        return $this->creditCardId;
    }

    /**
     * @param int $creditCardId
     *
     * @return BillingTransaction
     */
    public function setCreditCardId(int $creditCardId): BillingTransaction
    {
        $this->creditCardId = $creditCardId;

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
     * @return BillingTransaction
     */
    public function setAmountCents(int $amountCents): BillingTransaction
    {
        $this->amountCents = $amountCents;

        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     *
     * @return BillingTransaction
     */
    public function setTransactionId(string $transactionId): BillingTransaction
    {
        $this->transactionId = $transactionId;

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
     * @return BillingTransaction
     */
    public function setDateCreated(DateTime $dateCreated): BillingTransaction
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
