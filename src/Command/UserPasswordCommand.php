<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use Repository\UserRepository;

/**
 * Class UserPasswordCommand
 */
class UserPasswordCommand extends Command
{
    static $name = 'user:password';

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * Constructor
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Changes a user password.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $email = $input->read('User email address');
        if (!$email) {
            die(1);
        }
        $password = $input->read('New password');
        if (!$password) {
            die(1);
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            echo "User not found.\n";
            die(1);
        }

        $this->userRepository->changePassword($user['usr_id'], $password);
        $output->writeLine('Password updated');
    }
}
