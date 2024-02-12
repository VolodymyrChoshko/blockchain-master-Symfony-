<?php
namespace Entity;

use BlocksEdit\Database\ArrayEntity;
use BlocksEdit\Database\Annotations as DB;

/**
 * @DB\Table(
 *     "organization",
 *     prefix="org_",
 *     repository="Repository\OrganizationsRepository",
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class Organization extends ArrayEntity
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("int NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var string
     * @DB\Column("name")
     * @DB\Sql("varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''")
     */
    protected $name = '';

    /**
     * @var string
     * @DB\Column("token")
     * @DB\Sql("char(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''")
     */
    protected $token;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('org_');
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
     * @return Organization
     */
    public function setId(int $id): Organization
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
     * @return Organization
     */
    public function setName(string $name): Organization
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return Organization
     */
    public function setToken(string $token): Organization
    {
        $this->token = $token;

        return $this;
    }
}
