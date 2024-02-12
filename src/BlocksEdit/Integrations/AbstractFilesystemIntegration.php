<?php
namespace BlocksEdit\Integrations;

use BlocksEdit\Util\Strings;
use RuntimeException;

/**
 * Class AbstractFilesystemIntegration
 */
abstract class AbstractFilesystemIntegration
    extends AbstractIntegration
    implements FilesystemIntegrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function supportsBatchUpload(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
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
        if (!$this->supportsBatchUpload()) {
            throw new RuntimeException(
                sprintf('Integration %s does not support batch uploading.', get_called_class())
            );
        }

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function translateHomeDirectory(string $dir): string
    {
        return $dir;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldExportOriginalImageUrls(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function formatRemoteFilename($email): string
    {
        if (is_array($email)) {
            $name = Strings::getSlug($email['ema_title']);

            return "${name}.html";
        }

        return $email;
    }
}
