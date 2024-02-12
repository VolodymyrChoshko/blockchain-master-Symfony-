<?php
namespace BlocksEdit\Integrations;

use BlocksEdit\Integrations\Exception\OAuthUnauthorizedException;
use BlocksEdit\Integrations\Filesystem\FileInfo;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;

/**
 * Interface FilesystemIntegrationInterface
 */
interface FilesystemIntegrationInterface
{
    /**
     * @return string
     */
    public function getHomeDirectoryPlaceholder(): string;

    /**
     * @return string
     */
    public function getDefaultHomeDirectory(): string;

    /**
     * @param string $dir
     *
     * @return string
     */
    public function translateHomeDirectory(string $dir): string;

    /**
     * @return bool
     */
    public function shouldExportOriginalImageUrls(): bool;

    /**
     * @param array|string $email
     *
     * @return string
     */
    public function formatRemoteFilename($email): string;

    /**
     * @return bool
     */
    public function supportsBatchUpload(): bool;

    /**
     * @return bool
     * @throws Exception
     * @throws OAuthUnauthorizedException
     * @throws InvalidArgumentException
     */
    public function connect(): bool;

    /**
     * @return bool
     * @throws Exception
     */
    public function disconnect(): bool;

    /**
     * @return bool
     * @throws Exception
     */
    public function isConnected(): bool;

    /**
     * @param string $dir
     *
     * @return FileInfo[]
     * @throws Exception
     * @throws OAuthUnauthorizedException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function getDirectoryListing(string $dir): array;

    /**
     * @param string $dir
     *
     * @return bool
     * @throws Exception
     * @throws OAuthUnauthorizedException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function createDirectory(string $dir): bool;

    /**
     * @param string $dir
     *
     * @return bool
     * @throws Exception
     * @throws OAuthUnauthorizedException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function deleteDirectory(string $dir): bool;

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
     * @throws OAuthUnauthorizedException
     * @throws InvalidArgumentException
     */
    public function uploadFile(
        string $remoteFilename,
        string $localFilename,
        string $assetType,
        int $assetID,
        $subject = '',
        array $extra = []
    ): string;

    /**
     * @param array  $remoteFilenames
     * @param array  $localFilenames
     * @param string $assertType
     * @param array  $assetIDs
     * @param array  $subjects
     * @param array  $extra
     *
     * @return array
     */
    public function batchUploadFiles(
        array $remoteFilenames,
        array $localFilenames,
        string $assertType,
        array $assetIDs,
        array $subjects = [],
        array $extra = []
    ): array;

    /**
     * @param string $remoteFilename
     * @param string $localFilename
     *
     * @return int
     * @throws Exception
     * @throws OAuthUnauthorizedException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function downloadFile(string $remoteFilename, string $localFilename): int;

    /**
     * @param string $remoteFilename
     *
     * @return bool
     * @throws Exception
     * @throws OAuthUnauthorizedException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function deleteFile(string $remoteFilename): bool;

    /**
     * @param string $remoteOldName
     * @param string $remoteNewName
     *
     * @return bool
     * @throws Exception
     * @throws OAuthUnauthorizedException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function rename(string $remoteOldName, string $remoteNewName): bool;

    /**
     * @param string $remoteFilename
     *
     * @return bool
     * @throws Exception
     * @throws OAuthUnauthorizedException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function exists(string $remoteFilename): bool;

    /**
     * @param string $remoteFilename
     *
     * @return string
     * @throws Exception
     * @throws OAuthUnauthorizedException
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function getFileURL(string $remoteFilename): string;
}
