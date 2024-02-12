<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "emails",
 *     prefix="dev_",
 *     repository="Repository\DevEmailRepository",
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class DevEmail
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var string
     * @DB\Column("to")
     * @DB\Sql("VARCHAR(500) NOT NULL")
     */
    protected $to = '';

    /**
     * @var string
     * @DB\Column("reply_to")
     * @DB\Sql("VARCHAR(500) NOT NULL DEFAULT ''")
     */
    protected $replyTo = '';

    /**
     * @var string
     * @DB\Column("from")
     * @DB\Sql("VARCHAR(500) NOT NULL")
     */
    protected $from = '';

    /**
     * @var string
     * @DB\Column("body")
     * @DB\Sql("TEXT NOT NULL")
     */
    protected $body = '';

    /**
     * @var string
     * @DB\Column("subject")
     * @DB\Sql("VARCHAR(500) NOT NULL")
     */
    protected $subject = '';

    /**
     * @var string
     * @DB\Column("content_type")
     * @DB\Sql("VARCHAR(20) NOT NULL")
     */
    protected $contentType = '';

    /**
     * @var string
     * @DB\Column("host_response")
     * @DB\Sql("VARCHAR(255) NOT NULL DEFAULT ''")
     */
    protected $hostResponse = '';

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
     * @return DevEmail
     */
    public function setId(int $id): DevEmail
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * @param string $to
     *
     * @return DevEmail
     */
    public function setTo(string $to): DevEmail
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return string
     */
    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    /**
     * @param string $replyTo
     *
     * @return DevEmail
     */
    public function setReplyTo(string $replyTo): DevEmail
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @param string $from
     *
     * @return DevEmail
     */
    public function setFrom(string $from): DevEmail
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return DevEmail
     */
    public function setBody(string $body): DevEmail
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return DevEmail
     */
    public function setSubject(string $subject): DevEmail
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     *
     * @return DevEmail
     */
    public function setContentType(string $contentType): DevEmail
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return string
     */
    public function getHostResponse(): string
    {
        return $this->hostResponse;
    }

    /**
     * @param string $hostResponse
     *
     * @return DevEmail
     */
    public function setHostResponse(string $hostResponse): DevEmail
    {
        $this->hostResponse = substr($hostResponse, 0, 255);

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
     * @return DevEmail
     */
    public function setDateCreated(DateTime $dateCreated): DevEmail
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
