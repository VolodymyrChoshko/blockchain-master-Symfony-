<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "template_link_params",
 *     prefix="tpa_",
 *     repository="Repository\TemplateLinkParamRepository",
 *     indexes={"tpa_tmp_id"={"tmpId"}},
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class TemplateLinkParam
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id;

    /**
     * @var int
     * @DB\Column("tmp_id")
     * @DB\Sql("BIGINT(20) DEFAULT NULL")
     * @DB\ForeignKey("Entity\Template", references="id", onDelete="CASCADE", onUpdate="RESTRICT")
     */
    protected $tmpId = 0;

    /**
     * @var string
     * @DB\Column("param")
     */
    protected $param = '';

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
     * @return TemplateLinkParam
     */
    public function setId(int $id): TemplateLinkParam
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
     * @return TemplateLinkParam
     */
    public function setTmpId(int $tmpId): TemplateLinkParam
    {
        $this->tmpId = $tmpId;

        return $this;
    }

    /**
     * @return string
     */
    public function getParam(): string
    {
        return $this->param;
    }

    /**
     * @param string $param
     *
     * @return TemplateLinkParam
     */
    public function setParam(string $param): TemplateLinkParam
    {
        $this->param = $param;

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
     * @return TemplateLinkParam
     */
    public function setDateCreated(DateTime $dateCreated): TemplateLinkParam
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
