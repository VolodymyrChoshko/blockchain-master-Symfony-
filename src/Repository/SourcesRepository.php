<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Integrations\FilesystemIntegrationInterface;
use BlocksEdit\Integrations\IntegrationInterface;
use BlocksEdit\System\Required;
use Entity\Credential;
use Entity\Source;
use Entity\TemplateSource;
use Exception;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SourcesRepository
 */
class SourcesRepository extends Repository
{
    /**
     * @var IntegrationInterface[]
     */
    protected $integrations = null;

    /**
     * @return IntegrationInterface[]
     */
    public function getAvailableIntegrations(): ?array
    {
        $this->setupObjects();

        return $this->integrations;
    }

    /**
     * @param string $slug
     *
     * @return IntegrationInterface|FilesystemIntegrationInterface|null
     */
    public function getIntegrationBySlug(string $slug)
    {
        $this->setupObjects();
        if (!isset($this->integrations[$slug])) {
            return null;
        }

        return $this->integrations[$slug];
    }

    /**
     * @param string $className
     *
     * @return IntegrationInterface|null
     */
    public function getAvailableByClass(string $className): ?IntegrationInterface
    {
        foreach($this->getAvailableIntegrations() as $int) {
            if (get_class($int) === $className) {
                return $int;
            }
        }

        return null;
    }

    /**
     * @param IntegrationInterface|Source $source
     * @param int                         $oid
     *
     * @return bool
     * @throws Exception
     */
    public function hasIntegrationByOrg($source, int $oid): bool
    {
        if ($source instanceof Source) {
            $source = $source->getIntegration();
        } else if (!($source instanceof IntegrationInterface)) {
            throw new InvalidArgumentException(
                'First parameter must be an array or instance of IntegrationInterface'
            );
        }

        $row = $this->findByIntegrationAndOrg($source, $oid);

        return !empty($row);
    }

