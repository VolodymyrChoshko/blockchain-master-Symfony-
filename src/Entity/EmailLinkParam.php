<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "email_link_params",
 *     prefix="epa_",
 *     repository="Repository\EmailLinkParamRepository",
 *     indexes={
 *          "epa_ema_id"={"emaId"},
 *          "epa_tpa_id"={"tpaId"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class EmailLinkParam
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
     * @DB\Column("ema_id")
     * @DB\Sql("BIGINT(20) DEFAULT NULL")
     * @DB\ForeignKey("Entity\Email", references="id")
     */
    protected $emaId = 0;

    /**
     * @var int
     * @DB\Column("tpa_id")
     * @DB\Sql("INT(11) NOT NULL")
     * @DB\ForeignKey("Entity\TemplateLinkParam", references="id")
     */
    protected $tpaId = 0;

    /**
     * @var string
     * @DB\Column("epa_value")
     * @DB\Sql("VARCHAR(120) NOT NULL DEFAULT ''")
     */
    protected $value = '';

    /**
     * @var DateTime
     * @DB\Column("epa_date_created")
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
     * @return EmailLinkParam
     */
    public function setId(int $id): EmailLinkParam
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getEmaId(): int
    {
        return $this->emaId;
    }

    /**
     * @param int $emaId
     *
     * @return EmailLinkParam
     */
    public function setEmaId(int $emaId): EmailLinkParam
    {
        $this->emaId = $emaId;

        return $this;
    }

    /**
     * @return int
     */
    public function getTpaId(): int
    {
        return $this->tpaId;
    }

    /**
     * @param int $tpaId
     *
     * @return EmailLinkParam
     */
    public function setTpaId(int $tpaId): EmailLinkParam
    {
        $this->tpaId = $tpaId;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return EmailLinkParam
     */
    public function setValue(string $value): EmailLinkParam
    {
        $this->value = $value;

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
     * @return EmailLinkParam
     */
    public function setDateCreated(DateTime $dateCreated): EmailLinkParam
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
