<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "comments",
 *     prefix="cmt_",
 *     repository="Repository\CommentRepository",
 *     indexes={
 *          "cmt_parent_idx"={"parent"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class Comment
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("BIGINT(12) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var Email
     * @DB\Column("ema_id", nullable=false)
     * @DB\Join("Entity\Email", references="id")
     * @DB\Sql("bigint NOT NULL")
     * @DB\ForeignKey("Entity\Email", references="id", onDelete="CASCADE")
     */
    protected $email;

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
     * @DB\Column("parent_id", nullable=true)
     * @DB\Join("Entity\Comment", references="id")
     * @DB\Sql("BIGINT(12) DEFAULT NULL")
     * @DB\ForeignKey("Entity\Comment", references="id", onDelete="CASCADE")
     */
    protected $parent;

    /**
     * @var Emoji[]
     * @DB\OneToMany("Entity\Emoji", mappedBy="comment")
     */
    protected $emojis = [];

    /**
     * @var string
     * @DB\Column("content", nullable=false)
     * @DB\Sql("VARCHAR(10000) NOT NULL")
     */
    protected $content;

    /**
     * @var string
     * @DB\Column("status", nullable=false)
     * @DB\Sql("VARCHAR(255) NOT NULL DEFAULT ''")
     */
    protected $status = '';

    /**
     * @var array
     * @DB\Column("mentions", nullable=false, json=true)
     * @DB\Sql("VARCHAR(2000) NOT NULL DEFAULT '[]'")
     */
    protected $mentions = [];

    /**
     * @var int
     * @DB\Column("block_id", nullable=false)
     * @DB\Sql("SMALLINT(3) UNSIGNED NOT NULL DEFAULT '0'")
     */
    protected $blockId = 0;

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
     * @return Comment
     */
    public function setId(int $id): Comment
    {
        $this->id = $id;

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
     * @return Comment
     */
    public function setEmail(Email $email): Comment
    {
        $this->email = $email;

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
     * @return Comment
     */
    public function setUser(User $user): Comment
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Comment|null
     */
    public function getParent(): ?Comment
    {
        return $this->parent;
    }

    /**
     * @param Comment|null $parent
     *
     * @return Comment
     */
    public function setParent(?Comment $parent): Comment
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Comment
     */
    public function setContent(string $content): Comment
    {
        $this->content = $content;

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
     * @return Comment
     */
    public function setStatus(string $status): Comment
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return array
     */
    public function getEmojis(): array
    {
        return $this->emojis;
    }

    /**
     * @param array $emojis
     *
     * @return Comment
     */
    public function setEmojis(array $emojis): Comment
    {
        $this->emojis = $emojis;

        return $this;
    }

    /**
     * @param Emoji $emoji
     *
     * @return $this
     */
    public function addEmoji(Emoji $emoji): Comment
    {
        $this->emojis[] = $emoji;

        return $this;
    }

    /**
     * @param Emoji $emoji
     *
     * @return $this
     */
    public function removeEmoji(Emoji $emoji): Comment
    {
        foreach($this->emojis as $i => $e) {
            if ($e->getId() === $emoji->getId()) {
                unset($this->emojis[$i]);
                break;
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getMentions(): array
    {
        return array_map(function($item) {
            return (array)$item;
        }, $this->mentions);
    }

    /**
     * @param array $mentions
     *
     * @return Comment
     */
    public function setMentions(array $mentions): Comment
    {
        $this->mentions = $mentions;

        return $this;
    }

    /**
     * @return int
     */
    public function getBlockId(): int
    {
        return $this->blockId;
    }

    /**
     * @param int $blockId
     *
     * @return Comment
     */
    public function setBlockId(int $blockId): Comment
    {
        $this->blockId = $blockId;

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
     * @return Comment
     */
    public function setDateCreated(DateTime $dateCreated): Comment
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
