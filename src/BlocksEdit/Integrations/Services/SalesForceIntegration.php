<?php
namespace BlocksEdit\Integrations\Services;

use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\Mime;
use BlocksEdit\Http\Request;
use BlocksEdit\Integrations\Exception\IllegalCharacterException;
use BlocksEdit\Integrations\AbstractFilesystemIntegration;
use BlocksEdit\Integrations\BuilderIntegrationInterface;
use BlocksEdit\Integrations\Exception\OAuthUnauthorizedException;
use BlocksEdit\Integrations\Services\SalesForce\AssetType;
use BlocksEdit\Integrations\Services\SalesForce\RestClient;
use BlocksEdit\Integrations\Filesystem\FileInfo;
use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Soundasleep\Html2Text;

/**
 * Class SalesForceIntegration
 */
class SalesForceIntegration extends AbstractFilesystemIntegration
{
    const SCOPE = 'email_read email_write documents_and_images_read documents_and_images_write saved_content_write';
    const DIR_CONTENT_BUILDER = '/Content Builder';
    const ILLEGAL_CHARS = ['/', '=', '\\', ':', '"', '*', '?', '|', '<', '>', '@', '#'];

    /**
     * @var RestClient|null
     */
    protected $client;

    /**
     * @var array
     */
    protected $folders = [];

    /**
     * @var array
     */
    protected $userInfo = [];

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return 'salesforce';
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return 'Salesforce Marketing Cloud';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Import templates from Salesforce account.';
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(): int
    {
        return 29900;
    }

    /**
     * @return string
     */
    public function getIconURL(): string
    {
        return '/assets/images/integration-sfmc.png';
    }

    /**
     * {@inheritDoc}
     */
    public function getInstructionsURL(): string
    {
        return 'https://blocksedit.com/help/integrations/salesforce-marketing-cloud-setup/';
    }

    /**
     * @return string
     */
    public function getHomeDirectoryPlaceholder(): string
    {
        return 'Home Directory';
    }

    /**
     * @return string
     */
    public function getDefaultHomeDirectory(): string
    {
        return '/';
    }

    /**
     * {@inheritDoc}
     */
    public function translateHomeDirectory(string $dir): string
    {
        if ($dir === '/') {
            $dir = self::DIR_CONTENT_BUILDER;
        }

        return $dir;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldExportOriginalImageUrls(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function formatRemoteFilename($email): string
    {
        if (is_array($email)) {
            $title = $email['ema_title'] . '.html';
        } else {
            $title = $email;
        }
        foreach (self::ILLEGAL_CHARS as $char) {
            $title = str_replace($char, '-', $title);
        }

        return $title;
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontendSettings(array $rules = [], array $hooks = []): array
    {
        return parent::getFrontendSettings(
            [
                self::RULE_EXPORT_SETTINGS_SHOW  => true,
                self::RULE_EXPORT_TITLE_REQUIRED => true
            ]
        );
    }

    /**
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [
            'client_id'     => '',
            'client_secret' => '',
            'base_rest_url' => '',
            'base_auth_url' => '',
            'account_id'    => ''
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null)
    {
        $form = [
            'client_id' => [
                'type'     => 'text',
                'label'    => 'Client Id',
                'required' => true
            ],
            'client_secret' => [
                'type'     => 'password',
                'label'    => 'Client Secret',
                'required' => true
            ],
            'account_id' => [
                'type'     => 'text',
                'label'    => 'Business Unit MID',
                'help'     => 'Leave blank to use the default business unit.'
            ],
            'base_rest_url' => [
                'type'     => 'text',
                'label'    => 'REST Base URI',
                'required' => true
            ],
            'base_auth_url' => [
                'type'     => 'text',
                'label'    => 'Authentication Base URI',
                'required' => true
            ]
        ];

        return $this->applyFormValues($form, $values);
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
        if (!$this->isConnected()) {
            $this->client = new RestClient(
                $this->cache,
                $this->settings
            );
            $this->client->requestAccessToken(
                $this->settings['client_id'],
                $this->settings['client_secret'],
                (int)$this->settings['account_id'],
                self::SCOPE,
                true
            );
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
        $this->connect();

        if (!$dir || $dir === '/' || $dir === '.') {
            $dir = self::DIR_CONTENT_BUILDER;
        }

        $files      = $this->getSalesforceFolders($dir);
        $categoryID = array_search($dir, $this->folders);
        if ($categoryID === false) {
            throw new Exception("Directory $dir not found.");
        }

        $assets = $this->client->whileHasMore($this->client->fetchAssets($categoryID));
        foreach($assets as $asset) {
            $name = $asset['name'];
            if (in_array($asset['assetType']['name'], ['htmlblock', 'htmlemail', 'template'])) {
                $name .= '.html';
            } else if ($asset['assetType']['name'] === 'html') {
                $name = pathinfo($name, PATHINFO_FILENAME);
            }

            $files[] = new FileInfo(
                $name,
                $dir,
                0,
                (new DateTime($asset['createdDate']))->getTimestamp(),
                false,
                $asset
            );
        }

        usort($files, function($a, $b) {
            /**
             * @var FileInfo $a
             * @var FileInfo $b
             */
            return strcasecmp($a->getName(), $b->getName());
        });

        return $files;
    }