    /**
     * @param Source $source
     *
     * @return array
     * @throws Exception
     */
    public function findSettings(Source $source): array
    {
        $credential = $this->credentialsRepository->findByID($source->getCrdId());
        if ($credential) {
            $this->credentialsRepository->decrypt($credential);
            if ($decoded = @json_decode($credential->getUnsealed(), true)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @param Source $source
     * @param array  $values
     *
     * @return Source
     * @throws Exception
     */
    public function updateSettings(Source $source, array $values): Source
    {
        $json = json_encode($values);
        if ($source->getCrdId() && $credential = $this->credentialsRepository->findByID($source->getCrdId())) {
            $credential->setUnsealed($json);
            $this->credentialsRepository->encryptAndUpdate($credential);
        } else {
            $credential = (new Credential())
                ->setUnsealed($json);
            $this->credentialsRepository->encryptAndInsert($credential);
            $source->setCrdId($credential->getId());
        }

        return $source;
    }

    /**
     * @param int $sid
     *
     * @return Source
     * @throws Exception
     */
    public function findByID(int $sid): ?Source
    {
        $entity = $this->findOne([
            'id' => $sid
        ]);

        return $this->addIntegrationToSource($entity);
    }

    /**
     * @param IntegrationInterface|string $integration
     * @param int                         $oid
     *
     * @return Source[]
     * @throws Exception
     */
    public function findByIntegrationAndOrg($integration, int $oid): array
    {
        if ($integration instanceof IntegrationInterface) {
            $integration = get_class($integration);
        } else if (!class_exists($integration)) {
            throw new InvalidArgumentException('Invalid integration class name.');
        }

        $entity = $this->find([
            'orgId' => $oid,
            'class'  => $integration
        ]);

        return $this->addIntegrationToSources($entity);
    }

    /**
     * @param int $oid
     *
     * @return Source[]
     * @throws Exception
     */
    public function findByOrg(int $oid): array
    {
        $entities = $this->find([
            'orgId' => $oid
        ]);

        return $this->addIntegrationToSources($entities);
    }

    /**
     * @param int|Source $source
     * @param int $uid
     * @param int $oid
     *
     * @return IntegrationInterface|FilesystemIntegrationInterface|null
     * @throws Exception
     */
    public function integrationFactory($source, int $uid, int $oid)
    {
        if (!($source instanceof Source)) {
            $source = $this->findByID($source);
            if (!$source) {
                return null;
            }
        }

        /** @var IntegrationInterface $integration */
        /** @phpstan-ignore-next-line */
        $className   = $source->getClass();
        $integration = new $className();
        $integration->setSource($source);
        $integration->setContainer($this->container);
        $integration->setLogger($this->logger);
        $integration->setCache($this->cache);
        $integration->setConfig($this->config);
        $integration->setSettings($this->findSettings($source));
        $integration->setUser($uid, $oid);

        return $integration;
    }

    /**
     * @param int   $oid
     * @param array $templates
     *
     * @return bool
     * @throws Exception
     */
    public function setTemplatesSources(int $oid, array &$templates): bool
    {
        $sourcesRepo         = $this->container->get(SourcesRepository::class);
        $templateSourcesRepo = $this->container->get(TemplateSourcesRepository::class);

        $hasSources = false;
        $sources    = $sourcesRepo->findByOrg($oid);
        foreach($templates as &$template) {
            $template['sources'] = [];
            $emailHasSources     = false;
            foreach($sources as $source) {
                if (!($source->getIntegration() instanceof FilesystemIntegrationInterface)) {
                    continue;
                }

                /** @phpstan-ignore-next-line */
                $frontendSettings = $source->getIntegration()->getFrontendSettings();
                if ($frontendSettings['rules']['can_list_files']) {
                    $hasSources = true;
                }

                $sourceClone           = clone $source;
                $template['sources'][] = $sourceClone;
                $templateSource = $templateSourcesRepo->findByTemplateAndSource(
                    $template['tmp_id'],
                    $sourceClone->getId()
                );

                if ($templateSource) {
                    if ($templateSource->isEnabled()) {
                        $emailHasSources = true;
                    }
                    $sourceClone->setIsEnabled($templateSource->isEnabled());
                    $sourceClone->setHomeDir($templateSource->getHomeDir());
                } else {
                    $templateSource = (new TemplateSource())
                        ->setTmpId($template['tmp_id'])
                        ->setSrcId($sourceClone->getId())
                        ->setHomeDir($sourceClone->getHomeDir())
                        ->setIsEnabled(false);
                    $templateSourcesRepo->insert($templateSource);
                    $sourceClone->setIsEnabled(false);
                    $sourceClone->setHomeDir($sourceClone->getHomeDir());
                }
            }

            if (!empty($template['emails'])) {
                foreach ($template['emails'] as &$email) {
                    $email['hasSources'] = $emailHasSources;
                }
            }
        }

        return $hasSources;
    }

    /**
     * @param Source[] $rows
     *
     * @return array
     */
    private function addIntegrationToSources(array $rows): array
    {
        $results = [];
        foreach($rows as $row) {
            $integration = $this->getAvailableByClass($row->getClass());
            if ($integration) {
                $cloned = clone $integration;
                $cloned->setSource($row);
                $row->setIntegration($cloned);
                $results[] = $row;
            }
        }

        return $results;
    }

    /**
     * @param Source|null $row
     *
     * @return Source
     */
    private function addIntegrationToSource(?Source $row): ?Source
    {
        if ($row) {
            $integration = $this->getAvailableByClass($row->getClass());
            if ($integration) {
                $cloned = clone $integration;
                $cloned->setSource($row);
                $row->setIntegration($cloned);
            }
        }

        return $row;
    }

    /**
     *
     */
    private function setupObjects()
    {
        if ($this->integrations === null) {
            $this->integrations = [];
            foreach ($this->config->integrations as $className) {
                /** @var IntegrationInterface $integration */
                $integration = new $className();
                $integration->setContainer($this->container);
                $integration->setCache($this->cache);
                $integration->setConfig($this->config);
                $this->integrations[$integration->getSlug()] = $integration;
            }
        }
    }

    /**
     * @var CredentialsRepository
     */
    protected $credentialsRepository;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Required()
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @Required()
     * @param CredentialsRepository $credentialsRepository
     */
    public function setCredentialsRepository(CredentialsRepository $credentialsRepository)
    {
        $this->credentialsRepository = $credentialsRepository;
    }
}
