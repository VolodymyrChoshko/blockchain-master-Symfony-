<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "checklist_items",
 *     prefix="chk_",
 *     repository="Repository\ChecklistItemRepository",
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class ChecklistItem
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("BIGINT(12) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var Template
     * @DB\Column("tmp_id", nullable=false)
     * @DB\Join("Entity\Template", references="id")
     * @DB\Sql("bigint NOT NULL")
     * @DB\ForeignKey("Entity\Template", references="id", onDelete="CASCADE")
     */
    protected $template;

    /**
     * @var Email
     * @DB\Column("ema_id", nullable=false)
     * @DB\Join("Entity\Email", references="id")
     * @DB\Sql("bigint NOT NULL")
     * @DB\ForeignKey("Entity\Email", references="id", onDelete="CASCADE")
     */
    protected $email;

    /**
     * @var string
     * @DB\Column("key", nullable=false)
     * @DB\Sql("VARCHAR(50) NOT NULL DEFAULT ''")
     */
    protected $key;

    /**
     * @var string
     * @DB\Column("title", nullable=false)
     * @DB\Sql("VARCHAR(500) NOT NULL")
     */
    protected $title;

    /**
     * @var string
     * @DB\Column("description", nullable=false)
     * @DB\Sql("VARCHAR(500) NOT NULL")
     */
    protected $description;

    /**
     * @var bool
     * @DB\Column("is_template", nullable=false)
     * @DB\Sql("TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'")
     */
    protected $isTemplate = false;

    /**
     * @var bool
     * @DB\Column("is_checked", nullable=false)
     * @DB\Sql("TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'")
     */
    protected $isChecked = false;

    /**
     * @var User|null
     * @DB\Column("checked_usr_id", nullable=true)
     * @DB\Join("Entity\User", references="id")
     * @DB\Sql("int DEFAULT NULL")
     * @DB\ForeignKey("Entity\User", references="id", onDelete="CASCADE")
     */
    protected $checkedUser = null;

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
     * @return ChecklistItem
     */
    public function setId(int $id): ChecklistItem
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Template
     */
    public function getTemplate(): Template
    {
        return $this->template;
    }

    /**
     * @param Template $template
     *
     * @return ChecklistItem
     */
    public function setTemplate(Template $template): ChecklistItem
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * @param Email $email
     *
     * @return ChecklistItem
     */
    public function setEmail(Email $email): ChecklistItem
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return ChecklistItem
     */
    public function setKey(string $key): ChecklistItem
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return ChecklistItem
     */
    public function setTitle(string $title): ChecklistItem
    {
        $this->title = $title;

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
     * @return ChecklistItem
     */
    public function setDescription(string $description): ChecklistItem
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    /**
     * @param bool $isTemplate
     *
     * @return ChecklistItem
     */
    public function setIsTemplate(bool $isTemplate): ChecklistItem
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isChecked(): bool
    {
        return $this->isChecked;
    }

    /**
     * @param bool $isChecked
     *
     * @return ChecklistItem
     */
    public function setIsChecked(bool $isChecked): ChecklistItem
    {
        $this->isChecked = $isChecked;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getCheckedUser(): ?User
    {
        return $this->checkedUser;
    }

    /**
     * @param User|null $checkedUser
     *
     * @return ChecklistItem
     */
    public function setCheckedUser(?User $checkedUser): ChecklistItem
    {
        $this->checkedUser = $checkedUser;

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
     * @return ChecklistItem
     */
    public function setDateCreated(DateTime $dateCreated): ChecklistItem
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
