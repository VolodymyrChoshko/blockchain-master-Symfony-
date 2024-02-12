<?php
namespace Service;

use BlocksEdit\Service\SqsMessageQueue;

/**
 * Class NotificationsMessageQueue
 */
class NotificationsMessageQueue extends SqsMessageQueue
{
    /**
     * @inheritDoc
     */
    public function getSqsQueueUrl(): string
    {
        return $this->config->sqs['queues']['notifications']['url'];
    }

    /**
     * @inheritDoc
     */
    public function getSqsMessageGroupId(): string
    {
        return $this->config->sqs['queues']['notifications']['group'];
    }

    /**
     * @inheritDoc
     */
    public function isFIFO(): bool
    {
        return false;
    }
}
