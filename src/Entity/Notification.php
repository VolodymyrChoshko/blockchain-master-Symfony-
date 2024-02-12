<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "notifications",
 *     prefix="not_",
 *     repository="Repository\NotificationRepository",
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class Notification
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("BIGINT(12) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var User
     * @DB\Column("to_id", nullable=false)
     * @DB\Join("Entity\User", references="id")
     * @DB\Sql("int NOT NULL")
     * @DB\ForeignKey("Entity\User", references="id", onDelete="CASCADE")
     */
    protected $to;

    /**
     * @var User
     * @DB\Column("from_id", nullable=true)
     * @DB\Join("Entity\User", references="id")
     * @DB\Sql("int DEFAULT NULL")
     * @DB\ForeignKey("Entity\User", references="id", onDelete="CASCADE")
     */
    protected $from;

    /**
     * @var Comment
     * @DB\Column("comment_id", nullable=true)
     * @DB\Join("Entity\Comment", references="id")
     * @DB\Sql("BIGINT(12) DEFAULT NULL")
     * @DB\ForeignKey("Entity\Comment", references="id", onDelete="CASCADE")
     */
    protected $comment;

    /**
     * @var Mention
     * @DB\Column("mention_id", nullable=true)
     * @DB\Join("Entity\Mention", references="id")
     * @DB\Sql("BIGINT(12) DEFAULT NULL")
     * @DB\ForeignKey("Entity\Mention", references="id", onDelete="CASCADE")
     */
    protected $mention;

    /**
     * @var string
     * @DB\Column("action", nullable=false)
     * @DB\Sql("VARCHAR(60) NOT NULL")
     */
    protected $action;

    /**
     * @var string
     * @DB\Column("message", nullable=false)
     * @DB\Sql("VARCHAR(2000) NOT NULL DEFAULT ''")
     */
    protected $message = '';

    /**
     * @var string
     * @DB\Column("status", nullable=false)
     * @DB\Sql("ENUM('unread', 'read') NOT NULL DEFAULT 'unread'")
     */
    protected $status = 'unread';

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
     * @return Notification
     */
    public function setId(int $id): Notification
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return User
     */
    public function getTo(): User
    {
        return $this->to;
    }

    /**
     * @param User $to
     *
     * @return Notification
     */
    public function setTo(User $to): Notification
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getFrom(): ?User
    {
        return $this->from;
    }

    /**
     * @param User|null $from
     *
     * @return Notification
     */
    public function setFrom(?User $from): Notification
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return Comment|null
     */
    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    /**
     * @param Comment|null $comment
     *
     * @return Notification
     */
    public function setComment(?Comment $comment): Notification
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return Mention|null
     */
    public function getMention(): ?Mention
    {
        return $this->mention;
    }

    /**
     * @param Mention|null $mention
     *
     * @return Notification
     */
    public function setMention(?Mention $mention): Notification
    {
        $this->mention = $mention;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return Notification
     */
    public function setAction(string $action): Notification
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return Notification
     */
    public function setMessage(string $message): Notification
    {
        $this->message = $message;

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
     * @return Notification
     */
    public function setStatus(string $status): Notification
    {
        $this->status = $status;

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
     * @return Notification
     */
    public function setDateCreated(DateTime $dateCreated): Notification
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
