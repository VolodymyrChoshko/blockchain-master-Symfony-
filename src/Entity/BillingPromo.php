<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "billing_promos",
 *     prefix="bpro_",
 *     repository="Repository\BillingPromoRepository",
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class BillingPromo
{
    const TYPE_FREE = 'free';
    const TYPE_DISCOUNT = 'discount';
    const TYPE_FIXED_DOLLAR = 'fixed_dollar';
    const TYPE_FIXED_PERCENT = 'fixed_percent';
    const VALUE_TYPE_DOLLAR = 'dollar';
    const VALUE_TYPE_PERCENT = 'percent';

    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var string
     * @DB\Column("code")
     * @DB\Sql("VARCHAR(32) NOT NULL")
     */
    protected $code = '';

    /**
     * @var string
     * @DB\Column("name")
     * @DB\Sql("VARCHAR(255) NOT NULL")
     */
    protected $name = '';

    /**
     * @var string
     * @DB\Column("description")
     * @DB\Sql("VARCHAR(500) NOT NULL")
     */
    protected $description = '';

    /**
     * @var string
     * @DB\Column("type")
     * @DB\Sql("ENUM('discount', 'free', 'fixed_dollar', 'fixed_percent') DEFAULT 'discount'")
     */
    protected $type = '';

    /**
     * @var int
     * @DB\Column("value")
     * @DB\Sql("INT(11) NOT NULL DEFAULT 0")
     */
    protected $value = 0;

    /**
     * @var string
     * @DB\Column("value_type")
     * @DB\Sql("ENUM('dollar', 'percent') DEFAULT 'dollar'")
     */
    protected $valueType = 'dollar';

    /**
     * @var int
     * @DB\Column("period_months")
     * @DB\Sql("TINYINT(2) NOT NULL DEFAULT 0")
     */
    protected $periodMonths = 0;

    /**
     * @var string
     * @DB\Column("targets")
     * @DB\Sql("VARCHAR(255) NOT NULL DEFAULT ''")
     */
    protected $targets = '';

    /**
     * @var int
     * @DB\Column("is_new_user")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $isNewUser = 0;

    /**
     * @var int
     * @DB\Column("is_team_plan")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $isTeamPlan = 0;

    /**
     * @var int
     * @DB\Column("count_redeemed")
     * @DB\Sql("INT(11) NOT NULL DEFAULT 0")
     */
    protected $countRedeemed = 0;

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
     * @return BillingPromo
     */
    public function setId(int $id): BillingPromo
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return BillingPromo
     */
    public function setCode(string $code): BillingPromo
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return BillingPromo
     */
    public function setName(string $name): BillingPromo
    {
        $this->name = $name;

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
     * @return BillingPromo
     */
    public function setDescription(string $description): BillingPromo
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
     * @return BillingPromo
     */
    public function setType(string $type): BillingPromo
    {
        $types = [self::TYPE_DISCOUNT, self::TYPE_FREE, self::TYPE_FIXED_DOLLAR, self::TYPE_FIXED_PERCENT];
        if (!in_array($type, $types)) {
            throw new \InvalidArgumentException('Invalid billing promo type ' . $type);
        }
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param int $value
     *
     * @return BillingPromo
     */
    public function setValue(int $value): BillingPromo
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getValueType(): string
    {
        return $this->valueType;
    }

    /**
     * @param string $valueType
     *
     * @return BillingPromo
     */
    public function setValueType(string $valueType): BillingPromo
    {
        if (!in_array($valueType, [self::VALUE_TYPE_DOLLAR, self::VALUE_TYPE_PERCENT])) {
            throw new \InvalidArgumentException('Invalid billing promo value type ' . $valueType);
        }
        $this->valueType = $valueType;

        return $this;
    }

    /**
     * @return int
     */
    public function getPeriodMonths(): int
    {
        return $this->periodMonths;
    }

    /**
     * @param int $periodMonths
     *
     * @return BillingPromo
     */
    public function setPeriodMonths(int $periodMonths): BillingPromo
    {
        $this->periodMonths = $periodMonths;

        return $this;
    }

    /**
     * @return string
     */
    public function getTargets(): string
    {
        return $this->targets;
    }

    /**
     * @param string $targets
     *
     * @return BillingPromo
     */
    public function setTargets(string $targets): BillingPromo
    {
        $this->targets = $targets;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNewUser(): bool
    {
        return (bool)$this->isNewUser;
    }

    /**
     * @param bool $isNewUser
     *
     * @return BillingPromo
     */
    public function setIsNewUser(bool $isNewUser): BillingPromo
    {
        $this->isNewUser = (int)$isNewUser;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsTeamPlan(): bool
    {
        return (bool)$this->isTeamPlan;
    }

    /**
     * @param bool $isTeamPlan
     *
     * @return BillingPromo
     */
    public function setIsTeamPlan(bool $isTeamPlan): BillingPromo
    {
        $this->isTeamPlan = (int)$isTeamPlan;

        return $this;
    }

    /**
     * @return int
     */
    public function getCountRedeemed(): int
    {
        return $this->countRedeemed;
    }

    /**
     * @param int $countRedeemed
     *
     * @return BillingPromo
     */
    public function setCountRedeemed(int $countRedeemed): BillingPromo
    {
        $this->countRedeemed = $countRedeemed;

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
     * @return BillingPromo
     */
    public function setDateCreated(DateTime $dateCreated): BillingPromo
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
