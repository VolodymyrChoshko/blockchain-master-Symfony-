<?php
namespace BlocksEdit\Media;

/**
 * Interface CDNInterface
 */
interface CDNInterface
{
    const SYSTEM_IMAGES = 'images';
    const SYSTEM_SCREENSHOTS = 'screenshots';
    const SYSTEM_AVATARS = 'avatars';
    const SYSTEM_TESTING = 'testing';

    /**
     * @param int $oid
     *
     * @return CDNInterface
     */
    public function prefixed(int $oid): CDNInterface;

    /**
     * Uploads the data to the CDN and returns the URL
     *
     * @param string $system
     * @param string $path
     * @param string $data
     * @param callable|null   $progressFunc ($downloadTotalSize, $downloadSizeSoFar, $uploadTotalSize, $uploadSizeSoFar)
     *
     * @return string
     */
    public function upload(string $system, string $path, string $data, ?callable $progressFunc = null): string;

    /**
     * @param string $system
     * @param array  $paths
     * @param array  $localFiles
     * @param int    $max
     *
     * @return array
     */
    public function batchUpload(string $system, array $paths, array $localFiles, int $max = 5): array;

    /**
     * @param string $sourceSystem
     * @param string $sourcePath
     * @param string $targetSystem
     * @param string $targetPath
     *
     * @return string
     */
    public function copy(string $sourceSystem, string $sourcePath, string $targetSystem, string $targetPath): string;

    /**
     * @param string $url
     * @param string $targetSystem
     * @param string $targetPath
     *
     * @return string
     */
    public function copyByURL(string $url, string $targetSystem, string $targetPath): string;

    /**
     * @param string $sourceSystem
     * @param array  $sourcePaths
     * @param string $targetSystem
     * @param array  $targetPaths
     * @param int    $max
     *
     * @return array
     */
    public function batchCopy(string $sourceSystem, array $sourcePaths, string $targetSystem, array $targetPaths, int $max = 5): array;

    /**
     * @param array  $urls
     * @param string $targetSystem
     * @param array  $targetPaths
     * @param int    $max
     *
     * @return array
     */
    public function batchCopyByURL(array $urls, string $targetSystem, array $targetPaths, int $max = 5): array;

    /**
     * Returns the contents of the file at the given path
     *
     * @param string $system
     * @param string $path
     *
     * @return string
     */
    public function download(string $system, string $path): string;

    /**
     * Deletes the file at the given path
     *
     * @param string $system
     * @param string $path
     *
     * @return bool
     */
    public function remove(string $system, string $path): bool;

    /**
     * @param string $url
     *
     * @return bool
     */
    public function removeByURL(string $url): bool;

    /**
     * @param string $system
     * @param array  $paths
     * @param int    $max
     *
     * @return bool
     */
    public function batchRemove(string $system, array $paths, int $max = 5): bool;

    /**
     * @param array $urls
     * @param int   $max
     *
     * @return bool
     */
    public function batchRemoveByURL(array $urls, int $max = 5): bool;

    /**
     * Deletes the directory at the given path
     *
     * @param string $system
     * @param string $path
     *
     * @return bool
     */
    public function removeDir(string $system, string $path): bool;

    /**
     * @param string $system
     * @param string $path
     *
     * @return array
     */
    public function listDir(string $system, string $path): array;

    /**
     * @param string $system
     * @param string $path
     *
     * @return array
     */
    public function listDirRaw(string $system, string $path): array;

    /**
     * @param string $system
     * @param string $dir
     *
     * @return array
     */
    public function createDir(string $system, string $dir): array;

    /**
     * Returns the URL for the given path
     *
     * When $verify is true the method throws an exception if the file
     * does not exist in S3.
     *
     * @param string $system
     * @param string $path
     * @param bool   $verify
     *
     * @return string
     */
    public function resolveUrl(string $system, string $path, bool $verify = false): string;

    /**
     * @param string $url
     *
     * @return array
     */
    public function getSystemAndPathFromURL(string $url): array;
}
