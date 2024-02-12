<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use InvalidArgumentException;

/**
 * @DB\Table(
 *     "invoice_items",
 *     prefix="ivi_",
 *     repository="Repository\InvoiceItemRepository",
 *     indexes={"ivi_ivo_id"={"invoiceId"}},
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class InvoiceItem
{
    const TYPE_CHARGE   = 'charge';
    const TYPE_DISCOUNT = 'discount';

    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var int
     * @DB\Column("ivo_id")
     * @DB\Sql("INT(11) NOT NULL")
     * @DB\ForeignKey("Entity\Invoice", references="id", onDelete="CASCADE", onUpdate="RESTRICT")
     */
    protected $invoiceId;

    /**
     * @var int
     * @DB\Column("amount_cents")
     * @DB\Sql("INT(11) NOT NULL")
     */
    protected $amountCents = 0;

    /**
     * @var string
     * @DB\Column("description")
     * @DB\Sql("VARCHAR(500) NOT NULL")
     */
    protected $description = '';

    /**
     * @var string
     * @DB\Column("type")
     * @DB\Sql("VARCHAR(20) NOT NULL DEFAULT ''")
     */
    protected $type = '';

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
     * @return InvoiceItem
     */
    public function setId(int $id): InvoiceItem
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getInvoiceId(): int
    {
        return $this->invoiceId;
    }

    /**
     * @param int $invoiceId
     *
     * @return InvoiceItem
     */
    public function setInvoiceId(int $invoiceId): InvoiceItem
    {
        $this->invoiceId = $invoiceId;

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
     * @param float $amountCents
     *
     * @return InvoiceItem
     */
    public function setAmountCents(float $amountCents): InvoiceItem
    {
        $this->amountCents = (int)$amountCents;

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
     * @return InvoiceItem
     */
    public function setDescription(string $description): InvoiceItem
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return InvoiceItem
     */
    public function setType(string $type): InvoiceItem
    {
        if (!in_array($type, [self::TYPE_CHARGE, self::TYPE_DISCOUNT])) {
            throw new InvalidArgumentException("Invalid invoice type ${type}");
        }
        $this->type = $type;

        return $this;
    }
}
