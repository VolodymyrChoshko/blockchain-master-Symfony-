<?php
namespace BlocksEdit\Email;

use Entity\DevEmail;
use Repository\DevEmailRepository;
use Psr\Log\LoggerAwareTrait;
use Swift_Message;

/**
 * Class DevMailer
 */
class DevMailer implements MailerInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var DevEmailRepository
     */
    protected $emailRepository;

    /**
     * Constructor
     *
     * Symfony cannot auto-wire this without the default value.
     *
     * @param DevEmailRepository $emailRepository
     * @param array              $config
     */
    public function __construct(DevEmailRepository $emailRepository, array $config = [])
    {
        $this->config          = $config;
        $this->emailRepository = $emailRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $subject, $replyTo = ''): Swift_Message
    {
        $message = (new Swift_Message($subject))
            ->setFrom($this->config['from'], $this->config['from_name']);
        if ($replyTo) {
            $message->setReplyTo($replyTo);
        }

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function send(Swift_Message $message): int
    {
        $devEmail = (new DevEmail())
            ->setTo(json_encode($message->getTo()))
            ->setFrom(json_encode($message->getFrom()))
            ->setReplyTo(json_encode($message->getReplyTo()))
            ->setBody($message->getBody())
            ->setSubject($message->getSubject())
            ->setContentType($message->getBodyContentType());
        $this->emailRepository->insert($devEmail);

        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function quickSend($to, string $subject, string $message, $contentType = 'text/html', $replyTo = ''): int
    {
        if (!$contentType) {
            $contentType = 'text/html';
        }
        $message = $this->message($subject, $replyTo)
            ->setTo($to)
            ->setBody($message, $contentType);

        return $this->send($message);
    }
}
