<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * @DB\Table(
 *     "billing_plans",
 *     prefix="blp_",
 *     repository="Repository\BillingPlanRepository",
 *     indexes={"blp_credit_card_id"={"creditCardId"}},
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class BillingPlan
{
    const TYPE_TRIAL             = 'trial';
    const TYPE_SOLO              = 'solo';
    const TYPE_TEAM              = 'team';
    const TYPE_CUSTOM            = 'custom';
    const TYPE_TRIAL_INTEGRATION = 'trial_integration';

    const FLAG_FREE_INTEGRATION   = 'free_integration';
    const FLAG_NONPROFIT_DISCOUNT = 'nonprofit_discount';

    const AMOUNT_NONPROFIT_DISCOUNT = 0.25;

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
     * @DB\UniqueIndex()
     */
    protected $orgId = 0;

    /**
     * @var string
     * @DB\Column("type")
     * @DB\Sql("ENUM('trial', 'trial_integration', 'solo', 'team', 'custom') NOT NULL DEFAULT 'solo'")
     */
    protected $type = '';

    /**
     * @var int
     * @DB\Column("charge_day")
     * @DB\Sql("TINYINT(2) NOT NULL DEFAULT 0")
     */
    protected $chargeDay = 0;

    /**
     * @var int
     * @DB\Column("charge_month")
     * @DB\Sql("TINYINT(2) NOT NULL DEFAULT 0")
     */
    protected $chargeMonth = 1;

    /**
     * @var int
     * @DB\Column("charge_year")
     * @DB\Sql("SMALLINT(2) NOT NULL DEFAULT 2021")
     */
    protected $chargeYear = 2021;

    /**
     * @var int
     * @DB\Column("fixed_price_cents")
     * @DB\Sql("INT(11) NOT NULL DEFAULT 0")
     */
    protected $fixedPriceCents = 0;

    /**
     * @var int
     * @DB\Column("reoccurring_months")
     * @DB\Sql("TINYINT(2) UNSIGNED NOT NULL DEFAULT 1")
     */
    protected $reoccurringMonths = 1;

    /**
     * @var string
     * @DB\Column("flags")
     * @DB\Sql("VARCHAR(60) NOT NULL DEFAULT ''")
     */
    protected $flags = '';

    /**
     * @var int
     * @DB\Column("is_upcoming_notice_sent")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $isUpcomingNoticeSent = 0;

    /**
     * @var int
     * @DB\Column("is_trial_complete")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $isTrialComplete = 0;

    /**
     * @var int
     * @DB\Column("is_trial_notice_sent")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $isTrialNoticeSent = 0;

    /**
     * @var int
     * @DB\Column("is_trial_extended")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $isTrialExtended = 0;

    /**
     * @var int
     * @DB\Column("is_downgraded")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $isDowngraded = 0;

    /**
     * @var int
     * @DB\Column("is_paused")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $isPaused = 0;

    /**
     * @var string
     * @DB\Column("pause_reason")
     * @DB\Sql("VARCHAR(60) NOT NULL DEFAULT ''")
     */
    protected $pauseReason = '';

    /**
     * @var DateTime
     * @DB\Column("date_paused")
     * @DB\Sql("DATETIME DEFAULT NULL")
     */
    protected $datePaused;

    /**
     * @var DateTime
     * @DB\Column("date_declined")
     * @DB\Sql("DATETIME DEFAULT NULL")
     */
    protected $dateDeclined;

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
     * @return BillingPlan
     */
    public function setId(int $id): BillingPlan
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
     * @return BillingPlan
     */
    public function setOrgId(int $orgId): BillingPlan
    {
        $this->orgId = $orgId;

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
     * @return BillingPlan
     */
    public function setType(string $type): BillingPlan
    {
        $types = [self::TYPE_CUSTOM, self::TYPE_SOLO, self::TYPE_TEAM, self::TYPE_TRIAL, self::TYPE_TRIAL_INTEGRATION];
        if (!in_array($type, $types)) {
            throw new InvalidArgumentException("Invalid billing plan type $type");
        }
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getChargeDay(): int
    {
        return $this->chargeDay;
    }

    /**
     * @param int $chargeDay
     *
     * @return BillingPlan
     */
    public function setChargeDay(int $chargeDay): BillingPlan
    {
        $this->chargeDay = $chargeDay;

        return $this;
    }

    /**
     * @return int
     */
    public function getChargeMonth(): int
    {
        return $this->chargeMonth;
    }

    /**
     * @param int $chargeMonth
     *
     * @return BillingPlan
     */
    public function setChargeMonth(int $chargeMonth): BillingPlan
    {
        $this->chargeMonth = $chargeMonth;

        return $this;
    }

    /**
     * @return int
     */
    public function getChargeYear(): int
    {
        return $this->chargeYear;
    }

    /**
     * @param int $chargeYear
     *
     * @return BillingPlan
     */
    public function setChargeYear(int $chargeYear): BillingPlan
    {
        $this->chargeYear = $chargeYear;

        return $this;
    }

    /**
     * @return int
     */
    public function getReoccurringMonths(): int
    {
        return $this->reoccurringMonths;
    }

    /**
     * @param int $reoccurringMonths
     *
     * @return BillingPlan
     */
    public function setReoccurringMonths(int $reoccurringMonths): BillingPlan
    {
        $this->reoccurringMonths = $reoccurringMonths;

        return $this;
    }

    /**
     * @return int
     */
    public function getFixedPriceCents(): int
    {
        return $this->fixedPriceCents;
    }

    /**
     * @param int $fixedPriceCents
     *
     * @return BillingPlan
     */
    public function setFixedPriceCents(int $fixedPriceCents): BillingPlan
    {
        $this->fixedPriceCents = $fixedPriceCents;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTrialComplete(): bool
    {
        return (bool)$this->isTrialComplete;
    }

    /**
     * @param bool $isTrialComplete
     *
     * @return BillingPlan
     */
    public function setIsTrialComplete(bool $isTrialComplete): BillingPlan
    {
        $this->isTrialComplete = (int)$isTrialComplete;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTrialNoticeSent(): bool
    {
        return (bool)$this->isTrialNoticeSent;
    }

    /**
     * @param bool $isTrialNoticeSent
     *
     * @return BillingPlan
     */
    public function setIsTrialNoticeSent(bool $isTrialNoticeSent): BillingPlan
    {
        $this->isTrialNoticeSent = (int)$isTrialNoticeSent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTrialExtended(): bool
    {
        return (bool)$this->isTrialExtended;
    }

    /**
     * @param bool $isTrialExtended
     *
     * @return BillingPlan
     */
    public function setIsTrialExtended(bool $isTrialExtended): BillingPlan
    {
        $this->isTrialExtended = (int)$isTrialExtended;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUpcomingNoticeSent(): bool
    {
        return (bool)$this->isUpcomingNoticeSent;
    }

    /**
     * @param bool $isUpcomingNoticeSent
     *
     * @return BillingPlan
     */
    public function setIsUpcomingNoticeSent(bool $isUpcomingNoticeSent): BillingPlan
    {
        $this->isUpcomingNoticeSent = (int)$isUpcomingNoticeSent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDowngraded(): bool
    {
        return (bool)$this->isDowngraded;
    }

    /**
     * @param bool $isDowngraded
     *
     * @return BillingPlan
     */
    public function setIsDowngraded(bool $isDowngraded): BillingPlan
    {
        $this->isDowngraded = (int)$isDowngraded;

        return $this;
    }

    /**
     * @return string
     */
    public function getFlags(): string
    {
        return $this->flags;
    }

    /**
     * @param string $flags
     *
     * @return BillingPlan
     */
    public function setFlags(string $flags): BillingPlan
    {
        $valid = [self::FLAG_FREE_INTEGRATION, self::FLAG_NONPROFIT_DISCOUNT];
        $parts = $this->getFlagsArray($flags);
        foreach($parts as $flag) {
            if (!in_array($flag, $valid)) {
                throw new InvalidArgumentException("Invalid billing plan flag $flag");
            }
        }
        $this->flags = $flags;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPaused(): bool
    {
        return (bool)$this->isPaused;
    }

    /**
     * @param bool $isPaused
     *
     * @return BillingPlan
     */
    public function setIsPaused(bool $isPaused): BillingPlan
    {
        $this->isPaused = (int)$isPaused;

        return $this;
    }

    /**
     * @return string
     */
    public function getPauseReason(): string
    {
        return $this->pauseReason;
    }

    /**
     * @param string $pauseReason
     *
     * @return BillingPlan
     */
    public function setPauseReason(string $pauseReason): BillingPlan
    {
        $this->pauseReason = $pauseReason;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDatePaused(): ?DateTime
    {
        return $this->datePaused;
    }

    /**
     * @param DateTime|null $datePaused
     *
     * @return BillingPlan
     */
    public function setDatePaused(?DateTime $datePaused): BillingPlan
    {
        $this->datePaused = $datePaused;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateDeclined(): ?DateTime
    {
        return $this->dateDeclined;
    }

    /**
     * @param DateTime|null $dateDeclined
     *
     * @return BillingPlan
     */
    public function setDateDeclined(?DateTime $dateDeclined): BillingPlan
    {
        $this->dateDeclined = $dateDeclined;

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
     * @return BillingPlan
     */
    public function setDateCreated(DateTime $dateCreated): BillingPlan
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this->getType() === self::TYPE_TRIAL;
    }

    /**
     * @return bool
     */
    public function isTrialIntegration(): bool
    {
        return $this->getType() === self::TYPE_TRIAL_INTEGRATION;
    }

    /**
     * @return bool
     */
    public function isSolo(): bool
    {
        return $this->getType() === self::TYPE_SOLO;
    }

    /**
     * @return bool
     */
    public function isTeam(): bool
    {
        return $this->getType() === self::TYPE_TEAM;
    }

    /**
     * @return bool
     */
    public function isCustom(): bool
    {
        return $this->getType() === self::TYPE_CUSTOM;
    }

    /**
     * @return bool
     */
    public function isDeclined(): bool
    {
        return $this->dateDeclined !== null;
    }

    /**
     * @return int
     */
    public function getDaysUntilTrialEnds(): int
    {
        if (!$this->isTrial() && !$this->isTrialIntegration()) {
            return 0;
        }

        $thisMonth = (int)date('n');
        if ($this->chargeMonth > $thisMonth && $this->chargeMonth === 12) {
            // $nextYear = (int)(new DateTime('next year'))->format('Y');
            $nextYear = (int)date('Y');
        } else {
            $nextYear = (int)date('Y');
        }

        $nextDate = mktime(0, 0, 0, $this->chargeMonth, $this->chargeDay, $nextYear);
        $nowDate  = time();

        return (int)floor(($nextDate - $nowDate) / (60 * 60 * 24));
    }

    /**
     * @param int $skipMonths
     *
     * @return DateTime
     * @throws Exception
     */
    public function getNextBillingDate(int $skipMonths = 0): DateTime
    {
        $next = mktime(0, 0, 0, $this->chargeMonth, $this->chargeDay, $this->chargeYear);
        $next = new DateTime('@' . $next);
        $next->add(new DateInterval('P' . ($skipMonths * $this->reoccurringMonths) . 'M'));

        return $next;
    }

    /**
     * @param string $flag
     *
     * @return bool
     */
    public function hasFlag(string $flag): bool
    {
        $flags = $this->getFlagsArray($this->flags);

        return in_array($flag, $flags);
    }

    /**
     * @return int
     */
    public function getFlagCount(): int
    {
        $flags = $this->getFlagsArray($this->flags);

        return count($flags);
    }

    /**
     * @param string $flags
     *
     * @return array
     */
    protected function getFlagsArray(string $flags): array
    {
        return array_filter(array_map('trim', explode(',', $flags)));
    }
}
