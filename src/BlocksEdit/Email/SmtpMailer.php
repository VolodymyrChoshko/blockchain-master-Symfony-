<?php
namespace BlocksEdit\Email;

use Entity\DevEmail;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Repository\DevEmailRepository;
use Swift_Mailer;
use Swift_Message;
use Swift_Plugins_LoggerPlugin;
use Swift_Plugins_Loggers_ArrayLogger;
use Swift_SmtpTransport;

/**
 * Class SmtpMailer
 */
class SmtpMailer implements MailerInterface
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
        $this->emailRepository = $emailRepository;
        $this->config          = $config;
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
        $transport = (new Swift_SmtpTransport(
            $this->config['host'],
            $this->config['port'],
            $this->config['encryption']
        ))
            ->setUsername($this->config['user'])
            ->setPassword($this->config['pass']);
        $mailer = new Swift_Mailer($transport);

        $logger = new Swift_Plugins_Loggers_ArrayLogger();
        $mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
        $result = $mailer->send($message);
        $logs   = $this->sanitizeLogs($logger->dump());

        try {
            $devEmail = (new DevEmail())
                ->setTo(json_encode($message->getTo()))
                ->setFrom(json_encode($message->getFrom()))
                ->setReplyTo(json_encode($message->getReplyTo()))
                ->setBody($message->getBody())
                ->setSubject($message->getSubject())
                ->setContentType($message->getBodyContentType())
                ->setHostResponse($logs);
            $this->emailRepository->insert($devEmail);
        } catch (Exception $e) {}

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function quickSend($to, string $subject, string $message, string $contentType = 'text/html', string $replyTo = ''): int
    {
        if (!$contentType) {
            $contentType = 'text/html';
        }
        $message = $this->message($subject, $replyTo)
            ->setTo($to)
            ->setBody($message, $contentType);

        return $this->send($message);
    }

    /**
     * @param string $logs
     *
     * @return string
     */
    protected function sanitizeLogs(string $logs): string
    {
        $user   = $this->config['user'];
        $pass   = $this->config['pass'];
        $user64 = base64_encode($user);
        $pass64 = base64_encode($pass);
        $logs   = str_replace([$user, $pass, $user64, $pass64], '', $logs);
        $lines  = explode("\n", str_replace("\r\n", "\n", $logs));
        $lines  = array_filter($lines);
        $lines  = array_slice($lines, -12);

        return join("\n", $lines);
    }
}
