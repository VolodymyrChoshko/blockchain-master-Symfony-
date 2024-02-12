<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use PHPGangsta_GoogleAuthenticator;
use Repository\UserRepository;

/**
 * Class UserAdminMake
 */
class UserAdminMake extends Command
{
    static $name = 'user:admin:make';

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @param UserRepository $userRepository
     *
     * @return void
     */
    public function __constructor(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Makes a user an admin.';
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

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            echo "User not found.\n";
            die(1);
        }

        $label = 'Blocks Edit';
        if (gethostname() !== 'ip-172-31-100-96') {
            if (strpos(__DIR__, 'stagingapp.blocksedit.com') !== false) {
                $label .= ' Staging';
            } else {
                $label .= ' Dev';
            }
        }

        $ga        = new PHPGangsta_GoogleAuthenticator();
        $secret    = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($label, $secret);
        $output->writeLine($qrCodeUrl);

        do {
            $userCode = $input->read('Code');
        } while (!$ga->verifyCode($secret, $userCode));

        $this->userRepository->updateSingle($user['usr_id'], 'usr_is_site_admin', '1');
        $this->userRepository->updateSingle($user['usr_id'], 'usr_2fa_secret', $secret);
        $output->writeLine('Admin created.');
    }
}
