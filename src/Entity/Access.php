<?php
namespace Entity;

use BlocksEdit\Database\ArrayEntity;
use BlocksEdit\Database\Annotations as DB;

/**
 * @DB\Table(
 *     "access",
 *     prefix="acc_",
 *     repository="Repository\AccessRepository",
 *     indexes={
 *          "acc_usr_id"={"user"},
 *          "acc_tmp_id"={"template"}
 *     }
 * )
 */
class Access extends ArrayEntity
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     * @DB\Sql("bigint NOT NULL AUTO_INCREMENT")
     */
    protected $id = 0;

    /**
     * @var User
     * @DB\Column("usr_id")
     * @DB\Join("Entity\User", references="id")
     * @DB\Sql("int NOT NULL")
     */
    protected $user;

    /**
     * @var Template
     * @DB\Column("tmp_id")
     * @DB\Join("Entity\Template", references="id")
     * @DB\Sql("bigint NOT NULL")
     */
    protected $template;

    /**
     * @var int
     * @DB\Column("tmp_collapsed")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $tmpCollapsed = 0;

    /**
     * @var int
     * @DB\Column("archive_opened")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $archiveOpened = 0;

    /**
     * @var int
     * @DB\Column("responded")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $responded = 0;

    /**
     * @var int
     * @DB\Column("starter")
     * @DB\Sql("tinyint NOT NULL DEFAULT '0'")
     */
    protected $starter = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('acc_');
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
     * @return Access
     */
    public function setId(int $id): Access
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Access
     */
    public function setUser(User $user): Access
    {
        $this->user = $user;

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
     * @return Access
     */
    public function setTemplate(Template $template): Access
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return bool
     */
    public function getTmpCollapsed(): bool
    {
        return (bool)$this->tmpCollapsed;
    }

    /**
     * @param bool $tmpCollapsed
     *
     * @return Access
     */
    public function setTmpCollapsed(bool $tmpCollapsed): Access
    {
        $this->tmpCollapsed = (int)$tmpCollapsed;

        return $this;
    }

    /**
     * @return bool
     */
    public function getArchiveOpened(): bool
    {
        return (bool)$this->archiveOpened;
    }

    /**
     * @param bool $archiveOpened
     *
     * @return Access
     */
    public function setArchiveOpened(bool $archiveOpened): Access
    {
        $this->archiveOpened = (int)$archiveOpened;

        return $this;
    }

    /**
     * @return bool
     */
    public function isResponded(): bool
    {
        return (bool)$this->responded;
    }

    /**
     * @param bool $responded
     *
     * @return Access
     */
    public function setResponded(bool $responded): Access
    {
        $this->responded = (int)$responded;

        return $this;
    }

    /**
     * @return bool
     */
    public function getStarter(): bool
    {
        return (bool)$this->starter;
    }

    /**
     * @param bool $starter
     *
     * @return Access
     */
    public function setStarter(bool $starter): Access
    {
        $this->starter = (int)$starter;

        return $this;
    }
}
