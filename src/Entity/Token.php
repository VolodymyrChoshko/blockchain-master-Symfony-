<?php
namespace Entity;

use BlocksEdit\Database\ArrayEntity;
use BlocksEdit\Database\Annotations as DB;

/**
 * @DB\Table(
 *     "tokens",
 *     prefix="utk_",
 *     repository="Repository\TokensRepository",
 *     indexes={
 *          "utk_usr_id"={"usrId"},
 *          "utk_scope"={"scope"}
 *     },
 *     charSet="utf8mb4",
 *     collate="utf8mb4_unicode_ci"
 * )
 */
class Token extends ArrayEntity
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
     * @DB\Column("usr_id")
     * @DB\Sql("int DEFAULT NULL")
     */
    protected $usrId;

    /**
     * @var string
     * @DB\Column("token")
     * @DB\Sql("varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     * @DB\UniqueIndex()
     */
    protected $token;

    /**
     * @var string
     * @DB\Column("created_at", castTo="int")
     * @DB\Sql("varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $createdAt;

    /**
     * @var string
     * @DB\Column("scope")
     * @DB\Sql("varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''")
     */
    protected $scope;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('utk_');
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
     * @return Token
     */
    public function setId(int $id): Token
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getUsrId(): int
    {
        return $this->usrId;
    }

    /**
     * @param int $usrId
     *
     * @return Token
     */
    public function setUsrId(int $usrId): Token
    {
        $this->usrId = $usrId;

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
     * @return Token
     */
    public function setToken(string $token): Token
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedAt(): int
    {
        return (int)$this->createdAt;
    }

    /**
     * @param int $createdAt
     *
     * @return Token
     */
    public function setCreatedAt(int $createdAt): Token
    {
        $this->createdAt = (string)$createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     *
     * @return Token
     */
    public function setScope(string $scope): Token
    {
        $this->scope = $scope;

        return $this;
    }
}
