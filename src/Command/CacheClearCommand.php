<?php
namespace Command;

use BlocksEdit\Cache\CacheInterface;
use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Database\EntityManager;
use BlocksEdit\IO\Paths;
use DirectoryIterator;
use Entity;

/**
 * Class CacheClearCommand
 */
class CacheClearCommand extends Command
{
    static $name = 'cache:clear';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Clears the app cache.';
    }

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $cacheDir = realpath(__DIR__ . '/../../var/cache');
        if (!$cacheDir) {
            die('Cache directory does not exist.');
        }

        @unlink(Paths::combine($cacheDir, 'config.php'));
        @unlink(Paths::combine($cacheDir, 'routes.php'));
        @unlink(Paths::combine($cacheDir, 'middleware.php'));
        @unlink(Paths::combine($cacheDir, 'container.php'));
        $this->em->clearCache();

        $twigCache = $cacheDir . '/twig';
        if (file_exists($twigCache)) {
            $this->container->get(Paths::class)->remove($twigCache);
        }
        if (!file_exists($twigCache)) {
            @mkdir($twigCache);
        }
        @chmod($twigCache, 0777);

        $output->writeLine('Cache cleared');
    }
}
