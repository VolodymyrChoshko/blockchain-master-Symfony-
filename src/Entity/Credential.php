<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;
use Exception;

/**
 * @DB\Table(
 *     "credentials",
 *     prefix="crd_",
 *     repository="Repository\CredentialsRepository",
 *     charSet="latin1"
 * )
 */
class Credential
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("int unsigned NOT NULL AUTO_INCREMENT")
     */
    protected $id;

    /**
     * @var string
     * @DB\Column("sealed")
     * @DB\Sql("blob NOT NULL")
     */
    protected $sealed;

    /**
     * @var string
     * @DB\Column("key")
     * @DB\Sql("blob NOT NULL")
     */
    protected $key;

    /**
     * @var string
     * @DB\Column("iv")
     * @DB\Sql("blob NOT NULL")
     */
    protected $iv;

    /**
     * @var string
     * @DB\Column("with")
     * @DB\Sql("varchar(60) NOT NULL DEFAULT ''")
     */
    protected $with;

    /**
     * @var string
     */
    protected $unsealed;

    /**
     * @var DateTime
     * @DB\Column("crd_date_created")
     * @DB\Sql("datetime NOT NULL DEFAULT CURRENT_TIMESTAMP")
     */
    protected $dateCreated;

    /**
     * @var DateTime
     * @DB\Column("crd_date_updated")
     * @DB\Sql("timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP")
     */
    protected $dateUpdated;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
        $this->dateUpdated = new DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Credential
     */
    public function setId(int $id): Credential
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getSealed()
    {
        return $this->sealed;
    }

    /**
     * @param string $sealed
     *
     * @return Credential
     */
    public function setSealed(string $sealed): Credential
    {
        $this->sealed = $sealed;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return Credential
     */
    public function setKey(string $key): Credential
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getIv()
    {
        return $this->iv;
    }

    /**
     * @param string $iv
     *
     * @return Credential
     */
    public function setIv(string $iv): Credential
    {
        $this->iv = $iv;

        return $this;
    }

    /**
     * @return string
     */
    public function getWith()
    {
        return $this->with;
    }

    /**
     * @param string $with
     *
     * @return Credential
     */
    public function setWith(string $with): Credential
    {
        $this->with = $with;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @param DateTime $dateCreated
     *
     * @return Credential
     * @throws Exception
     */
    public function setDateCreated(DateTime $dateCreated): Credential
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * @param DateTime $dateUpdated
     *
     * @return Credential
     * @throws Exception
     */
    public function setDateUpdated(DateTime $dateUpdated): Credential
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnsealed()
    {
        return $this->unsealed;
    }

    /**
     * @param string $unsealed
     *
     * @return Credential
     */
    public function setUnsealed(string $unsealed): Credential
    {
        $this->unsealed = $unsealed;

        return $this;
    }
}
