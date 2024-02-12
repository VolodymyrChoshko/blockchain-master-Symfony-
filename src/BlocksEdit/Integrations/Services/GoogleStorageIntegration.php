<?php
namespace BlocksEdit\Integrations\Services;

use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\Mime;
use BlocksEdit\Http\Request;
use BlocksEdit\Integrations\AbstractFilesystemIntegration;
use BlocksEdit\Integrations\Filesystem\FileInfo;
use BlocksEdit\Integrations\IntegrationInterface;
use BlocksEdit\Util\Strings;
use DateTime;
use Exception;
use Google_Service_Storage_Bucket;
use Google_Service_Storage_StorageObject;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use Google_Client;
use Google_Service_Storage;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * Class GoogleStorageIntegration
 */
class GoogleStorageIntegration
    extends AbstractFilesystemIntegration
{
    /**
     * @var Google_Client|null
     */
    protected $googleClient;

    /**
     * @var bool
     */
    protected $requiresReAuthenticate = false;

    /**
     * {@inheritDoc}
     */
    public function getSlug(): string
    {
        return 'google-storage';
    }

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return 'Google Cloud Storage';
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
        return 9900;
    }

    /**
     * @return string
     */
    public function getIconURL(): string
    {
        return '/assets/images/integration-google-storage.png';
    }

    /**
     * {@inheritDoc}
     */
    public function getInstructionsURL(): string
    {
        return 'https://blocksedit.com/help/integrations/google-storage-setup/';
    }

    /**
     * {@inheritDoc}
     */
    public function getHomeDirectoryPlaceholder(): string
    {
        return 'Home Bucket';
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
    public function getDefaultSettings(): array
    {
        return [
            'project_id'     => '',
            'predefined_acl' => 'default'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setSettings(array $settings, array $prevSettings = []): IntegrationInterface
    {
        parent::setSettings($settings, $prevSettings);
        if (!empty($prevSettings['project_id']) && $settings['project_id'] !== $prevSettings['project_id']) {
            $this->requiresReAuthenticate = true;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null)
    {
        $form = [
            'project_id' => [
                'type'     => 'text',
                'label'    => 'Project Id',
                'required' => true
            ],
            'predefined_acl' => [
                'type'     => 'choice',
                'label'    => 'ACL',
                'required' => false,
                'help'     => 'Permissions assigned to upload files and directories.',
                'choices'  => [
                    'default'    => 'Bucket Default',
                    'PUBLICREAD' => 'Public-Read'
                ]
            ],
            'custom_domain' => [
                'type'     => 'text',
                'label'    => 'Custom Domain',
                'help'     => 'Custom domain name assigned to your GCS bucket.'
            ]
        ];

        return $this->applyFormValues($form, $values);
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontendSettings(array $rules = [], array $hooks = []): array
    {
        return parent::getFrontendSettings([
            self::RULE_CAN_RENAME_FOLDERS => false,
            self::RULE_CAN_CREATE_ROOT_FOLDER => false,
            self::RULE_NO_FOLDER_SPACES => true
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsBatchUpload(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function connect(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getDirectoryListing(string $dir): array
    {
        $dir = ltrim($dir, '/');
        $dir = str_replace('//', '/', $dir);

        $options = [];
        if ($dir) {
            $parts             = explode('/', $dir);
            $options['prefix'] = array_shift($parts);
        }

        $files   = [];
        $storage = $this->getGoogleStorage();
        $buckets = $storage->buckets->listBuckets($this->settings['project_id'], $options);
        foreach($buckets->getItems() as $bucket) {
            $id = $bucket->id;
            if (strpos($id, 'blocksedit-logs') !== false || strpos($id, 'blocksedit.appspot.com') !== false) {
                continue;
            }

            /** @var Google_Service_Storage_Bucket $bucket */
            if (!$dir || strpos($dir, $id) === false) {
                $files[] = new FileInfo(
                    $bucket->id,
                    $dir,
                    0,
                    (new DateTime($bucket->timeCreated))->getTimestamp(),
                    true
                );
            }

            if ($dir) {
                $other = $this->getFilesFromObjects($storage, $bucket, $dir);
                if (!empty($other)) {
                    $files = array_merge($files, $other);
                }
            }
        }

        return $files;
    }

    /**
     * @param Google_Service_Storage        $storage
     * @param Google_Service_Storage_Bucket $bucket
     * @param string                        $dir
     *
     * @return array
     * @throws Exception
     */
    protected function getFilesFromObjects(
        Google_Service_Storage $storage,
        Google_Service_Storage_Bucket $bucket,
        string $dir
    ): array
    {
        $prefix    = '';
        $files     = [];
        $foundDirs = [];

        $bucketName = $bucket->name;
        if (strpos($dir, $bucketName . '/') === 0) {
            $prefix = substr($dir, strlen($bucketName) + 1);
        }

        $items = [];
        $token = '';
        do {
            $objects = $storage->objects->listObjects($bucket->name, [
                'prefix'    => $prefix,
                'pageToken' => $token
            ]);
            $objectItems = $objects->getItems();
            if (!$objectItems) {
                $objectItems = [];
            }
            $items = array_merge($items, $objectItems);
            $token = $objects->getNextPageToken();
        } while($token);

        foreach ($items as $object) {
            $name = $object->name;
            $path = '/' . $object->bucket;

            if (!Strings::endsWith($name, '/')) {
                $name = explode('/', $name);
                $file = array_pop($name);
                $path = rtrim($path . '/' . join('/', $name), '/');
                $name = $file;

                if ('/' . $dir === $path) {
                    $files[] = new FileInfo(
                        rtrim($name, '/'),
                        $path,
                        0,
                        (new DateTime($object->timeCreated))->getTimestamp(),
                        false
                    );
                }
            } else {
                $depth = substr_count($dir, '/');
                $parts = array_filter(explode('/', $name));

                if (count($parts) > $depth) {
                    $name = $parts[$depth];
                    $rest = join('/', array_slice($parts, 0, $depth));
                    if (!Strings::startsWith($rest, '/')) {
                        $rest = '/' . $rest;
                    }

                    $resolved = $path . $rest;
                    if (strpos($resolved, '/' . $dir) === 0 && !in_array($name, $foundDirs)) {
                        $foundDirs[] = $name;
                        $files[]     = new FileInfo(
                            rtrim($name, '/'),
                            $resolved,
                            0,
                            (new DateTime($object->timeCreated))->getTimestamp(),
                            true
                        );
                    }
                }
            }
        }

        return $files;
    }

    /**
     * {@inheritDoc}
     */
    public function createDirectory(string $dir): bool
    {
        $storage = $this->getGoogleStorage();
        $parts   = explode('/', ltrim($dir, '/'), 2);
        if (count($parts) === 2) {
            $dir    = $parts[1] . '/';
            $object = new Google_Service_Storage_StorageObject();
            $object->setName($dir);
            $object->setSize(0);

            if ($this->logger) {
                $this->logger->debug('Creating bucket ' . $dir);
            }
            $object = $storage->objects->insert($parts[0], $object, [
                'name'       => $dir,
                'uploadType' => 'media',
                'projection' => 'full',
                'data'       => '',
            ]);
            if ($this->logger) {
                $this->logger->debug('Got id ' . $object->getId());
            }

            return !empty($object->getId());
        } else {
            $bucket = new Google_Service_Storage_Bucket();
            $bucket->setName($parts[0]);
            $bucket = $storage->buckets->insert($this->settings['project_id'], $bucket);

            return !empty($bucket->getId());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDirectory(string $dir): bool
    {
        $parts = explode('/', ltrim($dir, '/'), 2);
        if (count($parts) !== 2) {
            throw new Exception('Invalid remote file name ' . $dir);
        }

        $storage = $this->getGoogleStorage();
        $storage->objects->delete($parts[0], $parts[1] . '/');

        return true;
    }

    /**
     * {@inheritDoc}
     * @throws GuzzleException
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
        $parts = explode('/', ltrim($remoteFilename, '/'), 2);
        if (count($parts) !== 2) {
            throw new Exception('Invalid remote file name ' . $remoteFilename);
        }

        $mime   = Mime::getMimeType($localFilename);
        $object = new Google_Service_Storage_StorageObject();
        $object->setName($parts[1]);
        $object->setContentType($mime);
        $object->setSize(filesize($localFilename));
        $object->setAcl([]);

        $options = [
            'uploadType'    => 'media',
            'mimeType'      => $mime,
            'name'          => $parts[1],
            'data'          => file_get_contents($localFilename),
        ];
        if ($this->settings['predefined_acl'] !== 'default') {
            $options['predefinedAcl'] = $this->settings['predefined_acl'];
        }
        $storage = $this->getGoogleStorage();
        $storage->objects->insert($parts[0], $object, $options);

        return $this->getFileURL($remoteFilename);
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     * @throws GuzzleException
     */
    public function batchUploadFiles(
        array $remoteFilenames,
        array $localFilenames,
        string $assertType,
        array $assetIDs,
        array $subjects = [],
        array $extra = []
    ): array
    {
        $storage = $this->getGoogleStorage();
        $client  = $storage->getClient();
        $client->setDefer(true);

        $requests = [];
        foreach($remoteFilenames as $i => $remoteFilename) {
            $parts = explode('/', ltrim($remoteFilename, '/'), 2);
            if (count($parts) !== 2) {
                throw new Exception('Invalid remote file name ' . $remoteFilename);
            }

            $mime   = Mime::getMimeType($localFilenames[$i]);
            $object = new Google_Service_Storage_StorageObject();
            $object->setName($parts[1]);
            $object->setContentType($mime);
            $object->setSize(filesize($localFilenames[$i]));
            $object->setAcl([]);

            $options = [
                'uploadType'    => 'media',
                'mimeType'      => $mime,
                'name'          => $parts[1],
                'data'          => file_get_contents($localFilenames[$i]),
            ];
            if ($this->settings['predefined_acl'] !== 'default') {
                $options['predefinedAcl'] = $this->settings['predefined_acl'];
            }

            $requests[] = $storage->objects->insert($parts[0], $object, $options);
        }

        /** @var Response[] $results */
        $results = Pool::batch($client->authorize(), $requests);
        foreach($results as $result) {
            if ($result->getStatusCode() !== 200) {
                throw new RuntimeException($result->getReasonPhrase(), $result->getStatusCode());
            }
        }

        $urls = [];
        foreach($remoteFilenames as $remoteFilename) {
            $urls[] = $this->getFileURL($remoteFilename);
        }

        return $urls;
    }

    /**
     * {@inheritDoc}
     */
    public function downloadFile(string $remoteFilename, string $localFilename): int
    {
        $parts = explode('/', ltrim($remoteFilename, '/'), 2);
        if (count($parts) !== 2) {
            throw new Exception('Invalid remote file name ' . $remoteFilename);
        }

        $storage = $this->getGoogleStorage();
        $object  = $storage->objects->get($parts[0], $parts[1]);
        if (!$object) {
            throw new Exception('Object not found ' . $remoteFilename);
        }

        return file_put_contents($localFilename, $this->downloadGoogleObject($object));
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFile(string $remoteFilename): bool
    {
        $parts = explode('/', ltrim($remoteFilename, '/'), 2);
        if (count($parts) !== 2) {
            throw new Exception('Invalid remote file name ' . $remoteFilename);
        }

        $storage = $this->getGoogleStorage();
        $storage->objects->delete($parts[0], $parts[1]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function rename(string $remoteOldName, string $remoteNewName): bool
    {
        throw new Exception('Not supported.');
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $remoteFilename): bool
    {
        $parts = explode('/', ltrim($remoteFilename, '/'), 2);
        if (count($parts) !== 2) {
            return false;
        }

        $storage = $this->getGoogleStorage();
        if (!$storage->objects->get($parts[0], $parts[1])) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getFileURL(string $remoteFilename): string
    {
        try {
            $parts = explode('/', ltrim($remoteFilename, '/'), 2);
            if (count($parts) !== 2) {
                throw new Exception("URL not found for $remoteFilename.");
            }

            $storage = $this->getGoogleStorage();
            $object  = $storage->objects->get($parts[0], $parts[1]);
            if (!$object) {
                throw new Exception("URL not found for $remoteFilename.");
            }

            if (!empty($this->settings['custom_domain'])) {
                $domain = str_replace('http://', '', $this->settings['custom_domain']);
                $domain = str_replace('https://', '', $domain);
                $domain = trim($domain, '/');

                return sprintf('https://%s/%s', $domain, $parts[1]);
            } else {
                return sprintf('https://storage.googleapis.com/%s/%s', $parts[0], $parts[1]);
            }
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return Google_Client
     * @throws Exception
     */
    protected function getGoogleClient(): Google_Client
    {
        if (!$this->googleClient) {
            $this->googleClient = new Google_Client();
            $this->googleClient->setAuthConfig(__DIR__ . '/GoogleStorage/service_account_creds.json');
            $this->googleClient->addScope(Google_Service_Storage::DEVSTORAGE_READ_WRITE);
        }

        return $this->googleClient;
    }

    /**
     * @return Google_Service_Storage
     * @throws Exception
     */
    protected function getGoogleStorage(): Google_Service_Storage
    {
        if (!$this->connect()) {
            throw new Exception('Could not connect');
        }

        return new Google_Service_Storage($this->getGoogleClient());
    }

    /**
     * @param Google_Service_Storage_StorageObject $object
     *
     * @return string
     * @throws Exception
     * @throws GuzzleException
     */
    protected function downloadGoogleObject(Google_Service_Storage_StorageObject $object): string
    {
        if (!$this->connect()) {
            throw new Exception('Could not connect');
        }

        $token  = $this->getGoogleClient()->getAccessToken();
        $guzzle = new Client();
        $resp   = $guzzle->request('GET', $object->getMediaLink(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token['access_token']
            ]
        ]);

        return (string)$resp->getBody();
    }
}
