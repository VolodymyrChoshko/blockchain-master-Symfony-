<?php
namespace BlocksEdit\Logging;

use BlocksEdit\Email\MailerInterface;
use Exception;
use Monolog\Handler\MailHandler;
use Monolog\Logger;

/**
 * Class Signl4LogHandler
 */
class Signl4LogHandler extends MailHandler
{
    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * Constructor
     *
     * @param MailerInterface $mailer
     * @param int             $level
     * @param bool            $bubble
     */
    public function __construct(
        MailerInterface $mailer,
        int $level = Logger::DEBUG,
        bool $bubble = true
    )
    {
        parent::__construct($level, $bubble);

        $this->mailer = $mailer;
    }

    /**
     * {@inheritDoc}
     */
    protected function send($content, array $records)
    {
        if (strpos($content, 'GuzzleHttp') !== false) {
            return;
        }
        try {
            $this->mailer->quickSend(
                'kb95sknvbe@mail.signl4.com',
                'Blocks Edit Alert',
                $content,
                'text/plain'
            );
        } catch (Exception $e) {}
    }
}
