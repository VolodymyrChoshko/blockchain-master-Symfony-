<?php
namespace BlocksEdit\Command;

use BlocksEdit\Command\Exception\CommandNotFoundException;
use Exception;

/**
 * Class CommandMatcher
 */
interface CommandMatcherInterface
{
    /**
     * @param string $commandNamespace
     *
     * @return $this
     */
    public function setCommandNamespace(string $commandNamespace);

    /**
     * @param string $command
     *
     * @return MatchedCommand
     * @throws CommandNotFoundException
     * @throws Exception
     */
    public function match(string $command);
}
