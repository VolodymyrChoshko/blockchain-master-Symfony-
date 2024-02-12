<?php
namespace BlocksEdit\Command\Exception;

use BlocksEdit\Exception\BlocksEditException;
use Throwable;

/**
 * Class CommandNotFoundException
 */
class CommandNotFoundException extends BlocksEditException
{
    /**
     * @var array
     */
    protected $possibleCommands = [];

    /**
     * Constructor
     *
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     * @param array          $possibleCommands
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null, array $possibleCommands = [])
    {
        parent::__construct($message, $code, $previous);
        $this->possibleCommands = $possibleCommands;
    }

    /**
     * @return array
     */
    public function getPossibleCommands()
    {
        return $this->possibleCommands;
    }
}
