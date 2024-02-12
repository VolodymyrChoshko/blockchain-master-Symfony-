<?php
namespace BlocksEdit\Email;

use Exception;
use Swift_Message;

/**
 * Class Mailer
 */
interface MailerInterface
{
    /**
     * @param string $subject
     * @param string $replyTo
     *
     * @return Swift_Message
     */
    public function message(string $subject, $replyTo = ''): Swift_Message;

    /**
     * @param Swift_Message $message
     *
     * @return int
     * @throws Exception
     */
    public function send(Swift_Message $message): int;

    /**
     * @param string|array $to
     * @param string       $subject
     * @param string       $message
     * @param string       $contentType
     * @param string       $replyTo
     *
     * @return int
     * @throws Exception
     */
    public function quickSend($to, string $subject, string $message, string $contentType = 'text/html', string $replyTo = ''): int;
}
