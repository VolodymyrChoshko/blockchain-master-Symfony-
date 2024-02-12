<?php
namespace Service;

use BlocksEdit\Service\SqsMessageQueue;

/**
 * Class LayoutUpgradeMessageQueue
 */
class LayoutUpgradeMessageQueue extends SqsMessageQueue
{
    /**
     * @inheritDoc
     */
    public function getSqsQueueUrl(): string
    {
        return $this->config->sqs['queues']['layoutsUpgrade']['url'];
    }

    /**
     * @inheritDoc
     */
    public function getSqsMessageGroupId(): string
    {
        return $this->config->sqs['queues']['layoutsUpgrade']['group'];
    }

    /**
     * @inheritDoc
     */
    public function isFIFO(): bool
    {
        return true;
    }
}
