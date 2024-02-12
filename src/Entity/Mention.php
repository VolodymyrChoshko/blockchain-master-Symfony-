<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "mentions",
 *     prefix="men_",
 *     repository="Repository\MentionRepository",
 *     uniqueIndexes={
 *          "men_uuid_idx"={"uuid"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class Mention
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("BIGINT(12) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var string
     * @DB\Column("uuid", nullable=false)
     * @DB\Sql("CHAR(36) NOT NULL")
     */
    protected $uuid;

    /**
     * @var Comment
     * @DB\Column("comment_id", nullable=true)
     * @DB\Join("Entity\Comment", references="id")
     * @DB\Sql("BIGINT(12) DEFAULT NULL")
     * @DB\ForeignKey("Entity\Comment", references="id", onDelete="CASCADE")
     */
    protected $comment;

    /**
     * @var User
     * @DB\Column("usr_id", nullable=false)
     * @DB\Join("Entity\User", references="id")
     * @DB\Sql("int NOT NULL")
     * @DB\ForeignKey("Entity\User", references="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var bool
     * @DB\Column("is_notified", nullable=false)
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT '0'")
     */
    protected $isNotified = false;

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
     * @return Mention
     */
    public function setId(int $id): Mention
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return Mention
     */
    public function setUuid(string $uuid): Mention
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Comment
     */
    public function getComment(): Comment
    {
        return $this->comment;
    }

    /**
     * @param Comment $comment
     *
     * @return Mention
     */
    public function setComment(Comment $comment): Mention
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Mention
     */
    public function setUser(User $user): Mention
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNotified(): bool
    {
        return $this->isNotified;
    }

    /**
     * @param bool $isNotified
     *
     * @return Mention
     */
    public function setIsNotified(bool $isNotified): Mention
    {
        $this->isNotified = $isNotified;

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
     * @return Mention
     */
    public function setDateCreated(DateTime $dateCreated): Mention
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
