<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Email\EmailSender;
use DateInterval;
use DateTime;
use Entity\OnboardingSent;
use Repository\UserRepository;
use Repository\NoSendRepository;
use Repository\OnboardingSentRepository;

/**
 * Class EmailDailySend
 */
class EmailDailySendCommand extends Command
{
    static $name = 'email:daily:send';

    /**
     * @var EmailSender
     */
    protected $emailSender;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var NoSendRepository
     */
    protected $noSendRepository;

    /**
     * @var OnboardingSentRepository
     */
    protected $onboardingSentRepository;

    /**
     * Constructor
     *
     * @param EmailSender              $emailSender
     * @param UserRepository           $userRepository
     * @param NoSendRepository         $noSendRepository
     * @param OnboardingSentRepository $onboardingSentRepository
     */
    public function __construct(
        EmailSender $emailSender,
        UserRepository $userRepository,
        NoSendRepository $noSendRepository,
        OnboardingSentRepository $onboardingSentRepository
    ) {
        $this->emailSender = $emailSender;
        $this->userRepository = $userRepository;
        $this->noSendRepository = $noSendRepository;
        $this->onboardingSentRepository = $onboardingSentRepository;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Sends daily on-boarding emails.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $now = new DateTime();

        $emails = [
            [
                'days' => 3,
                'view' => EmailSender::WHAT_YOU_CAN_DO
            ],
            [
                'days' => 5,
                'view' => EmailSender::WHAT_YOU_CAN_DO_EXAMPLE
            ],
            [
                'days' => 7,
                'view' => EmailSender::HELP_AND_SUPPORT
            ],
        ];

        foreach($emails as $email) {
            $days = $email['days'];
            $view = $email['view'];

            $dateFind = clone $now;
            $dateFind->sub(new DateInterval("P${days}D"));
            $output->writeLine('Checking accounts created on %s.', $dateFind->format('Y-m-d'));

            foreach($this->userRepository->findByJoinDate($dateFind) as $user) {
                $email = $user['usr_email'];
                if (
                    !$this->noSendRepository->isSendable($email)
                    || $this->onboardingSentRepository->isSent($email, $view)
                ) {
                    continue;
                }

                $output->writeLine('Sending email %s to %s.', $view, $email);
                $this->emailSender->send($email, $view);
                $obs = (new OnboardingSent())
                    ->setEmail($email)
                    ->setView($view);
                $this->onboardingSentRepository->insert($obs);
            }
        }
    }
}
