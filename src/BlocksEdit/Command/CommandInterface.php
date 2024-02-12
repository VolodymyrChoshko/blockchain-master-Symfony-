<?php
namespace BlocksEdit\Command;

use Exception;
use Psr\Container\ContainerInterface;

/**
 * Interface CommandInterface
 */
interface CommandInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container);

    /**
     * @return string
     */
    public static function getHelp(): string;

    /**
     * @return array
     */
    public static function getOpts(): array;

    /**
     * @param Args            $args
     * @param OutputInterface $output
     * @param InputInterface  $input
     *
     * @return mixed
     * @throws Exception
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input);
}
