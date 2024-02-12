<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use DateTime;

/**
 * @DB\Table(
 *     "onboarding_sent",
 *     prefix="obs_",
 *     repository="Repository\OnboardingSentRepository",
 *     indexes={"obs_email"={"email"}},
 *     charSet="latin1"
 * )
 */
class OnboardingSent
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
     * @DB\Column("view")
     * @DB\Sql("varchar(120) NOT NULL")
     */
    protected $view;

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
     * @return OnboardingSent
     */
    public function setId(int $id): OnboardingSent
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
     * @return OnboardingSent
     */
    public function setEmail(string $email): OnboardingSent
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * @param string $view
     *
     * @return OnboardingSent
     */
    public function setView(string $view): OnboardingSent
    {
        $this->view = $view;

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
     * @return OnboardingSent
     */
    public function setDateCreated(DateTime $dateCreated): OnboardingSent
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
