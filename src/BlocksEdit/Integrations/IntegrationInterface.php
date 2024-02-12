<?php
namespace BlocksEdit\Integrations;

use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\Request;
use BlocksEdit\Config\Config;
use BlocksEdit\Cache\CacheInterface;
use BlocksEdit\View\View;
use Entity\Source;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface IntegrationInterface
 */
interface IntegrationInterface
{
    const ONE_PER_ORG                 = 'ONE_PER_ORG';
    const RULE_CAN_LIST_FOLDERS       = 'can_list_folders';
    const RULE_CAN_RENAME_FOLDERS     = 'can_rename_folders';
    const RULE_CAN_CREATE_FOLDERS     = 'can_create_folders';
    const RULE_CAN_DELETE_FOLDERS     = 'can_delete_folders';
    const RULE_CAN_CREATE_ROOT_FOLDER = 'can_create_root_folder';
    const RULE_CAN_EXPORT_IMAGES      = 'can_export_images';
    const RULE_CAN_EXPORT_HTML        = 'can_export_html';
    const RULE_CAN_LIST_FILES         = 'can_list_files';
    const RULE_CAN_DELETE_FILE        = 'can_delete_file';
    const RULE_CAN_RENAME_FILE        = 'can_rename_file';
    const RULE_NO_FOLDER_SPACES       = 'no_folder_spaces';
    const RULE_EXPORT_SETTINGS_SHOW   = 'export_settings_show';
    const RULE_EXPORT_TITLE_REQUIRED  = 'export_title_required';
    const HOOK_EMAIL_SETTINGS         = 'email_settings';
    const HOOK_EMAIL_SETTINGS_POST    = 'email_settings_post';
    const HOOK_TEMPLATE_SETTINGS_PRE  = 'template_settings_pre';
    const HOOK_TEMPLATE_SETTINGS_POST = 'template_settings_post';

    /**
     * @param int $uid
     * @param int $oid
     *
     * @return $this
     */
    public function setUser(int $uid, int $oid): IntegrationInterface;

    /**
     * @param Source $source
     *
     * @return $this
     */
    public function setSource(Source $source): IntegrationInterface;

    /**
     * @param ContainerInterface $container
     *
     * @return $this
     */
    public function setContainer(ContainerInterface $container): IntegrationInterface;

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface;

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): IntegrationInterface;

    /**
     * @param \BlocksEdit\Cache\CacheInterface $cache
     *
     * @return $this
     */
    public function setCache(CacheInterface $cache): IntegrationInterface;

    /**
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config): IntegrationInterface;

    /**
     * @return array
     */
    public function getRestrictions(): array;

    /**
     * @param string $restriction
     *
     * @return bool
     */
    public function hasRestriction(string $restriction): bool;

    /**
     * @param array $settings
     * @param array $prevSettings
     *
     * @return $this
     */
    public function setSettings(array $settings, array $prevSettings = []): IntegrationInterface;

    /**
     * @return array
     */
    public function getSettings(): array;

    /**
     * @return array
     */
    public function getDefaultSettings(): array;

    /**
     * @param array $settings
     *
     * @return array|FormErrors
     */
    public function preSaveSettings(array $settings);

    /**
     * @param array $rules
     * @param array $hooks
     *
     * @return array
     */
    public function getFrontendSettings(array $rules = [], array $hooks = []): array;

    /**
     * @return string
     */
    public function getSlug(): string;

    /**
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return int
     */
    public function getPrice(): int;

    /**
     * @return string
     */
    public function getIconURL(): string;

    /**
     * @return string
     */
    public function getInstructionsURL(): string;

    /**
     * @param Request         $request
     * @param array           $values
     * @param FormErrors|null $errors
     *
     * @return array|string|View
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null);

    /**
     * @return string
     */
    public function getSettingsScript(): string;

    /**
     * @return bool
     */
    public function requiresOauthRedirect(): bool;

    /**
     * @return bool
     * @throws Exception
     */
    public function isOauthAuthenticated(): bool;

    /**
     * @return string|bool
     */
    public function getOauthURL();

    /**
     * @param array $response
     *
     * @return IntegrationInterface
     * @throws Exception
     */
    public function setOauthResponse(array $response): IntegrationInterface;

    /**
     *
     */
    public function postRemoveIntegration();
}
