<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;
use Exception;

/**
 * @DB\Table(
 *     "template_sources",
 *     prefix="tns_",
 *     repository="Repository\TemplateSourcesRepository",
 *     indexes={
 *          "tns_tmp_id"={"tmpId"},
 *          "tns_src_id"={"srcId"}
 *     },
 *     charSet="latin1"
 * )
 */
class TemplateSource
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("tns_id")
     * @DB\Sql("int unsigned NOT NULL AUTO_INCREMENT")
     */
    protected $id;

    /**
     * @var int
     * @DB\Column("tns_tmp_id")
     * @DB\Sql("bigint NOT NULL")
     * @DB\ForeignKey("Entity\Template", references="id", onDelete="CASCADE", onUpdate="RESTRICT")
     */
    protected $tmpId;

    /**
     * @var int
     * @DB\Column("tns_src_id")
     * @DB\Sql("int unsigned NOT NULL")
     * @DB\ForeignKey("Entity\Source", references="id", onDelete="CASCADE", onUpdate="RESTRICT")
     */
    protected $srcId;

    /**
     * @var int
     * @DB\Column("tns_enabled")
     * @DB\Sql("tinyint unsigned NOT NULL DEFAULT '1'")
     */
    protected $isEnabled = 1;

    /**
     * @var string
     * @DB\Column("tns_home_dir")
     * @DB\Sql("varchar(255) NOT NULL DEFAULT '/'")
     */
    protected $homeDir = '/';

    /**
     * @var DateTime
     * @DB\Column("tns_date_created")
     * @DB\Sql("datetime NOT NULL DEFAULT CURRENT_TIMESTAMP")
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
     * @return TemplateSource
     */
    public function setId(int $id): TemplateSource
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getTmpId(): ?int
    {
        return $this->tmpId;
    }

    /**
     * @param int $tmpId
     *
     * @return TemplateSource
     */
    public function setTmpId(int $tmpId): TemplateSource
    {
        $this->tmpId = $tmpId;

        return $this;
    }

    /**
     * @return int
     */
    public function getSrcId(): ?int
    {
        return $this->srcId;
    }

    /**
     * @param int $srcId
     *
     * @return TemplateSource
     */
    public function setSrcId(int $srcId): TemplateSource
    {
        $this->srcId = $srcId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     *
     * @return TemplateSource
     */
    public function setIsEnabled(bool $isEnabled): TemplateSource
    {
        $this->isEnabled = (int)$isEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getHomeDir(): ?string
    {
        return $this->homeDir;
    }

    /**
     * @param string $homeDir
     *
     * @return TemplateSource
     */
    public function setHomeDir(string $homeDir): TemplateSource
    {
        $this->homeDir = $homeDir;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreated(): ?DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param DateTime $dateCreated
     *
     * @return TemplateSource
     * @throws Exception
     */
    public function setDateCreated(DateTime $dateCreated): TemplateSource
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
