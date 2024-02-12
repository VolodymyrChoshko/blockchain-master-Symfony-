<?php
namespace BlocksEdit\Command;

use BlocksEdit\Command\Exception\CommandNotFoundException;
use BlocksEdit\System\ClassFinderInterface;
use Exception;

/**
 * Class CommandMatcher
 */
class CommandMatcher implements CommandMatcherInterface
{
    /**
     * @var ClassFinderInterface
     */
    protected $classFinder;

    /**
     * @var string
     */
    protected $commandNamespace = 'Command';

    /**
     * Constructor
     *
     * @param ClassFinderInterface $classFinder
     */
    public function __construct(ClassFinderInterface $classFinder)
    {
        $this->classFinder = $classFinder;
    }

    /**
     * {@inheritDoc}
     */
    public function setCommandNamespace(string $commandNamespace)
    {
        $this->commandNamespace = $commandNamespace;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function match(string $command)
    {
        $commandParts    = preg_split('/[^\w\d_-]/', $command);
        $cCommandParts   = count($commandParts);
        $matchedCommands = $this->findCommands();
        $shortedCommand  = '';

        foreach($matchedCommands as $name => $matchedCommand) {
            $matchedParts = preg_split('/[^\w\d_-]/', $name);
            if (count($matchedParts) !== $cCommandParts) {
                continue;
            }

            $matches = true;
            for($i = 0; $i < $cCommandParts; $i++) {
                if (stripos($matchedParts[$i], $commandParts[$i]) !== 0) {
                    $matches = false;
                    break;
                }
            }
            if ($matches && ($shortedCommand === '' || strlen($name) < strlen($shortedCommand))) {
                $shortedCommand = $name;
            }
        }

        if (!isset($matchedCommands[$shortedCommand])) {
            $possibleCommands = [];
            foreach($matchedCommands as $name => $matchedCommand) {
                if (stripos($name, $commandParts[0]) !== false) {
                    $possibleCommands[] = $name;
                }
            }

            throw new CommandNotFoundException(
                sprintf('Command "%s" not found.', $command),
                1,
                null,
                $possibleCommands
            );
        }

        return $matchedCommands[$shortedCommand];
    }

    /**
     * @return MatchedCommand[]
     * @throws Exception
     */
    protected function findCommands()
    {
        $commands = [];
        $classes  = $this->classFinder->getNamespaceClasses($this->commandNamespace, true);
        foreach($classes as $path => $className) {
            include_once($path);
            if (!class_exists($className)) {
                throw new Exception("Class ${className} not found in ${path}.");
            }
            if (isset($className::$name)) {
                $commands[$className::$name] = new MatchedCommand(
                    $className,
                    $className::getHelp(),
                    $className::getOpts()
                );
            }
        }

        return $commands;
    }
}
