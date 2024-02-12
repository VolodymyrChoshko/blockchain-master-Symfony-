<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "invitations",
 *     prefix="inv_",
 *     repository="Repository\InvitationsRepository",
 *     indexes={"inv_token"={"token"}},
 *     charSet="latin1"
 * )
 */
class Invitation
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("bigint NOT NULL AUTO_INCREMENT")
     */
    protected $id;

    /**
     * @var string
     * @DB\Column("type")
     * @DB\Sql("enum('template','organization') NOT NULL")
     */
    protected $type;

    /**
     * @var string
     * @DB\Column("name")
     * @DB\Sql("varchar(255) NOT NULL")
     */
    protected $name;

    /**
     * @var string
     * @DB\Column("email")
     * @DB\Sql("varchar(120) NOT NULL")
     */
    protected $email;

    /**
     * @var string
     * @DB\Column("job")
     * @DB\Sql("varchar(255) NOT NULL DEFAULT ''")
     */
    protected $job;

    /**
     * @var string
     * @DB\Column("org")
     * @DB\Sql("varchar(255) NOT NULL DEFAULT ''")
     */
    protected $org;

    /**
     * @var int
     * @DB\Column("inviter_id")
     * @DB\Sql("int NOT NULL")
     */
    protected $inviterId;

    /**
     * @var int
     * @DB\Column("accepted_id")
     * @DB\Sql("int NOT NULL DEFAULT '0'")
     */
    protected $acceptedId;

    /**
     * @var int
     * @DB\Column("tmp_id")
     * @DB\Sql("bigint DEFAULT NULL")
     */
    protected $tmpId;

    /**
     * @var int
     * @DB\Column("org_id")
     * @DB\Sql("int DEFAULT NULL")
     */
    protected $orgId;

    /**
     * @var int
     * @DB\Column("org_access")
     * @DB\Sql("tinyint unsigned NOT NULL DEFAULT '0'")
     */
    protected $orgAccess;

    /**
     * @var string
     * @DB\Column("token")
     * @DB\Sql("varchar(100) NOT NULL")
     */
    protected $token;

    /**
     * @var int
     * @DB\Column("is_accepted")
     * @DB\Sql("tinyint unsigned NOT NULL")
     */
    protected $isAccepted;

    /**
     * @var DateTime
     * @DB\Column("date_created")
     * @DB\Sql("datetime NOT NULL")
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Invitation
     */
    public function setId(int $id): Invitation
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Invitation
     */
    public function setType(string $type): Invitation
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Invitation
     */
    public function setName(string $name): Invitation
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Invitation
     */
    public function setEmail(string $email): Invitation
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param string $job
     *
     * @return Invitation
     */
    public function setJob(string $job): Invitation
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrg()
    {
        return $this->org;
    }

    /**
     * @param string $org
     *
     * @return Invitation
     */
    public function setOrg(string $org): Invitation
    {
        $this->org = $org;

        return $this;
    }

    /**
     * @return int
     */
    public function getInviterId()
    {
        return $this->inviterId;
    }

    /**
     * @param int $inviterId
     *
     * @return Invitation
     */
    public function setInviterId(int $inviterId): Invitation
    {
        $this->inviterId = $inviterId;

        return $this;
    }

    /**
     * @return int
     */
    public function getAcceptedId()
    {
        return $this->acceptedId;
    }

    /**
     * @param int $acceptedId
     *
     * @return Invitation
     */
    public function setAcceptedId(int $acceptedId): Invitation
    {
        $this->acceptedId = $acceptedId;

        return $this;
    }

    /**
     * @return int
     */
    public function getTmpId()
    {
        return $this->tmpId;
    }

    /**
     * @param int $tmpId
     *
     * @return Invitation
     */
    public function setTmpId(int $tmpId): Invitation
    {
        $this->tmpId = $tmpId;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrgId()
    {
        return $this->orgId;
    }

    /**
     * @param int $orgId
     *
     * @return Invitation
     */
    public function setOrgId(int $orgId): Invitation
    {
        $this->orgId = $orgId;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrgAccess()
    {
        return $this->orgAccess;
    }

    /**
     * @param int $orgAccess
     *
     * @return Invitation
     */
    public function setOrgAccess(int $orgAccess): Invitation
    {
        $this->orgAccess = $orgAccess;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return Invitation
     */
    public function setToken(string $token): Invitation
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsAccepted(): bool
    {
        return (bool)$this->isAccepted;
    }

    /**
     * @param bool $isAccepted
     *
     * @return Invitation
     */
    public function setIsAccepted(bool $isAccepted): Invitation
    {
        $this->isAccepted = (int)$isAccepted;

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
     * @return Invitation
     */
    public function setDateCreated(DateTime $dateCreated): Invitation
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
