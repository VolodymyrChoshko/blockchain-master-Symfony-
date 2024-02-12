<?php
namespace BlocksEdit\System;

use BlocksEdit\IO\Paths;
use BlocksEdit\Twig\TwigCompilerPass;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Exception;
use ProjectServiceContainer;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class ContainerFactory
 */
class ContainerFactory
{
    /**
     * @var ContainerInterface|null
     */
    protected static $instance = null;

    /**
     * @param string $env
     *
     * @return ContainerInterface
     * @throws Exception
     */
    public static function instance(string $env): ContainerInterface
    {
        if (self::$instance) {
            return self::$instance;
        }

        AnnotationRegistry::registerLoader('class_exists');

        $containerFile = self::getContainerFile();
        if ($env === 'prod' && file_exists($containerFile)) {
            require_once($containerFile);
            /** @phpstan-ignore-next-line */
            self::$instance = new ProjectServiceContainer();
        } else {
            self::$instance = self::create($env);
            $dumper = new PhpDumper(self::$instance);
            file_put_contents($containerFile, $dumper->dump());
            @chmod($containerFile, 0777);
        }

        return self::$instance;
    }

    /**
     * @param string $env
     *
     * @return void
     * @throws Exception
     */
    public static function dump(string $env)
    {
        $containerFile = self::getContainerFile();
        self::$instance = self::create($env);
        $dumper = new PhpDumper(self::$instance);
        file_put_contents($containerFile, $dumper->dump());
        @chmod($containerFile, 0777);
    }

    /**
     * @param string $env
     *
     * @return ContainerBuilder
     * @throws Exception
     */
    protected static function create(string $env): ContainerBuilder
    {
        $projectDir = self::getProjectDir();
        $cacheDir   = self::getCacheDir();
        $configDir  = self::getConfigDir();

        $instance = new ContainerBuilder();
        $instance->setParameter('env', $env);
        $instance->setParameter('be.projectDir', $projectDir);
        $instance->setParameter('be.configDir', $configDir);
        $instance->setParameter('be.cacheDir', $cacheDir);

        $loader = new YamlFileLoader($instance, new FileLocator($configDir));
        $loader->load('services.yaml');
        if ($env === 'dev') {
            $loader->load('services.dev.yaml');
        }

        foreach(self::getCompiledPasses() as $obj) {
            $instance->addCompilerPass($obj);
        }
        $instance->compile();

        return $instance;
    }

    /**
     * @return string
     */
    protected static function getProjectDir(): string
    {
        // return '/var/www';
        return realpath(__DIR__ . '/../../..');
    }

    /**
     * @return string
     */
    protected static function getConfigDir(): string
    {
        return Paths::combine(self::getProjectDir(), 'config');
    }

    /**
     * @return string
     */
    protected static function getCacheDir(): string
    {
        return Paths::combine(self::getProjectDir(), 'var/cache');
    }

    /**
     * @return string
     */
    protected static function getContainerFile(): string
    {
        return Paths::combine(self::getCacheDir(), 'container.php');
    }

    /**
     * @return array
     */
    protected static function getCompiledPasses(): array
    {
        return [
            new RequiredCompilerPass(['Controller', 'Repository', 'Service', 'Command']),
            new TwigCompilerPass()
        ];
    }
}
