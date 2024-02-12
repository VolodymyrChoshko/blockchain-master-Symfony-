<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Database\EntityManager;
use BlocksEdit\System\ClassFinderInterface;

/**
 * Class CacheWarmupCommand
 */
class CacheWarmupCommand extends Command
{
    static $name = 'cache:warmup';

    /**
     * @var ClassFinderInterface
     */
    protected $classFinder;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param ClassFinderInterface $classFinder
     * @param EntityManager        $em
     */
    public function __construct(
        ClassFinderInterface $classFinder,
        EntityManager $em
    )
    {
        $this->classFinder = $classFinder;
        $this->em          = $em;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Pre-caches often used values.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $output->writeLine('Warming up repositories.');
        $repositories = $this->classFinder->getNamespaceClasses('Repository');
        foreach($repositories as $repository) {
            $this->em->getMeta($repository);
        }

        $output->writeLine('Done.');
    }
}
