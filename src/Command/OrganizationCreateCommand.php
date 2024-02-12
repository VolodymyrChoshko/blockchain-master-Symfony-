<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use Entity\Organization;
use Entity\OrganizationAccess;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Repository\OrganizationsRepository;

/**
 * Class OrganizationCreateCommand
 */
class OrganizationCreateCommand extends Command
{
    static $name = 'orgs:create';

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var OrganizationsRepository
     */
    protected $organizationsRepository;

    /**
     * @var OrganizationAccessRepository
     */
    protected $organizationAccessRepository;

    /**
     * Constructor
     *
     * @param UserRepository               $userRepository
     * @param OrganizationsRepository      $organizationsRepository
     * @param OrganizationAccessRepository $organizationAccessRepository
     */
    public function __construct(
        UserRepository $userRepository,
        OrganizationsRepository $organizationsRepository,
        OrganizationAccessRepository $organizationAccessRepository
    )
    {
        $this->userRepository               = $userRepository;
        $this->organizationsRepository      = $organizationsRepository;
        $this->organizationAccessRepository = $organizationAccessRepository;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Creates a new organization.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $name = $input->read('Organization name');
        if (!$name) {
            die(1);
        }
        $ownerEmail = $input->read('Owner email address');
        if (!$ownerEmail) {
            die(1);
        }
        $adminEmail = $input->read('Admin email address (optional)');

        $user = $this->userRepository->findByEmail($ownerEmail, true);
        if (!$user) {
            $output->errorLine('Owner not found.');
            die(1);
        }

        $org = (new Organization())
            ->setName($name);
        $this->organizationsRepository->insert($org);

        $orgAccess = (new OrganizationAccess())
            ->setUser($user)
            ->setOrganization($org)
            ->setAccess(OrganizationAccess::OWNER);
        $this->organizationAccessRepository->insert($orgAccess);

        if ($adminEmail) {
            $user = $this->container->get(UserRepository::class)->findByEmail($adminEmail);
            if (!$user) {
                $output->errorLine('Admin not found.');
                die(1);
            }

            $orgAccess = (new OrganizationAccess())
                ->setUser($user)
                ->setOrganization($org)
                ->setAccess(OrganizationAccess::ADMIN);
            $this->organizationAccessRepository->insert($orgAccess);
        }

        $output->writeLine('Organization created.');
    }
}
