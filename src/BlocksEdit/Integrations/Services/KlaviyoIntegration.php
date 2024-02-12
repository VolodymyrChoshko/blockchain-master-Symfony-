<?php
namespace BlocksEdit\Integrations\Services;

use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\Request;
use BlocksEdit\Integrations\AbstractFilesystemIntegration;
use BlocksEdit\Integrations\Services\Klaviyo\Client;
use Exception;
use Klaviyo\Exception\KlaviyoResourceNotFoundException;
use InvalidArgumentException;

/**
 * Class KlaviyoIntegration
 */
class KlaviyoIntegration
    extends AbstractFilesystemIntegration
{
    /**
     * @var Client|null
     */
    protected $client;

    /**
     * {@inheritDoc}
     */
    public function getSlug(): string
    {
        return 'klaviyo';
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'Klaviyo';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(): int
    {
        return 14900;
    }

    /**
     * {@inheritDoc}
     */
    public function getIconURL(): string
    {
        return '/assets/images/integration-klaviyo.png';
    }

    /**
     * {@inheritDoc}
     */
    public function getInstructionsURL(): string
    {
        return 'https://blocksedit.com/help/integrations/klaviyo-setup/';
    }

    /**
     * {@inheritDoc}
     */
    public function getHomeDirectoryPlaceholder(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultHomeDirectory(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontendSettings(array $rules = [], array $hooks = []): array
    {
        return parent::getFrontendSettings([
            self::RULE_CAN_LIST_FOLDERS     => false,
            self::RULE_CAN_RENAME_FOLDERS   => false,
            self::RULE_CAN_CREATE_FOLDERS   => false,
            self::RULE_CAN_DELETE_FOLDERS   => false,
            self::RULE_CAN_EXPORT_IMAGES    => false,
            self::RULE_CAN_LIST_FILES       => false,
            self::RULE_CAN_DELETE_FILE      => false,
            self::RULE_CAN_RENAME_FILE      => false,
            self::RULE_EXPORT_SETTINGS_SHOW => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function formatRemoteFilename($email): string
    {
        if (is_array($email)) {
            return $email['ema_title'] ?? 'Blocks Edit Template';
        }

        return $email;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultSettings(): array
    {
        return [
            'private_key' => '',
            'public_key'  => ''
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null)
    {
        $form = [
            'private_key' => [
                'type'     => 'text',
                'label'    => 'Private key',
                'required' => true
            ],
            'public_key' => [
                'type'     => 'text',
                'label'    => 'Public key',
                'required' => true
            ]
        ];

        return $this->applyFormValues($form, $values);
    }

    /**
     * {@inheritDoc}
     */
    public function connect(): bool
    {
        if (!$this->isConnected()) {
            $this->client = $this->createClient();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect(): bool
    {
        $this->client = null;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return $this->client !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function getDirectoryListing(string $dir): array
    {
        throw new Exception('Not implemented');
        /*$files = [];
        foreach($this->getRemoteTemplates() as $template) {
            $files[$template['id']] = new FileInfo(
                $template['id'] . '-' . $template['name'] . '.html',
                '/',
                0,
                (new DateTime($template['updated']))->getTimestamp(),
                false
            );
        }*/

        // Remove files which have been deleted on the remote end.
        /*if (!empty($this->settings['files'])) {
            $changed = false;
            foreach($this->settings['files'] as $id => $values) {
                if (!isset($files[$id])) {
                    unset($this->settings['files'][$id]);
                    $changed = true;
                }
            }
            if ($changed) {
                $this->container->get(SourcesRepository::class)
                    ->updateSettings($this->source, $this->settings);
            }
        }

        return array_values($files);*/
    }

    /**
     * {@inheritDoc}
     */
    public function createDirectory(string $dir): bool
    {
        throw new Exception('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDirectory(string $dir): bool
    {
        throw new Exception('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function uploadFile(
        string $remoteFilename,
        string $localFilename,
        string $assetType,
        int $assetID,
        $subject = '',
        array $extra = []
    ): string
    {
        $found = '';
        /*if (isset($this->settings['files'])) {
            foreach($this->settings['files'] as $id => $values) {
                if ($values['email'] === $assetID) {
                    $found = $id;
                    break;
                }
            }
        }*/

        $client = $this->createClient();
        $html   = file_get_contents($localFilename);
        /** @phpstan-ignore-next-line */
        if (!$found) {
            $resp = $client->templates->createTemplate($remoteFilename, $html);
            if (empty($resp['id'])) {
                throw new Exception('Could not create template.');
            }
        } else {
            try {
                $resp = $client->templates->updateTemplate($found, $remoteFilename, $html);
                if (empty($resp['id'])) {
                    throw new Exception('Could not create template.');
                }
            } catch (KlaviyoResourceNotFoundException $e) {
                $resp = $client->templates->createTemplate($remoteFilename, $html);
                if (empty($resp['id'])) {
                    throw new Exception('Could not create template.');
                }
                unset($this->settings['files'][$found]);
            }
        }

        /*if (!isset($this->settings['files'])) {
            $this->settings['files'] = [];
        }
        $this->settings['files'][$resp['id']] = [
            'name'  => $resp['name'],
            'email' => $assetID
        ];
        $this->container->get(SourcesRepository::class)
            ->updateSettings($this->source, $this->settings);*/

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function downloadFile(string $remoteFilename, string $localFilename): int
    {
        throw new Exception('Not implemented');
        /*$remoteFilename = ltrim($remoteFilename, '/');
        list($id)       = explode('-', $remoteFilename, 2);
        $templates      = $this->getRemoteTemplates();

        foreach($templates as $template) {
            if ($template['id'] == $id) {
                return file_put_contents($localFilename, $template['html']);
            }
        }

        return 0;*/
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFile(string $remoteFilename): bool
    {
        throw new Exception('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function rename(string $remoteOldName, string $remoteNewName): bool
    {
        throw new Exception('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $remoteFilename): bool
    {
        throw new Exception('Not implemented');
        /*$remoteFilename = ltrim($remoteFilename, '/');
        list($id)       = explode('-', $remoteFilename, 2);
        $templates      = $this->getRemoteTemplates();

        foreach($templates as $template) {
            if ($template['id'] == $id) {
                return true;
            }
        }

        return false;*/
    }

    /**
     * {@inheritDoc}
     */
    public function getFileURL(string $remoteFilename): string
    {
        return '';
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getRemoteTemplates(): array
    {
        try {
            // Only templates uploaded from BE will be listed. $this->settings['files']
            // is a list of those files uploaded from BE.
            if (!isset($this->settings['files'])) {
                $this->settings['files'] = [];
            }

            $beFiles   = $this->settings['files'];
            $cacheKey  = $this->getCacheKey('klaviyo_folders');
            $flatFiles = $this->cache->get($cacheKey);

            if ($flatFiles === null) {
                $flatFiles = [];
                $client    = $this->createClient();
                $templates = $client->templates->getTemplates();
                foreach ($templates as $template) {
                    // Exclude templates not uploaded from BE.
                    $id = $template['id'];
                    if (isset($beFiles[$id])) {
                        $flatFiles[] = $template;
                    }
                }

                $this->cache->set($cacheKey, $flatFiles, 3600 * 24);
            }

            return $flatFiles;
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return Client
     */
    protected function createClient(): Client
    {
        // 'pk_d920a3f62c58e8e8b0fded7994e5f1f219', 'WLkaky'
        $this->client = new Client($this->settings['private_key'], $this->settings['public_key']);
        return $this->client;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return sprintf(
            '%s_%s_%d',
            $key,
            md5($this->settings['private_key'] . $this->settings['public_key']),
            $this->oid
        );
    }
}
