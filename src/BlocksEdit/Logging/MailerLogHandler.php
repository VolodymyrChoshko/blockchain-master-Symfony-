<?php
namespace BlocksEdit\Logging;

use BlocksEdit\Email\MailerInterface;
use Exception;
use Monolog\Handler\MailHandler;
use Monolog\Logger;
use Repository\UserRepository;

/**
 * Class MailerLogHandler
 */
class MailerLogHandler extends MailHandler
{
    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * Constructor
     *
     * @param MailerInterface $mailer
     * @param UserRepository  $userRepository
     * @param int             $level
     * @param bool            $bubble
     */
    public function __construct(
        MailerInterface $mailer,
        UserRepository $userRepository,
        int $level = Logger::DEBUG,
        bool $bubble = true
    )
    {
        parent::__construct($level, $bubble);

        $this->mailer         = $mailer;
        $this->userRepository = $userRepository;
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
            foreach ($this->userRepository->findBySiteAdmin() as $user) {
                $this->mailer->quickSend(
                    $user['usr_email'],
                    'Blocks Edit Alert',
                    $content,
                    'text/plain'
                );
            }
        } catch (Exception $e) {}
    }
}
