<?php
namespace BlocksEdit\Service;

use Aws\Sqs\SqsClient;
use BlocksEdit\Config\Config;
use BlocksEdit\Util\Strings;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class SqsMessageQueue
 */
abstract class SqsMessageQueue implements MessageQueueInterface
{
    use LoggerAwareTrait;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param Config          $config
     * @param LoggerInterface $logger
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->setLogger($logger);
    }

    /**
     * @return string
     */
    abstract public function getSqsQueueUrl(): string;

    /**
     * @return string
     */
    abstract public function getSqsMessageGroupId(): string;

    /**
     * @return bool
     */
    abstract public function isFIFO(): bool;

    /**
     * @inheritDoc
     */
    public function send($messageBody): string
    {
        $this->logger->debug(get_called_class() . ' ' . $this->getSqsQueueUrl() . ' sending SQL job: ' . json_encode($messageBody));

        if ($this->isFIFO()) {
            $resp = $this->getSqsClient()->sendMessage([
                'QueueUrl'               => $this->getSqsQueueUrl(),
                'MessageBody'            => json_encode($messageBody),
                'MessageGroupId'         => $this->getSqsMessageGroupId(),
                'MessageDeduplicationId' => Strings::uuid()
            ]);
        } else {
            $resp = $this->getSqsClient()->sendMessage([
                'QueueUrl'    => $this->getSqsQueueUrl(),
                'MessageBody' => json_encode($messageBody),
            ]);
        }
        $this->logger->debug(get_called_class() . ' message id: ' . $resp->get('MessageId'));

        return $resp->get('MessageId');
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        $result = $this->getSqsClient()->receiveMessage([
            'QueueUrl'       => $this->getSqsQueueUrl(),
            'MessageGroupId' => $this->getSqsMessageGroupId(),
        ]);
        $messages = $result->get('Messages');
        if (!$messages) {
            return null;
        }

        return new Message(
            json_decode($messages[0]['Body'], true),
            $messages[0]
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(Message $message): bool
    {
        $meta = $message->getMeta();
        $this->getSqsClient()->deleteMessage([
            'QueueUrl'      => $this->getSqsQueueUrl(),
            'ReceiptHandle' => $meta['ReceiptHandle']
        ]);

        return true;
    }

    /**
     * @return SqsClient
     */
    protected function getSqsClient(): SqsClient
    {
        return SqsClient::factory($this->config->aws);
    }
}
