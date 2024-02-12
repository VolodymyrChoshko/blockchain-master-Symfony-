<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Http\RouteGenerator;

/**
 * Class RoutesAnnotationsWriteCommand
 */
class RoutesAnnotationsWriteCommand extends Command
{
    static $name = 'routes:annotations:write';

    /**
     * @var RouteGenerator
     */
    protected $routeGenerator;

    /**
     * Constructor
     *
     * @param RouteGenerator $routeGenerator
     */
    public function __construct(RouteGenerator $routeGenerator)
    {
        $this->routeGenerator = $routeGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Writes routes found in code annotations.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $this->routeGenerator->writeRoutesConfig();

        $output->writeLine("Routes written");
    }
}