    /**
     * @param string $dir
     *
     * @return array
     * @throws GuzzleException
     */
    protected function getSalesforceFolders(string $dir): array
    {
        // $cacheKey = $this->getCacheKey('salesforce_folders');
        // $this->folders = $this->cache->get($cacheKey);
        $this->folders = [];

        /** @phpstan-ignore-next-line */
        if (!$this->folders) {
            $folders = [];
            $refs    = [];

            $categories = $this->client->whileHasMore($this->client->fetchCategories());
            foreach($categories as $category) {
                $id  = $category['id'];
                $ref = &$refs[$id];
                if (!$ref) {
                    $ref = $category;
                }

                if (!$category['parentId']) {
                    $folders[$id] = &$ref;
                } else {
                    if (isset($refs[$id]) && is_array($refs[$id])) {
                        if (key($refs[$id]) === 'children') {
                            $children              = $refs[$id]['children'];
                            $refs[$id]             = $category;
                            $refs[$id]['children'] = $children;
                        }
                    }

                    $refs[$category['parentId']]['children'][$id] = &$ref;
                }
            }

            $this->folders = $this->flattenFolders($folders);
            // $this->cache->set($cacheKey, $this->folders, 3600 * 24);
        }

        $files = [];
        foreach($this->folders as $category) {
            if (strpos($category, $dir) === 0) {
                $category = substr($category, strlen($dir));
                if ($category && substr_count($category, '/') < 2) {
                    $files[] = new FileInfo(
                        ltrim($category, '/'),
                        $dir,
                        0,
                        0,
                        true
                    );
                }
            }
        }

        return $files;
    }

    /**
     * @param array $folders
     * @param array $parents
     *
     * @return array
     */
    protected function flattenFolders(array $folders, array $parents = []): array
    {
        $names = [];
        foreach($folders as $folder) {
            $name = '';
            foreach($parents as $parent) {
                $name .= '/' . $parent['name'];
            }
            // if (empty($folder['name'])) {
                // continue;
            // }
            $name .= '/' . $folder['name'];
            $names[$folder['id']] = $name;

            if (!empty($folder['children'])) {
                $parents[] = $folder;
                $names += $this->flattenFolders($folder['children'], $parents);
                array_pop($parents);
            }
        }

        return $names;
    }

