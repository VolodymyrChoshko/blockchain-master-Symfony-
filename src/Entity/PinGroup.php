<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "pin_groups",
 *     prefix="png_",
 *     repository="Repository\PinGroupRepository",
 *     indexes={"sel_tmp_id"={"tmpId"}},
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class PinGroup
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("INT(11) NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var Template
     * @DB\Column("tmp_id")
     * @DB\Join("Entity\Template", references="id")
     * @DB\Sql("bigint NOT NULL")
     */
    protected $template;

    /**
     * @var string
     * @DB\Column("name")
     * @DB\Sql("VARCHAR(60) NOT NULL")
     */
    protected $name = '';

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
     * @return PinGroup
     */
    public function setId(int $id): PinGroup
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Template
     */
    public function getTemplate(): Template
    {
        return $this->template;
    }

    /**
     * @param Template $template
     *
     * @return PinGroup
     */
    public function setTemplate(Template $template): PinGroup
    {
        $this->template = $template;

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
     * @return PinGroup
     */
    public function setName(string $name): PinGroup
    {
        $this->name = $name;

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
     * @return PinGroup
     */
    public function setDateCreated(DateTime $dateCreated): PinGroup
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
