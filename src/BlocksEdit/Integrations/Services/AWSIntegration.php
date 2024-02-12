<?php
namespace BlocksEdit\Integrations\Services;

use BlocksEdit\Html\FormErrors;
use BlocksEdit\Http\Request;
use BlocksEdit\Integrations\AbstractFilesystemIntegration;
use BlocksEdit\Integrations\Filesystem\FileInfo;
use BlocksEdit\Util\Strings;
use Aws\S3\S3Client;
use DateTime;
use Exception;

/**
 * Class AWSIntegration
 */
class AWSIntegration extends AbstractFilesystemIntegration
{
    /**
     * @var S3Client|null
     */
    protected $s3;

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return 'aws';
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return 'AWS S3';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Import templates from an AWS S3 bucket.';
    }

    /**
     * @return string
     */
    public function getIconURL(): string
    {
        return '/assets/images/integration-aws.png';
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(): int
    {
        return 9900;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstructionsURL(): string
    {
        return 'https://blocksedit.com/help/integrations/amazon-web-services-s3-setup/';
    }

    /**
     * @return string
     */
    public function getHomeDirectoryPlaceholder(): string
    {
        return 'Home Bucket';
    }

    /**
     * @return string
     */
    public function getDefaultHomeDirectory(): string
    {
        return '/';
    }

    /**
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [
            'version' => 'latest',
            'region'  => 'us-east-2',
            'acl'     => 'public-read',
            'headers' => "Cache-Control:max-age=86400"
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsForm(Request $request, array $values = [], ?FormErrors $errors = null)
    {
        $form = [
            'region' => [
                'type'     => 'choice',
                'label'    => 'Region',
                'required' => true,
                'choices'  => [
                    ''     => 'Select...',
                    'us-east-2'      => 'us-east-2',
                    'us-east-1'      => 'us-east-1',
                    'us-west-1'      => 'us-west-1',
                    'us-west-2'      => 'us-west-2',
                    'ap-east-1'      => 'ap-east-1',
                    'ap-south-1'     => 'ap-south-1',
                    'ap-northeast-3' => 'ap-northeast-3',
                    'ap-northeast-2' => 'ap-northeast-2',
                    'ap-southeast-1' => 'ap-southeast-1',
                    'ap-southeast-2' => 'ap-southeast-2',
                    'ap-northeast-1' => 'ap-northeast-1',
                    'ca-central-1'   => 'ca-central-1',
                    'cn-north-1'     => 'cn-north-1',
                    'cn-northwest-1' => 'cn-northwest-1',
                    'eu-central-1'   => 'eu-central-1',
                    'eu-west-1'      => 'eu-west-1',
                    'eu-west-2'      => 'eu-west-2',
                    'eu-west-3'      => 'eu-west-3',
                    'eu-north-1'     => 'eu-north-1',
                    'sa-east-1'      => 'sa-east-1'
                ]
            ],
            'key' => [
                'type'     => 'password',
                'label'    => 'Access key ID',
                'required' => true
            ],
            'secret' => [
                'type'     => 'password',
                'label'    => 'Secret access key',
                'required' => true
            ],
            'acl' => [
                'type'     => 'choice',
                'label'    => 'Access-Control',
                'required' => false,
                'help'     => 'Bucket permissions assigned to files and directories. Public-Read is required to make images viewable by the world.',
                'choices'  => [
                    'public-read'               => 'Public-Read',
                    'public-read-write'         => 'Public-Read-Write',
                    'private'                   => 'Private',
                    'bucket-owner-read'         => 'Bucket-Owner-Read',
                    'bucket-owner-full-control' => 'Bucket-Owner-Full-Control',
                    'authenticated-read'        => 'Authenticated-Read',
                    'aws-exec-read'             => 'AWS-Exec-Read'
                ]
            ],
            'headers' => [
                'type' => 'hidden'
            ]
        ];

        return $this->applyFormValues($form, $values);
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsScript(): string
    {
        return file_get_contents(__DIR__ . '/scripts/aws.js');
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontendSettings(array $rules = [], array $hooks = []): array
    {
        return parent::getFrontendSettings([
            self::RULE_NO_FOLDER_SPACES => true,
            self::RULE_CAN_CREATE_ROOT_FOLDER => false
        ]);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function connect(): bool
    {
        if (!$this->isConnected()) {
            $settings = array_merge($this->settings, [
                'credentials' => [
                    'key'    => $this->settings['key'],
                    'secret' => $this->settings['secret']
                ]
            ]);
            $this->s3 = new S3Client($settings);
        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function disconnect(): bool
    {
        if ($this->isConnected()) {
            $this->s3 = null;
        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isConnected(): bool
    {
        return $this->s3 !== null;
    }

    /**
     * @param string $dir
     *
     * @return FileInfo[]
     * @throws Exception
     */
    public function getDirectoryListing(string $dir): array
    {
        $files = [];
        $this->connect();

        $dir = str_replace('\\', '/', $dir);
        $dir = trim($dir, '/');

        if ($dir === '') {
            $results = $this->s3->listBuckets();
            $results = $results->toArray();

            foreach($results['Buckets'] as $bucket) {
                /** @var DateTime $date */
                $date  = $bucket['CreationDate'];
                $name  = $bucket['Name'];

                $files[] = new FileInfo(
                    $name,
                    $dir,
                    0,
                    $date->getTimestamp(),
                    true,
                    $bucket
                );
            }
        } else {
            list($bucket, $key) = $this->extractBucketAndKey($dir);
            $options = [
                'Bucket'    => $bucket,
                'Delimiter' => '/'
            ];
            if ($key) {
                $options['Prefix'] = $key . '/';
            }
            $results = $this->s3->listObjects($options);
            $results = $results->toArray();

            if (!empty($results['Contents'])) {
                foreach ($results['Contents'] as $content) {
                    /** @var DateTime $date */
                    $date  = $content['LastModified'];
                    $name  = $content['Key'];
                    $isDir = Strings::endsWith($content['Key'], '/');
                    if ($isDir) {
                        $name = substr($name, 0, -1);
                    }
                    if ($name === $key) {
                        continue;
                    }
                    if ($key && Strings::startsWith($name, $key . '/')) {
                        $name = substr($name, strlen($key) + 1);
                    }

                    $files[] = new FileInfo(
                        $name,
                        $dir,
                        $content['Size'],
                        $date->getTimestamp(),
                        false,
                        $content
                    );
                }
            }

            if (!empty($results['CommonPrefixes'])) {
                foreach ($results['CommonPrefixes'] as $items) {
                    foreach($items as $item) {
                        $name = substr($item, 0, -1);
                        if ($key && Strings::startsWith($name, $key . '/')) {
                            $name = substr($name, strlen($key) + 1);
                        }

                        $files[] = new FileInfo(
                            $name,
                            $dir,
                            0,
                            0,
                            true,
                            $item
                        );
                    }
                }
            }
        }

        return $files;
    }

    /**
     * @param string $dir
     *
     * @return bool
     * @throws Exception
     */
    public function createDirectory(string $dir): bool
    {
        list($bucket, $key) = $this->extractBucketAndKey($dir);

        $this->connect();
        $this->s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $key . '/',
            'Body'   => '',
            'ACL'    => $this->settings['acl']
        ]);

        return true;
    }

    /**
     * @param string $dir
     *
     * @return bool
     * @throws Exception
     */
    public function deleteDirectory(string $dir): bool
    {
        list($bucket, $key) = $this->extractBucketAndKey($dir);

        $this->connect();
        $this->s3->deleteObject([
            'Bucket' => $bucket,
            'Key'    => $key . '/',
        ]);

        return true;
    }

    /**
     * @param string $remoteFilename
     * @param string $localFilename
     * @param string $assetType
     * @param int    $assetID
     * @param string $subject
     * @param array  $extra
     *
     * @return string
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
        list($bucket, $key) = $this->extractBucketAndKey($remoteFilename);

        $this->connect();

        $params = [
            'Bucket'     => $bucket,
            'Key'        => $key,
            'SourceFile' => $localFilename,
            'ACL'        => $this->settings['acl']
        ];
        if ($this->settings['headers']) {
            $params['MetaData'] = [];
            $headers = explode("\n", $this->settings['headers']);
            foreach($headers as $header) {
                $parts = explode(':', $header);
                if (count($parts) === 2) {
                    if (strtolower($parts[0]) === 'cache-control' || strtolower($parts[0]) === 'cachecontrol') {
                        $params['CacheControl'] = $parts[1];
                    } else {
                        $params['MetaData'][$parts[0]] = $parts[1];
                    }
                }
            }
        }

        $this->s3->putObject($params);

        return $this->getFileURL($remoteFilename);
    }

    /**
     * @param string $remoteFilename
     * @param string $localFilename
     *
     * @return int
     * @throws Exception
     */
    public function downloadFile(string $remoteFilename, string $localFilename): int
    {
        list($bucket, $key) = $this->extractBucketAndKey($remoteFilename);

        $this->connect();
        $results = $this->s3->getObject([
            'Bucket' => $bucket,
            'Key'    => $key
        ]);

        return file_put_contents($localFilename, $results['Body']);
    }

    /**
     * @param string $remoteFilename
     *
     * @return bool
     * @throws Exception
     */
    public function deleteFile(string $remoteFilename): bool
    {
        list($bucket, $key) = $this->extractBucketAndKey($remoteFilename);

        $this->connect();
        $this->s3->deleteObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);

        return true;
    }

    /**
     * @param string $remoteOldName
     * @param string $remoteNewName
     *
     * @return bool
     * @throws Exception
     */
    public function rename(string $remoteOldName, string $remoteNewName): bool
    {
        list($bucket, $keyNew) = $this->extractBucketAndKey($remoteNewName);

        $this->connect();
        $this->s3->copyObject([
            'Bucket'     => $bucket,
            'CopySource' => $remoteOldName,
            'Key'        => $keyNew,
            'ACL'        => $this->settings['acl']
        ]);
        $this->deleteFile($remoteOldName);

        return true;
    }

    /**
     * @param string $remoteFilename
     *
     * @return string
     * @throws Exception
     */
    public function getFileURL(string $remoteFilename): string
    {
        list($bucket, $key) = $this->extractBucketAndKey($remoteFilename);

        $this->connect();
        $results = $this->s3->getObject([
            'Bucket' => $bucket,
            'Key'    => $key
        ]);

        if (isset($results['@metadata']) && !empty($results['@metadata']['effectiveUri'])) {
            return $results['@metadata']['effectiveUri'];
        }

        throw new Exception("URL not found for $remoteFilename.");
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $remoteFilename): bool
    {
        list($bucket, $key) = $this->extractBucketAndKey($remoteFilename);

        $this->connect();

        try {
            $results = $this->s3->getObject(
                [
                    'Bucket' => $bucket,
                    'Key'    => $key
                ]
            );
        } catch (Exception $e) {
            return false;
        }

        if (isset($results['@metadata']) && !empty($results['@metadata']['effectiveUri'])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    private function extractBucketAndKey(string $dir): array
    {
        $dir   = str_replace('\\', '/', $dir);
        $dir   = ltrim($dir, '/');
        $parts = explode('/', $dir, 2);
        if (count($parts) !== 2) {
            $parts[1] = '';
            // throw new InvalidArgumentException('No directory specified.');
        }

        $parts[0] = trim($parts[0], '/\\');

        return $parts;
    }
}