    /**
     * {@inheritDoc}
     */
    public function createDirectory(string $dir): bool
    {
        try {
            $this->connect();

            $parts = explode('/', $dir);
            $name  = array_pop($parts);
            $path  = join('/', $parts);
            $path  = $this->translateHomeDirectory($path);
            if (!$path) {
                $path = self::DIR_CONTENT_BUILDER;
            }

            foreach (self::ILLEGAL_CHARS as $char) {
                if (strpos($name, $char) !== false) {
                    throw new IllegalCharacterException(
                        'Your folder name cannot contain any of these special characters: ' . join(
                            '',
                            self::ILLEGAL_CHARS
                        )
                    );
                }
            }

            $parentId = $this->getCategoryID($path);
            if (!$parentId) {
                throw new Exception('Parent directory not found.');
            }

            $json = $this->client->postCategory($name, $parentId);
            $this->cache->delete($this->getCacheKey('salesforce_folders'));

            return isset($json['id']);
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDirectory(string $dir): bool
    {
        try {
            $this->connect();
            $this->cache->delete($this->getCacheKey('salesforce_folders'));

            $category = $this->getCategoryID($dir);
            if (!$category) {
                throw new Exception('Directory not found.');
            }

            $json = $this->client->deleteCategory($category);
            $this->cache->delete($this->getCacheKey('salesforce_folders'));

            return $json === 'OK';
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFile(string $remoteFilename): bool
    {
        try {
            $this->connect();

            $found = $this->getFile($remoteFilename);
            if (!$found) {
                return false;
            }

            $meta = $found->getMeta();
            $json = $this->client->deleteAsset($meta['id']);
            $this->cache->delete($this->getCacheKey('salesforce_folders'));

            return $json === 'OK';
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rename(string $remoteOldName, string $remoteNewName): bool
    {
        try {
            $this->connect();

            $id = $this->getCategoryID($remoteOldName);
            if (!$id) {
                $found = $this->getFile($remoteOldName);
                if (!$found) {
                    throw new Exception('Directory or file not found.');
                }

                return true;
            } else {
                $parts    = explode('/', $remoteNewName);
                $name     = array_pop($parts);
                $path     = join('/', $parts);
                $parentId = $this->getCategoryID($path);
                if (!$parentId) {
                    throw new Exception('Parent directory not found.');
                }

                $json = $this->client->putCategory($id, [
                    'id'       => $id,
                    'name'     => $name,
                    'parentId' => $parentId
                ]);
                $this->cache->delete($this->getCacheKey('salesforce_folders'));

                return !empty($json['id']);
            }
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     * @throws Exception
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
        try {
            $this->connect();

            list($asset, $mime) = $this->createAsset($remoteFilename, $localFilename, $subject);

            set_time_limit(60);
            if ($this->getFile($remoteFilename)) {
                $this->deleteFile($remoteFilename);
            }
            $resp = $this->client->postAsset($asset);
            $this->cache->delete($this->getCacheKey('salesforce_folders'));

            if (!empty($resp['fileProperties']['publishedURL'])) {
                return $resp['fileProperties']['publishedURL'];
            }

            if ($mime === 'text/html') {
                return '';
            }

            return $this->getFileURL($remoteFilename);
        } catch (InvalidArgumentException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function batchUploadFiles(
        array $remoteFilenames,
        array $localFilenames,
        string $assertType,
        array $assetIDs,
        array $subjects = [],
        array $extra = []
    ): array {
        $this->connect();

        $assets = [];
        $mimes  = [];
        foreach($remoteFilenames as $i => $remoteFilename) {
            $subject = $subjects[$i] ?? '';
            list($asset, $mime) = $this->createAsset($remoteFilename, $localFilenames[$i], $subject);
            $assets[] = $asset;
            $mimes[]  = $mime;

            if ($this->getFile($remoteFilename)) {
                $this->deleteFile($remoteFilename);
            }
        }

        set_time_limit(60);

        $urls = [];
        $responses = $this->client->postAssetsAsync($assets);
        foreach($responses as $i => $resp) {
            if ($mimes[$i] === 'text/html') {
                $urls[] = '';
            } else if (!empty($resp['fileProperties']['publishedURL'])) {
                $urls[] = $resp['fileProperties']['publishedURL'];
            } else {
                $urls[] = $this->getFileURL($remoteFilenames[$i]);
            }
        }
        $this->logger->debug('Salesforce', $urls);

        return $urls;
    }

    /**
     * {@inheritDoc}
     */
    public function downloadFile(string $remoteFilename, string $localFilename): int
    {
        $this->connect();

        $found = $this->getFile($remoteFilename);
        if (!$found) {
            throw new Exception('File not found.');
        }

        $size = 0;
        $meta = $found->getMeta();
        if (isset($meta['fileProperties']) && isset($meta['fileProperties']['publishedURL'])) {
            $data = file_get_contents($meta['fileProperties']['publishedURL']);
            $size = strlen($data);
            file_put_contents($localFilename, $data);
        } else if (isset($meta['content'])) {
            $size = strlen($meta['content']);
            file_put_contents($localFilename, $meta['content']);
        } else if (isset($meta['views']) && isset($meta['views']['html']['content'])) {
            $size = strlen($meta['views']['html']['content']);
            file_put_contents($localFilename, $meta['views']['html']['content']);
        }

        return $size;
    }

    /**
     * {@inheritDoc}
     */
    public function getFileURL(string $remoteFilename): string
    {
        $this->connect();

        $pathInfo = pathinfo($remoteFilename);
        foreach($this->getDirectoryListing($pathInfo['dirname']) as $file) {
            if ($file->getName() === $pathInfo['basename']) {
                $meta = $file->getMeta();
                if (isset($meta['fileProperties']) && !empty($meta['fileProperties']['publishedURL'])) {
                    return $meta['fileProperties']['publishedURL'];
                }
            }
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $remoteFilename): bool
    {
        $this->connect();

        $pathInfo = pathinfo($remoteFilename);
        foreach($this->getDirectoryListing($pathInfo['dirname']) as $file) {
            if ($file->getName() === $pathInfo['basename']) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     */
    public function clearLocalCache()
    {
        try {
            $this->connect();
            $this->client->clearLocalCache();
        } catch (Exception $e) {
        }
    }

    /**
     * @param string $remoteFilename
     * @param string $localFilename
     * @param string $subject
     *
     * @return array
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function createAsset(string $remoteFilename, string $localFilename, string $subject): array
    {
        $pathInfo = pathinfo($remoteFilename);
        $dir      = $pathInfo['dirname'];
        if (!$dir || $dir === '/' || $dir === '.') {
            $dir = self::DIR_CONTENT_BUILDER;
        }
        $asset = [
            'name'     => $pathInfo['basename'],
            'category' => ['id' => null]
        ];

        $categoryID = $this->cache->get($this->getCacheKey('salesforce_category_id_' . $dir));
        if (!$categoryID) {
            $categoryID = $this->getCategoryID($dir);
            if ($categoryID) {
                $this->cache->set($this->getCacheKey('salesforce_category_id_' . $dir), $categoryID, 3600);
            }
        }
        $asset['category']['id'] = $categoryID;
        if (!$asset['category']['id']) {
            throw new Exception('Unknown directory ' . $dir);
        }

        $data = file_get_contents($localFilename);
        $mime = Mime::getMimeType($localFilename);
        switch ($mime) {
            case 'text/html':
                $asset['assetType'] = [
                    'id' => AssetType::HTML_EMAIL
                ];
                $asset['views']     = [
                    'html' => [
                        'content' => $data
                    ],
                    'text' => [
                        'content' => Html2Text::convert($data, true)
                    ]
                ];
                if (!$subject) {
                    $subject = '';
                }
                $asset['views']['subjectline'] = [
                    'content' => $subject
                ];
                $asset['name'] = str_replace('.html', '', $asset['name']);
                break;
            case 'image/jpg':
                $asset['assetType'] = [
                    'id' => AssetType::JPG
                ];
                $asset['file'] = base64_encode($data);
                // $asset['name'] = str_replace('.jpg', '', $asset['name']);
                break;
            case 'image/jpeg':
                $asset['assetType'] = [
                    'id' => AssetType::JPEG
                ];
                $asset['file'] = base64_encode($data);
                // $asset['name'] = str_replace('.jpeg', '', $asset['name']);
                break;
            case 'image/png':
                $asset['assetType'] = [
                    'id' => AssetType::PNG
                ];
                $asset['file'] = base64_encode($data);
                // $asset['name'] = str_replace('.png', '', $asset['name']);
                break;
            case 'image/gif':
                $asset['assetType'] = [
                    'id' => AssetType::GIF
                ];
                $asset['file'] = base64_encode($data);
                // $asset['name'] = str_replace('.gif', '', $asset['name']);
                break;
        }

        return [$asset, $mime];
    }

    /**
     * @param string $categoryName
     *
     * @return int
     * @throws GuzzleException
     */
    protected function getCategoryID(string $categoryName): int
    {
        $category = 0;
        $this->getSalesforceFolders('/');
        foreach($this->folders as $id => $name) {
            if ($name === $categoryName) {
                $category = $id;
                break;
            }
        }

        return $category;
    }

    /**
     * @param string $remoteFilename
     *
     * @return FileInfo|null
     * @throws GuzzleException
     * @throws OAuthUnauthorizedException
     */
    protected function getFile(string $remoteFilename): ?FileInfo
    {
        $found = null;
        $parts = pathinfo($remoteFilename);
        $base  = $parts['basename'];
        foreach($this->getDirectoryListing($parts['dirname']) as $file) {
            if ($file->getName() === $base) {
                $found = $file;
                break;
            }
        }

        return $found;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return sprintf(
            '%s_%s_%d_2',
            $key,
            md5($this->settings['client_id'] . $this->settings['client_secret']),
            $this->settings['account_id']
        );
    }
}
