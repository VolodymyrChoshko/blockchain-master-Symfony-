<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use BlocksEdit\Integrations\IntegrationInterface;
use DateTime;
use Exception;

/**
 * @DB\Table(
 *     "sources",
 *     prefix="src_",
 *     repository="Repository\SourcesRepository",
 *     charSet="latin1"
 * )
 */
class Source
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("id")
     */
    protected $id;

    /**
     * @var string
     * @DB\Column("name")
     */
    protected $name;

    /**
     * @var int
     * @DB\Column("org_id")
     */
    protected $orgId;

    /**
     * @var string
     * @DB\Column("home_dir")
     */
    protected $homeDir;

    /**
     * @var string
     * @DB\Column("class")
     */
    protected $class;

    /**
     * @var int
     * @DB\Column("crd_id")
     */
    protected $crdId = 0;

    /**
     * @var IntegrationInterface
     */
    protected $integration;

    /**
     * @var bool
     */
    protected $isEnabled = true;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var DateTime
     * @DB\Column("src_date_created")
     */
    protected $dateCreated;

    /**
     * @var DateTime
     * @DB\Column("src_date_updated")
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
     * @return array
     */
    public function toArray(): array
    {
        $integration = $this->getIntegration();

        $details = [
            'id'                 => $this->getId(),
            'name'               => $this->getName(),
            'homeDir'            => $this->getHomeDir(),
            'isEnabled'          => $this->isEnabled(),
            'slug'               => $integration->getSlug(),
            'thumb'              => $integration->getIconURL(),
            'settings'           => $integration->getFrontendSettings(),
            /** @phpstan-ignore-next-line */
            'homeDirPlaceholder' => $integration->getHomeDirectoryPlaceholder()
        ];
        if (isset($details['settings']['hooks'])) {
            $details['settings']['hooks'] = array_keys($details['settings']['hooks']);
        }

        return $details;
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
     * @return Source
     */
    public function setId(int $id): Source
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Source
     */
    public function setName(string $name): Source
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrgId(): ?int
    {
        return $this->orgId;
    }

    /**
     * @param int $orgId
     *
     * @return Source
     */
    public function setOrgId(int $orgId): Source
    {
        $this->orgId = $orgId;

        return $this;
    }

    /**
     * @return string
     */
    public function getHomeDir(): ?string
    {
        return $this->homeDir;
    }

    /**
     * @param string $homeDir
     *
     * @return Source
     */
    public function setHomeDir(string $homeDir): Source
    {
        $this->homeDir = $homeDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @param string $class
     *
     * @return Source
     */
    public function setClass(string $class): Source
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return int
     */
    public function getCrdId(): ?int
    {
        return $this->crdId;
    }

    /**
     * @param int $crdId
     *
     * @return Source
     */
    public function setCrdId(int $crdId): Source
    {
        $this->crdId = $crdId;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreated(): ?DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param DateTime $dateCreated
     *
     * @return Source
     * @throws Exception
     */
    public function setDateCreated(DateTime $dateCreated): Source
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateUpdated(): ?DateTime
    {
        return $this->dateUpdated;
    }

    /**
     * @param DateTime $dateUpdated
     *
     * @return Source
     * @throws Exception
     */
    public function setDateUpdated(DateTime $dateUpdated): Source
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * @return IntegrationInterface|null
     */
    public function getIntegration(): ?IntegrationInterface
    {
        return $this->integration;
    }

    /**
     * @param IntegrationInterface $integration
     *
     * @return Source
     */
    public function setIntegration(IntegrationInterface $integration): Source
    {
        $this->integration = $integration;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     *
     * @return Source
     */
    public function setIsEnabled(bool $isEnabled): Source
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     *
     * @return Source
     */
    public function setSettings(array $settings): Source
    {
        $this->settings = $settings;

        return $this;
    }
}
