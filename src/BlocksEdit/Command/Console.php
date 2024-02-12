<?php
namespace BlocksEdit\Command;

use BlocksEdit\Command\Exception\CommandNotFoundException;
use BlocksEdit\System\ClassFinderInterface;
use BlocksEdit\Config\Config;
use Exception;
use Psr\Container\ContainerInterface;

/**
 * Class Console
 */
class Console
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CommandMatcherInterface
     */
    protected $matcher;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var resource
     */
    protected $stdOut;

    /**
     * @var resource
     */
    protected $stdErr;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     *
     * @throws Exception
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->matcher   = $container->get(CommandMatcherInterface::class);
        $this->config    = $container->get(Config::class);
        $this->stdOut    = defined('STDOUT') ? STDOUT : fopen('php://memory', 'w');
        $this->stdErr    = defined('STDERR') ? STDERR : fopen('php://memory', 'w');
    }

    /**
     * @param resource $stdOut
     *
     * @return $this
     */
    public function setOut($stdOut): Console
    {
        $this->stdOut = $stdOut;
        return $this;
    }

    /**
     * @param resource $stdErr
     *
     * @return $this
     */
    public function setErr($stdErr): Console
    {
        $this->stdErr = $stdErr;
        return $this;
    }

    /**
     * @param Args $args
     * @param bool $dieOnHelp Whether to call die(0) after the help page is displayed
     *
     * @return mixed
     * @throws Exception
     */
    public function run(Args $args, bool $dieOnHelp = true)
    {
        $name     = $args->getArg(0);
        $commands = $this->findCommands();
        $output   = new Output($this->stdOut, $this->stdErr);

        if (!$name) {
            $output->writeLine("Example:\nbin/console app:user:password\n");
            asort($commands);
            foreach($commands as $name => $values) {
                $output->writeLine(sprintf("%-25s %s", $name, $values['help']));
            }
            if ($dieOnHelp) {
                die(0);
            }
            return 0;
        }

        try {
            $matched = $this->matcher->match($name);
            if ($args->getOpt('h') || $args->getOpt('help')) {
                $output->writeLine(sprintf("%s %s", $name, $matched->getHelp()));
                foreach($matched->getOpts() as $opt => $description) {
                    $output->writeLine(sprintf("  %-15s %s", $opt, $description));
                }
                if ($dieOnHelp) {
                    die(0);
                }
                return 0;
            }

            $className = $matched->getClassName();
            if ($this->container->has($className)) {
                $object = $this->container->get($className);
            } else {
                $object = new $className();
            }
            if (!($object instanceof CommandInterface)) {
                throw new Exception("Class ${className} does not implement CommandInterface.");
            }

            $object->setContainer($this->container);
            $all = $args->getArgs();
            array_shift($all);
            $args->setArgs($all);

            return $object->run($args, $output, new Input($output));
        } catch (CommandNotFoundException $e) {
            $output->errorLine($e->getMessage());
            $possibleCommands = $e->getPossibleCommands();
            if ($possibleCommands) {
                $output->errorLine("\nDid you mean?");
                foreach($possibleCommands as $command) {
                    $output->errorLine(sprintf("  %s", $command));
                }
            }
            if ($dieOnHelp) {
                die(1);
            }
        }

        return 0;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function findCommands(): array
    {
        $commands    = [];
        $classFinder = $this->container->get(ClassFinderInterface::class);
        $classes     = $classFinder->getNamespaceClasses('Command', true);
        foreach($classes as $path => $className) {
            if (!class_exists($className)) {
                include($path);
            }
            if (!class_exists($className)) {
                throw new Exception("Class ${className} not found in ${path}.");
            }

            if (isset($className::$name)) {
                $commands[$className::$name] = [
                    'className' => $className,
                    'help'      => $className::getHelp(),
                    'opts'      => $className::getOpts()
                ];
            }
        }

        return $commands;
    }
}
