<?php
namespace Entity;

use BlocksEdit\Database\ArrayEntity;
use BlocksEdit\Database\Annotations as DB;
use DateTime;
use Exception;

/**
 * @DB\Table(
 *     "users",
 *     prefix="usr_",
 *     repository="Repository\UserRepository",
 *     uniqueIndexes={
 *          "usr_email"={"email", "parent"}
 *     },
 *     indexes={
 *          "usr_trial_notice"={"trialNotice"},
 *          "usr_updated"={"updated"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 * @DB\CacheTag("Tag\UserTag")
 */
class User extends ArrayEntity
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("int NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var User|null
     * @DB\Column("parent_id", nullable=true)
     * @DB\Join("Entity\User", references="id")
     * @DB\Sql("int DEFAULT NULL")
     */
    protected $parent = null;

    /**
     * @var string
     * @DB\Column("pass")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $pass = '';

    /**
     * @var string
     * @DB\Column("email")
     * @DB\Sql("varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $email = '';

    /**
     * @var string
     * @DB\Column("name")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $name = '';

    /**
     * @var string
     * @DB\Column("job")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $job = '';

    /**
     * @var string
     * @DB\Column("organization")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $organization = '';

    /**
     * @var string
     * @DB\Column("avatar")
     * @DB\Sql("varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $avatar = '';

    /**
     * @var string
     * @DB\Column("avatar_60")
     * @DB\Sql("varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $avatar60 = '';

    /**
     * @var string
     * @DB\Column("avatar_120")
     * @DB\Sql("varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $avatar120 = '';

    /**
     * @var string
     * @DB\Column("avatar_240")
     * @DB\Sql("varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $avatar240 = '';

    /**
     * @var string
     * @DB\Column("created_at", castTo="int")
     * @DB\Sql("varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $createdAt = '';

    /**
     * @var string
     * @DB\Column("stripe")
     * @DB\Sql("varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $stripe = '';

    /**
     * @var int
     * @DB\Column("exp_date")
     * @DB\Sql("int NOT NULL DEFAULT '0'")
     */
    protected $expDate = 0;

    /**
     * @var int
     * @DB\Column("invited")
     * @DB\Sql("int NOT NULL DEFAULT '0'")
     */
    protected $invited = 0;

    /**
     * @var int
     * @DB\Column("welcome")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $welcome = 0;

    /**
     * @var int
     * @DB\Column("trial_notice")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $trialNotice = 0;

    /**
     * @var int
     * @DB\Column("newsletter")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $newsletter = 0;

    /**
     * @var int
     * @DB\Column("skin_tone")
     * @DB\Sql("tinyint(2) NOT NULL DEFAULT '-1'")
     */
    protected $skinTone = -1;

    /**
     * @var int
     * @DB\Column("updated")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $updated = 0;

    /**
     * @var string
     * @DB\Column("flags")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $flags = '';

    /**
     * @var int
     * @DB\Column("is_site_admin")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $isSiteAdmin = 0;

    /**
     * @var string
     * @DB\Column("timezone")
     * @DB\Sql("varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'America/New_York'")
     */
    protected $timezone = 'America/New_York';

    /**
     * @var bool
     * @DB\Column("is_notifications_enabled")
     * @DB\SQL("TINYINT(1) NOT NULL DEFAULT '0'")
     */
    protected $isNotificationsEnabled = false;

    /**
     * @var bool
     * @DB\Column("is_emails_enabled")
     * @DB\SQL("TINYINT(1) NOT NULL DEFAULT '0'")
     */
    protected $isEmailsEnabled = false;

    /**
     * @var bool
     * @DB\Column("is_showing_count")
     * @DB\SQL("TINYINT(1) NOT NULL DEFAULT '0'")
     */
    protected $isShowingCount = true;

    /**
     * @var array
     * @DB\Column("web_push_subscription", json=true)
     * @DB\SQL("VARCHAR(1000) NOT NULL DEFAULT '[]'")
     */
    protected $webPushSubscription = [];

    /**
     * @var string
     * @DB\Column("2fa_secret")
     * @DB\Sql("varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $tfaSecret = '';

    /**
     * @var DateTime|null
     * @DB\Column("date_last_login")
     * @DB\Sql("datetime DEFAULT NULL")
     */
    protected $dateLastLogin;

    /**
     * @var DateTime|null
     * @DB\Column("date_prev_login")
     * @DB\Sql("datetime DEFAULT NULL")
     */
    protected $datePrevLogin;

    /**
     * @var int|null
     * @DB\Column("is_dark_mode")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $isDarkMode = 0;

    /**
     * @var string
     * @DB\Column("join_ref")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $joinRef = '';

    /**
     * @var string
     */
    protected $passPlain = '';

    /**
     * @var bool
     */
    protected $isResponded = false;

    /**
     * @var bool
     */
    protected $isOwner = false;

    protected $isAdmin = false;
    /**
     * @var bool
     * @DB\Column("edited_tmpl_settings")
     * @DB\Sql("tinyint(1) NOT NULL DEFAULT '0'")
     */
    public $editedTemplateSettings = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('usr_');
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
     * @return User
     */
    public function setId(int $id): User
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPass(): string
    {
        return $this->pass;
    }

    /**
     * @param string $pass
     *
     * @return User
     */
    public function setPass(string $pass): User
    {
        $this->pass = $pass;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;

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
     * @return User
     */
    public function setName(string $name): User
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        $parts = explode(' ', $this->getName(), 2);
        if (!isset($parts[0])) {
            $parts = ['A'];
        }

        return $parts[0];
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        $parts = explode(' ', $this->getName(), 2);
        if (count($parts) !== 2) {
            return '';
        }

        return $parts[1];
    }

    /**
     * @return string
     */
    public function getInitials(): string
    {
        $firstName = $this->getFirstName();
        $lastName  = $this->getLastName();
        if (!$lastName) {
            return $firstName[0];
        }

        return $firstName[0] . $lastName[0];
    }

    /**
     * @return string
     */
    public function getJob(): string
    {
        return $this->job;
    }

    /**
     * @param string $job
     *
     * @return User
     */
    public function setJob(string $job): User
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrganization(): string
    {
        return $this->organization;
    }

    /**
     * @param string $organization
     *
     * @return User
     */
    public function setOrganization(string $organization): User
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatar(): string
    {
        return $this->avatar;
    }

    /**
     * @param string $avatar
     *
     * @return User
     */
    public function setAvatar(string $avatar): User
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatar60(): string
    {
        return $this->avatar60;
    }

    /**
     * @param string $avatar60
     *
     * @return User
     */
    public function setAvatar60(string $avatar60): User
    {
        $this->avatar60 = $avatar60;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatar120(): string
    {
        return $this->avatar120;
    }

    /**
     * @param string $avatar120
     *
     * @return User
     */
    public function setAvatar120(string $avatar120): User
    {
        $this->avatar120 = $avatar120;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatar240(): string
    {
        return $this->avatar240;
    }

    /**
     * @param string $avatar240
     *
     * @return User
     */
    public function setAvatar240(string $avatar240): User
    {
        $this->avatar240 = $avatar240;

        return $this;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getCreatedAt(): int
    {
        return (int)$this->createdAt;
    }

    /**
     * @param int $createdAt
     *
     * @return User
     */
    public function setCreatedAt(int $createdAt): User
    {
        $this->createdAt = (string)$createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getStripe(): string
    {
        return $this->stripe;
    }

    /**
     * @param string $stripe
     *
     * @return User
     */
    public function setStripe(string $stripe): User
    {
        $this->stripe = $stripe;

        return $this;
    }

    /**
     * @return int
     */
    public function getExpDate(): int
    {
        return $this->expDate;
    }

    /**
     * @param int $expDate
     *
     * @return User
     */
    public function setExpDate(int $expDate): User
    {
        $this->expDate = $expDate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInvited(): bool
    {
        return (bool)$this->invited;
    }

    /**
     * @param bool $invited
     *
     * @return User
     */
    public function setInvited(bool $invited): User
    {
        $this->invited = (int)$invited;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWelcome(): bool
    {
        return (bool)$this->welcome;
    }

    /**
     * @param bool $welcome
     *
     * @return User
     */
    public function setWelcome(bool $welcome): User
    {
        $this->welcome = (int)$welcome;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTrialNotice(): bool
    {
        return (bool)$this->trialNotice;
    }

    /**
     * @param bool $trialNotice
     *
     * @return User
     */
    public function setTrialNotice(bool $trialNotice): User
    {
        $this->trialNotice = (int)$trialNotice;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNewsletter(): bool
    {
        return (bool)$this->newsletter;
    }

    /**
     * @param bool $newsletter
     *
     * @return User
     */
    public function setNewsletter(bool $newsletter): User
    {
        $this->newsletter = (int)$newsletter;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUpdated(): bool
    {
        return (bool)$this->updated;
    }

    /**
     * @param bool $updated
     *
     * @return User
     */
    public function setUpdated(bool $updated): User
    {
        $this->updated = (int)$updated;

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
     * @return User
     */
    public function setFlags(string $flags): User
    {
        $this->flags = $flags;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getParent(): ?User
    {
        return $this->parent;
    }

    /**
     * @param User|null $parent
     *
     * @return User
     */
    public function setParent(?User $parent): User
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSiteAdmin(): bool
    {
        return (bool)$this->isSiteAdmin;
    }

    /**
     * @param bool $isSiteAdmin
     *
     * @return User
     */
    public function setIsSiteAdmin(bool $isSiteAdmin): User
    {
        $this->isSiteAdmin = (int)$isSiteAdmin;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        if (!$this->timezone) {
            return 'America/New_York';
        }
        return $this->timezone;
    }

    /**
     * @param string $timezone
     *
     * @return User
     */
    public function setTimezone(string $timezone): User
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNotificationsEnabled(): bool
    {
        return $this->isNotificationsEnabled;
    }

    /**
     * @param bool $isNotificationsEnabled
     *
     * @return User
     */
    public function setIsNotificationsEnabled(bool $isNotificationsEnabled): User
    {
        $this->isNotificationsEnabled = $isNotificationsEnabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmailsEnabled(): bool
    {
        return $this->isEmailsEnabled;
    }

    /**
     * @param bool $isEmailsEnabled
     *
     * @return User
     */
    public function setIsEmailsEnabled(bool $isEmailsEnabled): User
    {
        $this->isEmailsEnabled = $isEmailsEnabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isShowingCount(): bool
    {
        return $this->isShowingCount;
    }

    /**
     * @param bool $isShowingCount
     *
     * @return User
     */
    public function setIsShowingCount(bool $isShowingCount): User
    {
        $this->isShowingCount = $isShowingCount;

        return $this;
    }

    /**
     * @return array
     */
    public function getWebPushSubscription(): array
    {
        return $this->webPushSubscription;
    }

    /**
     * @param array $webPushSubscription
     *
     * @return User
     */
    public function setWebPushSubscription(array $webPushSubscription): User
    {
        $this->webPushSubscription = $webPushSubscription;

        return $this;
    }

    /**
     * @return string
     */
    public function getTfaSecret(): string
    {
        return $this->tfaSecret;
    }

    /**
     * @param string $tfaSecret
     *
     * @return User
     */
    public function setTfaSecret(string $tfaSecret): User
    {
        $this->tfaSecret = $tfaSecret;

        return $this;
    }

    /**
     * @return int
     */
    public function getSkinTone(): int
    {
        return $this->skinTone;
    }

    /**
     * @param int $skinTone
     *
     * @return User
     */
    public function setSkinTone(int $skinTone): User
    {
        $this->skinTone = $skinTone;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateLastLogin(): ?DateTime
    {
        return $this->dateLastLogin;
    }

    /**
     * @param DateTime|null $dateLastLogin
     *
     * @return User
     */
    public function setDateLastLogin(?DateTime $dateLastLogin): User
    {
        $this->dateLastLogin = $dateLastLogin;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDatePrevLogin(): ?DateTime
    {
        return $this->datePrevLogin;
    }

    /**
     * @param DateTime|null $datePrevLogin
     *
     * @return User
     */
    public function setDatePrevLogin(?DateTime $datePrevLogin): User
    {
        $this->datePrevLogin = $datePrevLogin;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsDarkMode(): ?bool
    {
        if ($this->isDarkMode === null) {
            return null;
        }

        return (bool)$this->isDarkMode;
    }

    /**
     * @param bool $isDarkMode
     *
     * @return User
     */
    public function setIsDarkMode(bool $isDarkMode): User
    {
        $this->isDarkMode = (int)$isDarkMode;

        return $this;
    }

    /**
     * @return string
     */
    public function getJoinRef(): string
    {
        return $this->joinRef;
    }

    /**
     * @param string $joinRef
     *
     * @return User
     */
    public function setJoinRef(string $joinRef): User
    {
        $this->joinRef = substr($joinRef, 0, 254);

        return $this;
    }

    /**
     * @return bool
     */
    public function isResponded(): bool
    {
        return $this->isResponded;
    }

    /**
     * @param bool $isResponded
     *
     * @return User
     */
    public function setIsResponded(bool $isResponded): User
    {
        $this->isResponded = $isResponded;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOwner(): bool
    {
        return $this->isOwner;
    }

    /**
     * @param bool $isOwner
     *
     * @return User
     */
    public function setIsOwner(bool $isOwner): User
    {
        $this->isOwner = $isOwner;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * @param bool $isAdmin
     *
     * @return $this
     */
    public function setIsAdmin(bool $isAdmin): User
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassPlain(): string
    {
        return $this->passPlain;
    }

    /**
     * @param string $passPlain
     *
     * @return User
     */
    public function setPassPlain(string $passPlain): User
    {
        $this->passPlain = $passPlain;

        return $this;
    }
    /**
     * @return bool
     */

    public function getEditedTemplateSettings(): bool
    {
        return $this->editedTemplateSettings;
    }
    /**
     * @param bool $setting
     *
     * @return User
     */
    public function setEditedTemplateSettings(bool $setting): User
    {
        $this->editedTemplateSettings = (int)$setting;

        return $this;
    }
}
