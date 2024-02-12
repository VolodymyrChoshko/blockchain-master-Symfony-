<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "records",
 *     prefix="log_",
 *     repository="Repository\LogRecordRepository",
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class LogRecord
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) UNSIGNED NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var string
     * @DB\Column("channel")
     * @DB\Sql("VARCHAR(255) NOT NULL")
     */
    protected $channel = '';

    /**
     * @var int
     * @DB\Column("level")
     * @DB\Sql("SMALLINT(3) UNSIGNED NOT NULL")
     */
    protected $level = 0;

    /**
     * @var string
     * @DB\Column("message")
     * @DB\Sql("TEXT NOT NULL")
     */
    protected $message = '';

    /**
     * @var string
     * @DB\Column("context")
     * @DB\Sql("TEXT NOT NULL")
     */
    protected $context = '';

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
     * @return LogRecord
     */
    public function setId(int $id): LogRecord
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     *
     * @return LogRecord
     */
    public function setChannel(string $channel): LogRecord
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     *
     * @return LogRecord
     */
    public function setLevel(int $level): LogRecord
    {
        $this->level = $level;

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
     * @return LogRecord
     */
    public function setMessage(string $message): LogRecord
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @param string $context
     *
     * @return LogRecord
     */
    public function setContext(string $context): LogRecord
    {
        $this->context = $context;

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
     * @return LogRecord
     */
    public function setDateCreated(DateTime $dateCreated): LogRecord
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
