<?php
namespace BlocksEdit\Service;

/**
 * Interface MessageQueueInterface
 */
interface MessageQueueInterface
{
    /**
     * @param mixed $messageBody
     *
     * @return string
     */
    public function send($messageBody): string;

    /**
     * @return Message|null
     */
    public function receive(): ?Message;

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function delete(Message $message): bool;
}
