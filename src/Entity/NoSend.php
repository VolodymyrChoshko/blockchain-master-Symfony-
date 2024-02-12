<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "no_send",
 *     prefix="nos_",
 *     repository="Repository\NoSendRepository",
 *     indexes={"nos_email"={"email"}},
 *     charSet="latin1"
 * )
 */
class NoSend
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
     * @DB\Column("email")
     * @DB\Sql("varchar(120) NOT NULL")
     */
    protected $email;

    /**
     * @var string
     * @DB\Column("reason")
     * @DB\Sql("varchar(30) NOT NULL")
     */
    protected $reason;

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
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return NoSend
     */
    public function setId(int $id): NoSend
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return NoSend
     */
    public function setEmail(string $email): NoSend
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     *
     * @return NoSend
     */
    public function setReason(string $reason): NoSend
    {
        $this->reason = $reason;

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
     * @return NoSend
     */
    public function setDateCreated(DateTime $dateCreated): NoSend
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
