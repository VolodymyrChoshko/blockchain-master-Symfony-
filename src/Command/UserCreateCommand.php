<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use Entity\User;
use Repository\UserRepository;

/**
 * Class UserCreateCommand
 */
class UserCreateCommand extends Command
{
    static $name = 'user:create';

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
        return 'Creates a new user.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $email = $input->read('Email address');
        if (!$email) {
            die(1);
        }
        $name = $input->read('Name');
        if (!$name) {
            die(1);
        }
        $password = $input->read('Password');
        if (!$password) {
            die(1);
        }

        $user = (new User())
            ->setName($name)
            ->setEmail($email)
            ->setPassPlain($password)
            ->setOrganization('')
            ->setJob('')
            ->setNewsletter(false);
        $this->userRepository->insert($user);

        $output->writeLine('User created with ID %d.', $user->getId());
    }
}
