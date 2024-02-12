<?php
namespace Entity;

use BlocksEdit\Database\ArrayEntity;
use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "folders",
 *     prefix="fld_",
 *     repository="Repository\FoldersRepository",
 *     charSet="latin1"
 * )
 */
class Folder extends ArrayEntity
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("int unsigned NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var int
     * @DB\Column("tmp_id")
     * @DB\Sql("bigint DEFAULT NULL")
     */
    protected $tmpId;

    /**
     * @var string
     * @DB\Column("name")
     * @DB\Sql("varchar(60) NOT NULL")
     */
    protected $name = '';

    /**
     * @var int|null
     * @DB\Column("parent_id")
     * @DB\Sql("int DEFAULT NULL")
     */
    protected $parentId = null;

    /**
     * @var DateTime
     * @DB\Column("updated_at")
     * @DB\Sql("datetime NOT NULL")
     */
    protected $updatedAt;

    /**
     * @var DateTime
     * @DB\Column("created_at")
     * @DB\Sql("datetime NOT NULL")
     */
    protected $createdAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('fld_');

        $this->createdAt = new DateTime();
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
     * @return Folder
     */
    public function setId(int $id): Folder
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
     * @return Folder
     */
    public function setTmpId(int $tmpId): Folder
    {
        $this->tmpId = $tmpId;

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
     * @return Folder
     */
    public function setName(string $name): Folder
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    /**
     * @param int|null $parentId
     *
     * @return Folder
     */
    public function setParentId(?int $parentId): Folder
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     *
     * @return Folder
     */
    public function setUpdatedAt(DateTime $updatedAt): Folder
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return Folder
     */
    public function setCreatedAt(DateTime $createdAt): Folder
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
