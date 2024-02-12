<?php
namespace BlocksEdit\Media;

use BlocksEdit\Util\Strings;
use BlocksEdit\Http\Mime;
use Aws\S3\S3Client;
use DateTime;
use GuzzleHttp\Promise\Each;
use RuntimeException;

/**
 * Class AmazonCDN
 */
class AmazonCDN extends AbstractCDN
{
    /**
     * @var S3Client
     */
    protected $s3;

    /**
     * @var int
     */
    protected $oid = 0;

    /**
     * Constructor
     *
     * @param S3Client $s3
     * @param array    $config
     * @param Mime     $mimeTypes
     * @param int      $oid
     */
    public function __construct(S3Client $s3, array $config, Mime $mimeTypes, int $oid = 0)
    {
        parent::__construct($config, $mimeTypes);
        $this->s3  = $s3;
        $this->oid = $oid;
    }

    /**
     * {@inheritDoc}
     */
    public function prefixed(int $oid): CDNInterface
    {
        return new AmazonCDN($this->s3, $this->config, $this->mimeTypes, $oid);
    }

    /**
     * {@inheritDoc}
     */
    public function copy(string $sourceSystem, string $sourcePath, string $targetSystem, string $targetPath): string
    {
        $sourceConfig = $this->getSystemConfig($sourceSystem);
        $targetConfig = $this->getSystemConfig($targetSystem);
        $sourceKey    = $this->getSystemPath($sourceSystem, $sourcePath, false);
        $targetKey    = $this->getSystemPath($targetSystem, $targetPath);

        $req = [
            'Bucket'       => $targetConfig['s3']['bucket'],
            'Key'          => $targetKey,
            'ContentType'  => $this->mimeTypes->getFromFilename($targetPath),
            'CacheControl' => $targetConfig['s3']['cacheControl'],
            'CopySource'   => $sourceConfig['s3']['bucket'] . '/' . $sourceKey,
            'ACL'          => 'public-read',
        ];

        $this->s3->copyObject($req);
        $targetKey = $this->removeSystemFromPath($targetKey);

        return $this->resolveUrl($targetSystem, $targetKey);
    }

    /**
     * {@inheritDoc}
     */
    public function copyByURL(string $url, string $targetSystem, string $targetPath): string
    {
        list($sourceSystem, $sourcePath) = $this->getSystemAndPathFromURL($url);

        return $this->copy($sourceSystem, $sourcePath, $targetSystem, $targetPath);
    }

    /**
     * {@inheritDoc}
     */
    public function batchCopy(string $sourceSystem, array $sourcePaths, string $targetSystem, array $targetPaths, int $max = 5): array
    {
        $targetKeys = [];
        foreach($targetPaths as $targetPath) {
            $targetKeys[] = $this->getSystemPath($targetSystem, $targetPath);
        }

        $copy = function($sourcePaths) use($targetKeys, $targetPaths, $sourceSystem, $targetSystem) {
            foreach($sourcePaths as $i => $sourcePath) {
                $sourceConfig = $this->getSystemConfig($sourceSystem);
                $targetConfig = $this->getSystemConfig($targetSystem);
                $sourceKey    = $this->getSystemPath($sourceSystem, $sourcePath, false);
                yield $this->s3->copyObjectAsync([
                    'Bucket'       => $targetConfig['s3']['bucket'],
                    'Key'          => $targetKeys[$i],
                    'ContentType'  => $this->mimeTypes->getFromFilename($targetPaths[$i]),
                    'CacheControl' => $targetConfig['s3']['cacheControl'],
                    'CopySource'   => $sourceConfig['s3']['bucket'] . '/' . $sourceKey,
                    'ACL'          => 'public-read',
                ]);
            }
        };

        $urls = [];
        Each::ofLimitAll(
            $copy($sourcePaths),
            $max,
            function($response, $index) use(&$results, $targetSystem, $targetKeys) {
                $target = $this->removeSystemFromPath($targetKeys[$index]);
                $results[$index] = $this->resolveUrl($targetSystem, $target);
            }
        )->wait();

        ksort($urls);

        return $urls;
    }

