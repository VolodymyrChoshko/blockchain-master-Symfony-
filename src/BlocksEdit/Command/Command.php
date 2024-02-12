<?php
namespace BlocksEdit\Command;

use BlocksEdit\Config\Config;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;

/**
 * Class Command
 */
abstract class Command implements CommandInterface
{
    use LoggerAwareTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \BlocksEdit\Config\Config
     */
    protected $config;

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public static function getOpts(): array
    {
        return [];
    }

    /**
     * @param ContainerInterface $container
     *
     * @throws Exception
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config    = $container->get(Config::class);
        $this->setLogger($container->get(LoggerInterface::class));
    }
}
