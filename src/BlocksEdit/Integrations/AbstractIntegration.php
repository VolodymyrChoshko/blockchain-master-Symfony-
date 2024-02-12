<?php
namespace BlocksEdit\Integrations;

use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\Request;
use BlocksEdit\Config\Config;
use BlocksEdit\Cache\CacheInterface;
use Entity\Source;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AbstractIntegration
 */
abstract class AbstractIntegration implements IntegrationInterface
{
    /**
     * @var int
     */
    protected $uid;

    /**
     * @var int
     */
    protected $oid;

    /**
     * @var Source|null
     */
    protected $source;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @var \BlocksEdit\Cache\CacheInterface
     */
    protected $cache;

    /**
     * @var \BlocksEdit\Config\Config
     */
    protected $config;

    /**
     * {@inheritDoc}
     */
    public function setUser(int $uid, int $oid): IntegrationInterface
    {
        $this->uid = $uid;
        $this->oid = $oid;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setSource(Source $source): IntegrationInterface
    {
        $this->source = $source;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container): IntegrationInterface
    {
        $this->container = $container;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * {@inheritDoc}
     */
    public function setLogger(LoggerInterface $logger): IntegrationInterface
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param \BlocksEdit\Cache\CacheInterface $cache
     *
     * @return $this
     */
    public function setCache(CacheInterface $cache): IntegrationInterface
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param \BlocksEdit\Config\Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config): IntegrationInterface
    {
        $this->config = $config;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRestrictions(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function hasRestriction(string $restriction): bool
    {
        return in_array($restriction, $this->getRestrictions());
    }

    /**
     * {@inheritDoc}
     */
    public function setSettings(array $settings, array $prevSettings = []): IntegrationInterface
    {
        $this->settings = array_merge($this->getDefaultSettings(), $settings);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultSettings(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsScript(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function preSaveSettings(array $settings)
    {
        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontendSettings(array $rules = [], array $hooks = []): array
    {
        return [
            'rules' => array_merge([
                self::RULE_CAN_LIST_FOLDERS       => true,
                self::RULE_CAN_RENAME_FOLDERS     => true,
                self::RULE_CAN_CREATE_FOLDERS     => true,
                self::RULE_CAN_DELETE_FOLDERS     => true,
                self::RULE_CAN_CREATE_ROOT_FOLDER => true,
                self::RULE_CAN_EXPORT_IMAGES      => true,
                self::RULE_CAN_EXPORT_HTML        => true,
                self::RULE_CAN_LIST_FILES         => true,
                self::RULE_CAN_DELETE_FILE        => true,
                self::RULE_CAN_RENAME_FILE        => true,
                self::RULE_NO_FOLDER_SPACES       => false,
                self::RULE_EXPORT_SETTINGS_SHOW   => false,
                self::RULE_EXPORT_TITLE_REQUIRED  => false
            ], $rules),
            'hooks' => $hooks
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function requiresOauthRedirect(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isOauthAuthenticated(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getOauthURL()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function setOauthResponse(array $response): IntegrationInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function postRemoveIntegration() {}

    /**
     * @param array $form
     * @param array $values
     *
     * @return array
     */
    protected function applyFormValues(array $form, array $values): array
    {
        foreach($form as $name => &$item) {
            if (isset($values[$name])) {
                $item['value'] = $values[$name];
            }
        }

        return $form;
    }
}