    /**
     * {@inheritDoc}
     */
    public function batchCopyByURL(array $urls, string $targetSystem, array $targetPaths, int $max = 5): array
    {
        $targetKeys = [];
        foreach($targetPaths as $targetPath) {
            $targetKeys[] = $this->getSystemPath($targetSystem, $targetPath);
        }

        $copy = function($urls) use($targetKeys, $targetPaths, $targetSystem) {
            foreach($urls as $i => $url) {
                list($sourceSystem, $sourcePath) = $this->getSystemAndPathFromURL($url);
                $sourceConfig = $this->getSystemConfig($sourceSystem);
                $targetConfig = $this->getSystemConfig($targetSystem);
                $sourceKey    = $this->getSystemPath($sourceSystem, $sourcePath, false);

                yield $this->s3->copyObjectAsync([
                    'Bucket'       => $targetConfig['s3']['bucket'],
                    'Key'          => $targetKeys[$i],
                    'ContentType'  => $this->mimeTypes->getFromFilename($targetPaths[$i]),
                    'CacheControl' => $targetConfig['s3']['cacheControl'],
                    'CopySource'   => $sourceConfig['s3']['bucket'] . '/' . $sourceKey,
                    'ACL'          => 'public-read',
                ]);
            }
        };

        $results = [];
        Each::ofLimitAll(
            $copy($urls),
            $max,
            function($response, $index) use(&$results, $targetSystem, $targetKeys) {
                $target = $this->removeSystemFromPath($targetKeys[$index]);
                $results[$index] = $this->resolveUrl($targetSystem, $target);
            }
        )->wait();

        ksort($results);

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function upload(string $system, string $path, string $data, ?callable $progressFunc = null): string
    {
        $config = $this->getSystemConfig($system);
        $key    = $this->getSystemPath($system, $path);

        $req = [
            'Bucket'       => $config['s3']['bucket'],
            'Key'          => $key,
            'Body'         => $data,
            'ContentType'  => $this->mimeTypes->getFromFilename($path),
            'CacheControl' => $config['s3']['cacheControl'],
            'ACL'          => 'public-read',
        ];
        if ($progressFunc) {
            $req['@http'] = [
                'progress' => $progressFunc
            ];
        }

        $this->s3->putObject($req);
        $key = $this->removeSystemFromPath($key);

        return $this->resolveUrl($system, $key);
    }

    /**
     * {@inheritDoc}
     */
    public function batchUpload(string $system, array $paths, array $localFiles, int $max = 5): array
    {
        $targetKeys = [];
        foreach($paths as $targetPath) {
            $targetKeys[] = $this->getSystemPath($system, $targetPath);
        }

        $config = $this->getSystemConfig($system);
        $upload = function($targetKeys) use($localFiles, $config) {
            foreach($targetKeys as $i => $path) {
                yield $this->s3->putObjectAsync([
                    'Bucket'       => $config['s3']['bucket'],
                    'Key'          => $path,
                    'Body'         => file_get_contents($localFiles[$i]),
                    'ContentType'  => $this->mimeTypes->getFromFilename($path),
                    'CacheControl' => $config['s3']['cacheControl'],
                    'ACL'          => 'public-read',
                ]);
            }
        };

        $urls = [];
        Each::ofLimitAll(
            $upload($targetKeys),
            $max,
            function($response, $index) use(&$urls, $system, $targetKeys) {
                $target = $this->removeSystemFromPath($targetKeys[$index]);
                $urls[$index] = $this->resolveUrl($system, $target);
            }
        )->wait();

        ksort($urls);

        return $urls;
    }

    /**
     * {@inheritDoc}
     */
    public function download(string $system, string $path): string
    {
        $config = $this->getSystemConfig($system);
        $key    = $this->getSystemPath($system, $path);

        $results = $this->s3->getObject([
            'Bucket' => $config['s3']['bucket'],
            'Key'    => $key
        ]);

        return (string)$results['Body'];
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $system, string $path): bool
    {
        $config = $this->getSystemConfig($system);
        $key    = $this->getSystemPath($system, $path);

        $this->s3->deleteObject([
            'Bucket' => $config['s3']['bucket'],
            'Key'    => $key,
        ]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function removeByURL(string $url): bool
    {
        list($system, $path) = $this->getSystemAndPathFromURL($url);

        return $this->remove($system, $path);
    }

    /**
     * {@inheritDoc}
     */
    public function batchRemove(string $system, array $paths, int $max = 5): bool
    {
        $config = $this->getSystemConfig($system);
        $remove = function($paths) use($config, $system) {
            foreach($paths as $path) {
                $key = $this->getSystemPath($system, $path, false);
                yield $this->s3->deleteObjectAsync([
                    'Bucket' => $config['s3']['bucket'],
                    'Key'    => $key
                ]);
            }
        };

        Each::ofLimitAll(
            $remove($paths),
            $max
        )->wait();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function batchRemoveByURL(array $urls, int $max = 5): bool
    {
        $remove = function($urls) {
            foreach($urls as $url) {
                list($system, $path) = $this->getSystemAndPathFromURL($url);
                $config = $this->getSystemConfig($system);
                $key    = $this->getSystemPath($system, $path, false);
                yield $this->s3->deleteObjectAsync([
                    'Bucket' => $config['s3']['bucket'],
                    'Key'    => $key
                ]);
            }
        };

        Each::ofLimitAll(
            $remove($urls),
            $max
        )->wait();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function removeDir(string $system, string $path): bool
    {
        $config = $this->getSystemConfig($system);
        $key    = $this->getSystemPath($system, $path);
        $key    = rtrim($key, '/') . '/';
        $body   = [
            'Bucket' => $config['s3']['bucket'],
            'Prefix' => $key
        ];

        $response = $this->s3->listObjects($body);
        $response = $response->toArray();
        if (isset($response['Contents'])) {
            foreach ($response['Contents'] as $result) {
                $this->s3->deleteObject([
                    'Bucket' => $config['s3']['bucket'],
                    'Key'    => $result['Key']
                ]);
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function listDir(string $system, string $path): array
    {
        $config = $this->getSystemConfig($system);
        $key    = $this->getSystemPath($system, $path);
        $key    = rtrim($key, '/') . '/';

        $body = [
            'Bucket'    => $config['s3']['bucket'],
            'Delimiter' => '/',
            'Prefix'    => $key
        ];

        $response = $this->s3->listObjects($body);
        $response = $response->toArray();

        $files = [];
        if (isset($response['Contents'])) {
            foreach ($response['Contents'] as $content) {
                /** @var DateTime $date */
                $date  = $content['LastModified']->getTimestamp();
                $name  = $content['Key'];
                $size  = $content['Size'];
                $isDir = false;

                if (Strings::startsWith($name, $key)) {
                    $name = substr($name, strlen($key));
                }
                if (!$name) {
                    continue;
                }

                $files[] = compact('name', 'date', 'size', 'isDir');
            }
        }

        if (isset($response['CommonPrefixes'])) {
            foreach ($response['CommonPrefixes'] as $content) {
                $name = substr($content['Prefix'], 0, -1);
                if (Strings::startsWith($name, $key)) {
                    $name = substr($name, strlen($key));
                }
                if (!$name) {
                    continue;
                }

                $files[] = [
                    'name'  => $name,
                    'isDir' => true
                ];
            }
        }

        usort($files, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $files;
    }

    /**
     * {@inheritDoc}
     */
    public function listDirRaw(string $system, string $path): array
    {
        $config = $this->getSystemConfig($system);
        $key    = $this->getSystemPath($system, $path);
        $key    = rtrim($key, '/') . '/';

        $body = [
            'Bucket'    => $config['s3']['bucket'],
            'Prefix'    => $key
        ];

        $response = $this->s3->listObjects($body);
        $response = $response->toArray();

        $files = [];
        if (isset($response['Contents'])) {
            foreach($response['Contents'] as $info) {
                $files[] = substr($info['Key'], strpos($info['Key'], '/') + 1);
            }
        }

        return $files;
    }

    /**
     * {@inheritDoc}
     */
    public function createDir(string $system, string $dir): array
    {
        $config = $this->getSystemConfig($system);
        $key    = $this->getSystemPath($system, $dir);

        $this->s3->putObject([
            'Bucket' => $config['s3']['bucket'],
            'Key'    => $key . '/',
            'Body'   => '',
            'ACL'    => 'public-read',
        ]);

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function resolveUrl(string $system, string $path, bool $verify = false): string
    {
        $config = $this->getSystemConfig($system);
        $key    = $this->getSystemPath($system, $path, false);

        if ($verify) {
            if (!$this->s3->doesObjectExist($config['s3']['bucket'], $key)) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to resolve s3://%s/%s',
                        $config['s3']['bucket'],
                        $key
                    )
                );
            }
        }

        return sprintf('https://%s/%s', $config['cloudfront']['url'], $key);
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemAndPathFromURL(string $url): array
    {
        $parts = parse_url($url);
        $path  = ltrim($parts['path'], '/');
        list($system, $path) = explode('/', $path, 2);
        if (!isset($this->config[$system])) {
            throw new RuntimeException("Invalid CDN system $system.");
        }

        return [$system, $path];
    }

    /**
     * @param string $system
     *
     * @return array
     */
    protected function getSystemConfig(string $system): array
    {
        if (!isset($this->config[$system])) {
            throw new RuntimeException("Invalid CDN system $system.");
        }

        return $this->config[$system];
    }

    /**
     * @param string $system
     * @param string $path
     * @param bool   $forOrg
     *
     * @return string
     */
    protected function getSystemPath(string $system, string $path, bool $forOrg = true): string
    {
        $config = $this->getSystemConfig($system);
        $path   = trim($path, '/');
        if ($forOrg) {
            $path = $this->createOrgFilename($path);
        }

        return sprintf('%s/%s', trim($config['s3']['folder'], '/'), $path);
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    protected function createOrgFilename(string $filename): string
    {
        if (!$this->oid) {
            return $filename;
        }

        return $this->oid . '/' . Strings::uuid() . '-' . $filename;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function removeSystemFromPath(string $path): string
    {
        foreach($this->config as $system => $values) {
            if (strpos($path, $system) === 0) {
                return (string)substr($path, strlen($system));
            }
        }

        return $path;
    }
}
