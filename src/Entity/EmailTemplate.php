<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;
use InvalidArgumentException;

/**
 * @DB\Table(
 *     "email_templates",
 *     prefix="emt_",
 *     repository="Repository\EmailTemplateRepository",
 *     indexes={"emt_ema_id"={"emaId"}},
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class EmailTemplate
{
    const LOCATION_DISK = 'disk';
    const LOCATION_DATABASE = 'database';
    const LOCATION_BUILDER = 'builder';

    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var string
     * @DB\Column("name")
     * @DB\Sql("VARCHAR(40) NOT NULL")
     * @DB\UniqueIndex()
     */
    protected $name = '';

    /**
     * @var string
     * @DB\Column("subject")
     * @DB\Sql("VARCHAR(255) NOT NULL")
     */
    protected $subject = '';

    /**
     * @var string
     * @DB\Column("location")
     * @DB\Sql("ENUM('disk', 'database', 'builder') DEFAULT 'disk'")
     */
    protected $location = '';

    /**
     * @var int
     * @DB\Column("ema_id")
     * @DB\Sql("BIGINT(20) DEFAULT NULL")
     * @DB\ForeignKey("Entity\Email", references="id")
     */
    protected $emaId = 0;

    /**
     * @var string
     * @DB\Column("content")
     * @DB\Sql("MEDIUMTEXT NOT NULL")
     */
    protected $content = '';

    /**
     * @var string
     * @DB\Column("filename")
     * @DB\Sql("VARCHAR(60) NOT NULL DEFAULT ''")
     */
    protected $filename = '';

    /**
     * @var string
     * @DB\Column("variables")
     * @DB\Sql("VARCHAR(500) NOT NULL DEFAULT ''")
     */
    protected $variables = '';

    /**
     * @var int
     * @DB\Column("no_send_check")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT 0")
     */
    protected $noSendCheck = 0;

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
     * @return EmailTemplate
     */
    public function setId(int $id): EmailTemplate
    {
        $this->id = $id;

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
     * @return EmailTemplate
     */
    public function setName(string $name): EmailTemplate
    {
        $this->name = $name;

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
     * @return EmailTemplate
     */
    public function setSubject(string $subject): EmailTemplate
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     *
     * @return EmailTemplate
     */
    public function setLocation(string $location): EmailTemplate
    {
        if (!in_array($location, [self::LOCATION_BUILDER, self::LOCATION_DATABASE, self::LOCATION_DISK])) {
            throw new InvalidArgumentException('Invalid email template location ' . $location);
        }
        $this->location = $location;

        return $this;
    }

    /**
     * @return int
     */
    public function getEmaId(): ?int
    {
        return $this->emaId;
    }

    /**
     * @param int|null $emaId
     *
     * @return EmailTemplate
     */
    public function setEmaId(?int $emaId): EmailTemplate
    {
        $this->emaId = $emaId;

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
     * @return EmailTemplate
     */
    public function setContent(string $content): EmailTemplate
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     *
     * @return EmailTemplate
     */
    public function setFilename(string $filename): EmailTemplate
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return string
     */
    public function getVariables(): string
    {
        return $this->variables;
    }

    /**
     * @param string $variables
     *
     * @return EmailTemplate
     */
    public function setVariables(string $variables): EmailTemplate
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * @return bool
     */
    public function getNoSendCheck(): bool
    {
        return (bool)$this->noSendCheck;
    }

    /**
     * @param bool $noSendCheck
     *
     * @return EmailTemplate
     */
    public function setNoSendCheck(bool $noSendCheck): EmailTemplate
    {
        $this->noSendCheck = (int)$noSendCheck;

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
     * @return EmailTemplate
     */
    public function setDateCreated(DateTime $dateCreated): EmailTemplate
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
