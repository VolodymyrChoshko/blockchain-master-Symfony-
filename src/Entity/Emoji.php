<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "emojis",
 *     prefix="emj_",
 *     repository="Repository\EmojiRepository",
 *     uniqueIndexes={
 *          "emj_uuid_idx"={"uuid"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class Emoji
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
     * @var User
     * @DB\Column("usr_id", nullable=false)
     * @DB\Join("Entity\User", references="id")
     * @DB\Sql("int NOT NULL")
     * @DB\ForeignKey("Entity\User", references="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var Comment
     * @DB\Column("comment_id", nullable=false)
     * @DB\Sql("BIGINT(12) NOT NULL")
     * @DB\ManyToOne("Entity\Comment", inversedBy="emojis")
     */
    protected $comment;

    /**
     * @var string
     * @DB\Column("code", nullable=false)
     * @DB\Sql("VARCHAR(10) NOT NULL")
     */
    protected $code;

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
     * @return Emoji
     */
    public function setId(int $id): Emoji
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
     * @return Emoji
     */
    public function setUuid(string $uuid): Emoji
    {
        $this->uuid = $uuid;

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
     * @return Emoji
     */
    public function setUser(User $user): Emoji
    {
        $this->user = $user;

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
     * @return Emoji
     */
    public function setComment(Comment $comment): Emoji
    {
        $this->comment = $comment;

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
     * @return Emoji
     */
    public function setCode(string $code): Emoji
    {
        $this->code = $code;

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
     * @return Emoji
     */
    public function setDateCreated(DateTime $dateCreated): Emoji
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
