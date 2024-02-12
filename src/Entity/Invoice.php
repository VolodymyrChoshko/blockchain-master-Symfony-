<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;
use InvalidArgumentException;

/**
 * @DB\Table(
 *     "invoices",
 *     prefix="ivo_",
 *     repository="Repository\InvoiceRepository",
 *     indexes={
 *          "ivo_org_id"={"orgId"},
 *          "ivo_billing_transaction_id"={"billingTransactionId"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class Invoice
{
    const STATUS_PAID   = 'paid';
    const STATUS_BILLED = 'billed';

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
     * @DB\Column("billing_transaction_id")
     * @DB\Sql("INT(11) DEFAULT NULL")
     * @DB\ForeignKey("Entity\BillingTransaction", references="id")
     */
    protected $billingTransactionId = 0;

    /**
     * @var int
     * @DB\Column("total_amount_cents")
     * @DB\Sql("INT(11) NOT NULL")
     */
    protected $amountCents = 0;

    /**
     * @var string
     * @DB\Column("status")
     * @DB\Sql("VARCHAR(12) NOT NULL")
     */
    protected $status = '';

    /**
     * @var string
     * @DB\Column("file_url")
     * @DB\Sql("VARCHAR(255) NOT NULL")
     */
    protected $fileUrl = '';

    /**
     * @var string
     * @DB\Column("description")
     * @DB\Sql("VARCHAR(255) NOT NULL")
     */
    protected $description = '';

    /**
     * @var string
     * @DB\Column("notes")
     * @DB\Sql("VARCHAR(500) NOT NULL DEFAULT ''")
     */
    protected $notes = '';

    /**
     * @var DateTime
     * @DB\Column("date_created")
     * @DB\Sql("DATETIME NOT NULL")
     */
    protected $dateCreated;

    /**
     * @var DateTime
     * @DB\Column("date_period_start")
     * @DB\Sql("DATETIME NOT NULL")
     */
    protected $datePeriodStart;

    /**
     * @var DateTime
     * @DB\Column("date_period_end")
     * @DB\Sql("DATETIME NOT NULL")
     */
    protected $datePeriodEnd;

    /**
     * @var InvoiceItem[]
     */
    protected $items = [];

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
     * @return Invoice
     */
    public function setId(int $id): Invoice
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
     * @return Invoice
     */
    public function setOrgId(int $orgId): Invoice
    {
        $this->orgId = $orgId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getBillingTransactionId(): ?int
    {
        return $this->billingTransactionId;
    }

    /**
     * @param int|null $billingTransactionId
     *
     * @return Invoice
     */
    public function setBillingTransactionId(?int $billingTransactionId): Invoice
    {
        $this->billingTransactionId = $billingTransactionId;

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
     * @return Invoice
     */
    public function setAmountCents(int $amountCents): Invoice
    {
        $this->amountCents = $amountCents;

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
     * @return Invoice
     */
    public function setStatus(string $status): Invoice
    {
        if (!in_array($status, [self::STATUS_BILLED, self::STATUS_PAID])) {
            throw new InvalidArgumentException("Invalid invoice status $status");
        }
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileUrl(): string
    {
        return $this->fileUrl;
    }

    /**
     * @param string $fileUrl
     *
     * @return Invoice
     */
    public function setFileUrl(string $fileUrl): Invoice
    {
        $this->fileUrl = $fileUrl;

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
     * @return Invoice
     */
    public function setDescription(string $description): Invoice
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     *
     * @return Invoice
     */
    public function setNotes(string $notes): Invoice
    {
        $this->notes = $notes;

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
     * @return Invoice
     */
    public function setDateCreated(DateTime $dateCreated): Invoice
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return InvoiceItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param InvoiceItem[] $items
     *
     * @return Invoice
     */
    public function setItems(array $items): Invoice
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDatePeriodStart(): DateTime
    {
        return $this->datePeriodStart;
    }

    /**
     * @param DateTime $datePeriodStart
     *
     * @return Invoice
     */
    public function setDatePeriodStart(DateTime $datePeriodStart): Invoice
    {
        $this->datePeriodStart = $datePeriodStart;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDatePeriodEnd(): DateTime
    {
        return $this->datePeriodEnd;
    }

    /**
     * @param DateTime $datePeriodEnd
     *
     * @return Invoice
     */
    public function setDatePeriodEnd(DateTime $datePeriodEnd): Invoice
    {
        $this->datePeriodEnd = $datePeriodEnd;

        return $this;
    }
}
