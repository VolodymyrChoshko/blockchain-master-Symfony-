<?php
namespace Service;

use BlocksEdit\Service\SqsMessageQueue;

/**
 * Class LibraryThumbnailsMessageQueue
 */
class LibraryThumbnailsMessageQueue extends SqsMessageQueue
{
    /**
     * @inheritDoc
     */
    public function getSqsQueueUrl(): string
    {
        return $this->config->sqs['queues']['libraryThumbnails']['url'];
    }

    /**
     * @inheritDoc
     */
    public function getSqsMessageGroupId(): string
    {
        return $this->config->sqs['queues']['libraryThumbnails']['group'];
    }

    /**
     * @inheritDoc
     */
    public function isFIFO(): bool
    {
        return true;
    }
}
