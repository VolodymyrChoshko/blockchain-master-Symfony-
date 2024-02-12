<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "section_libraries",
 *     prefix="sel_",
 *     repository="Repository\SectionLibraryRepository",
 *     indexes={
 *      "sel_tmp_id"={"tmpId"},
 *      "sel_pin_group_id"={"pinGroup"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class SectionLibrary
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var int
     * @DB\Column("tmp_id")
     * @DB\Sql("BIGINT(20) DEFAULT NULL")
     * @DB\ForeignKey("Entity\Template", references="id", onDelete="RESTRICT", onUpdate="RESTRICT")
     */
    protected $tmpId = 0;

    /**
     * @var PinGroup|null
     * @DB\Column("pin_group_id", nullable=true)
     * @DB\Join("Entity\PinGroup", references="id")
     * @DB\Sql("INT(11) DEFAULT NULL")
     */
    protected $pinGroup = null;

    /**
     * @var int
     * @DB\Column("desktop_id")
     * @DB\Sql("INT(11) NOT NULL DEFAULT 0")
     */
    protected $desktopId = 0;

    /**
     * @var int
     * @DB\Column("tmp_version")
     * @DB\Sql("int NOT NULL DEFAULT '1'")
     */
    protected $tmpVersion = 1;

    /**
     * @var string
     * @DB\Column("name")
     * @DB\Sql("VARCHAR(60) NOT NULL")
     */
    protected $name = '';

    /**
     * @var string
     * @DB\Column("html")
     * @DB\Sql("LONGTEXT NOT NULL")
     */
    protected $html = '';

    /**
     * @var string
     * @DB\Column("group")
     * @DB\Sql("VARCHAR(60) NOT NULL")
     */
    protected $group = '';

    /**
     * @var string
     * @DB\Column("block")
     * @DB\Sql("VARCHAR(60) NOT NULL")
     */
    protected $block = '';

    /**
     * @var string
     * @DB\Column("thumbnail")
     * @DB\Sql("VARCHAR(60) NOT NULL")
     */
    protected $thumbnail = '';

    /**
     * @var int
     * @DB\Column("is_mobile")
     * @DB\Sql("TINYINT(1) NOT NULL DEFAULT '0'")
     */
    protected $isMobile = 0;

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
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return SectionLibrary
     */
    public function setId(int $id): SectionLibrary
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getTmpId(): int
    {
        return $this->tmpId;
    }

    /**
     * @param int $tmpId
     *
     * @return SectionLibrary
     */
    public function setTmpId(int $tmpId): SectionLibrary
    {
        $this->tmpId = $tmpId;

        return $this;
    }

    /**
     * @return PinGroup|null
     */
    public function getPinGroup(): ?PinGroup
    {
        return $this->pinGroup;
    }

    /**
     * @param PinGroup|null $pinGroup
     *
     * @return SectionLibrary
     */
    public function setPinGroup(?PinGroup $pinGroup): SectionLibrary
    {
        $this->pinGroup = $pinGroup;

        return $this;
    }

    /**
     * @return int
     */
    public function getDesktopId(): int
    {
        return $this->desktopId;
    }

    /**
     * @param int $desktopId
     *
     * @return SectionLibrary
     */
    public function setDesktopId(int $desktopId): SectionLibrary
    {
        $this->desktopId = $desktopId;

        return $this;
    }

    /**
     * @return int
     */
    public function getTmpVersion(): int
    {
        return $this->tmpVersion;
    }

    /**
     * @param int $tmpVersion
     *
     * @return SectionLibrary
     */
    public function setTmpVersion(int $tmpVersion): SectionLibrary
    {
        $this->tmpVersion = $tmpVersion;

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
     * @return SectionLibrary
     */
    public function setName(string $name): SectionLibrary
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * @param string $html
     *
     * @return SectionLibrary
     */
    public function setHtml(string $html): SectionLibrary
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @param string $group
     *
     * @return SectionLibrary
     */
    public function setGroup(string $group): SectionLibrary
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return string
     */
    public function getBlock(): string
    {
        return $this->block;
    }

    /**
     * @param string $block
     *
     * @return SectionLibrary
     */
    public function setBlock(string $block): SectionLibrary
    {
        $this->block = $block;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     *
     * @return SectionLibrary
     */
    public function setThumbnail(string $thumbnail): SectionLibrary
    {
        $this->thumbnail = ltrim($thumbnail, '/');

        return $this;
    }

    /**
     * @return bool
     */
    public function isMobile(): bool
    {
        return (bool)$this->isMobile;
    }

    /**
     * @param bool $isMobile
     *
     * @return SectionLibrary
     */
    public function setIsMobile(bool $isMobile): SectionLibrary
    {
        $this->isMobile = (int)$isMobile;

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
     * @return SectionLibrary
     */
    public function setDateCreated(DateTime $dateCreated): SectionLibrary
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
