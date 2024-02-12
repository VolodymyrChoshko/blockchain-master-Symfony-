<?php
namespace Entity;

use BlocksEdit\Database\ArrayEntity;
use BlocksEdit\Database\Annotations as DB;

/**
 * @DB\Table(
 *     "sections",
 *     prefix="sec_",
 *     repository="Repository\SectionsRepository",
 *     indexes={
 *          "sec_tmp_id"={"tmpId"},
 *          "sec_tmp_version"={"tmpVersion"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class Section extends ArrayEntity
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("int NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var int
     * @DB\Column("nr")
     * @DB\Sql("int DEFAULT NULL")
     */
    protected $nr;

    /**
     * @var int
     * @DB\Column("tmp_id")
     * @DB\Sql("int DEFAULT NULL")
     */
    protected $tmpId;

    /**
     * @var string
     * @DB\Column("html")
     * @DB\Sql("longtext COLLATE utf8mb4_unicode_ci")
     */
    protected $html;

    /**
     * @var string
     * @DB\Column("title")
     * @DB\Sql("varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $title;

    /**
     * @var string
     * @DB\Column("title")
     * @DB\Sql("varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $style;

    /**
     * @var string
     * @DB\Column("block")
     * @DB\Sql("varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $block;

    /**
     * @var string
     * @DB\Column("tmp")
     * @DB\Sql("varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL")
     */
    protected $tmp;

    /**
     * @var int
     * @DB\Column("tmp_version")
     * @DB\Sql("int NOT NULL DEFAULT '0'")
     */
    protected $tmpVersion;

    /**
     * @var string
     * @DB\Column("thumb")
     * @DB\Sql("varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $thumb;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('sec_');
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
     * @return Section
     */
    public function setId(int $id): Section
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getNr(): int
    {
        return $this->nr;
    }

    /**
     * @param int $nr
     *
     * @return Section
     */
    public function setNr(int $nr): Section
    {
        $this->nr = $nr;

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
     * @return Section
     */
    public function setTmpId(int $tmpId): Section
    {
        $this->tmpId = $tmpId;

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
     * @return Section
     */
    public function setHtml(string $html): Section
    {
        $this->html = $html;

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
     * @return Section
     */
    public function setTitle(string $title): Section
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param string $style
     *
     * @return Section
     */
    public function setStyle(string $style): Section
    {
        $this->style = $style;

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
     * @return Section
     */
    public function setBlock(string $block): Section
    {
        $this->block = $block;

        return $this;
    }

    /**
     * @return string
     */
    public function getTmp(): string
    {
        return $this->tmp;
    }

    /**
     * @param string $tmp
     *
     * @return Section
     */
    public function setTmp(string $tmp): Section
    {
        $this->tmp = $tmp;

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
     * @return Section
     */
    public function setTmpVersion(int $tmpVersion): Section
    {
        $this->tmpVersion = $tmpVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumb(): string
    {
        return $this->thumb;
    }

    /**
     * @param string $thumb
     *
     * @return Section
     */
    public function setThumb(string $thumb): Section
    {
        $this->thumb = $thumb;

        return $this;
    }
}
