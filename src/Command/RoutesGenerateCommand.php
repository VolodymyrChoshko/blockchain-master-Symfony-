<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Http\RouteGenerator;

/**
 * Class RoutesGenerateCommand
 */
class RoutesGenerateCommand extends Command
{
    static $name = 'routes:generate';

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
        return 'Generate routes javascript file.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {;
        $this->routeGenerator->writeRoutesConfig();
        sleep(1);

        $routes = $this->routeGenerator->getJavascriptRoutes();
        $json   = json_encode($routes, JSON_PRETTY_PRINT);
        $file   = $this->config->dirs['assets'] . 'js/lib/routes.json';
        file_put_contents($file, $json);

        $routes = [
            'url'            => $this->config->uri,
            'assets_url'     => $this->config->uris['assets'],
            'assets_version' => $this->config->assetsVersion
        ];
        $json   = json_encode($routes, JSON_PRETTY_PRINT);
        $file = $this->config->dirs['assets'] . 'js/lib/site.json';
        file_put_contents($file, $json);

        $output->writeLine("Routes written to ${file}");
    }
}
