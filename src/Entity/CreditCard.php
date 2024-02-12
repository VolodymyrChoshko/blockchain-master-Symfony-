<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "credit_cards",
 *     prefix="crc_",
 *     repository="Repository\CreditCardRepository",
 *     indexes={
 *          "crc_org_id"={"orgId"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class CreditCard
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
     * @DB\Column("number_4")
     * @DB\Sql("CHAR(4) NOT NULL")
     */
    protected $number4 = '';

    /**
     * @var int
     * @DB\Column("exp_month")
     * @DB\Sql("TINYINT(2) UNSIGNED NOT NULL")
     */
    protected $expMonth = 0;

    /**
     * @var int
     * @DB\Column("exp_year")
     * @DB\Sql("SMALLINT(4) UNSIGNED NOT NULL")
     */
    protected $expYear = 0;

    /**
     * @var string
     * @DB\Column("brand")
     * @DB\Sql("VARCHAR(20) NOT NULL")
     */
    protected $brand = '';

    /**
     * @var string
     * @DB\Column("stripe_id")
     * @DB\Sql("VARCHAR(50) NOT NULL DEFAULT ''")
     */
    protected $stripeId = '';

    /**
     * @var int
     * @DB\Column("is_active")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $isActive = 0;

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
     * @return CreditCard
     */
    public function setId(int $id): CreditCard
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
     * @return CreditCard
     */
    public function setOrgId(int $orgId): CreditCard
    {
        $this->orgId = $orgId;

        return $this;
    }

    /**
     * @return string
     */
    public function getNumber4(): string
    {
        return $this->number4;
    }

    /**
     * @param string $number4
     *
     * @return CreditCard
     */
    public function setNumber4(string $number4): CreditCard
    {
        $this->number4 = $number4;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpMonth(): int
    {
        return $this->expMonth;
    }

    /**
     * @param int $expMonth
     *
     * @return CreditCard
     */
    public function setExpMonth(int $expMonth): CreditCard
    {
        $this->expMonth = $expMonth;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpYear(): int
    {
        return $this->expYear;
    }

    /**
     * @param int $expYear
     *
     * @return CreditCard
     */
    public function setExpYear(int $expYear): CreditCard
    {
        $this->expYear = $expYear;

        return $this;
    }

    /**
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     *
     * @return CreditCard
     */
    public function setBrand(string $brand): CreditCard
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return string
     */
    public function getStripeId(): string
    {
        return $this->stripeId;
    }

    /**
     * @param string $stripeId
     *
     * @return CreditCard
     */
    public function setStripeId(string $stripeId): CreditCard
    {
        $this->stripeId = $stripeId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool)$this->isActive;
    }

    /**
     * @param bool $isActive
     *
     * @return CreditCard
     */
    public function setIsActive(bool $isActive): CreditCard
    {
        $this->isActive = (int)$isActive;

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
     * @return CreditCard
     */
    public function setDateCreated(DateTime $dateCreated): CreditCard
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
