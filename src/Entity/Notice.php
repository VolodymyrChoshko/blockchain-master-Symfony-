<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "notices",
 *     prefix="ntc_",
 *     repository="Repository\NoticeRepository",
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class Notice
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("ntc_id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var string
     * @DB\Column("ntc_name")
     * @DB\Sql("VARCHAR(60) NOT NULL")
     */
    protected $name = '';

    /**
     * @var string
     * @DB\Column("ntc_content")
     * @DB\Sql("TEXT NOT NULL")
     */
    protected $content = '';

    /**
     * @var string
     * @DB\Column("ntc_location")
     * @DB\Sql("VARCHAR(60) NOT NULL")
     */
    protected $location = 'dashboard';

    /**
     * @var DateTime
     * @DB\Column("ntc_date_created")
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
     * @return Notice
     */
    public function setId(int $id): Notice
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
     * @return Notice
     */
    public function setName(string $name): Notice
    {
        $this->name = $name;

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
     * @return Notice
     */
    public function setContent(string $content): Notice
    {
        $this->content = $content;

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
     * @return Notice
     */
    public function setLocation(string $location): Notice
    {
        $this->location = $location;

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
     * @return Notice
     */
    public function setDateCreated(DateTime $dateCreated): Notice
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
