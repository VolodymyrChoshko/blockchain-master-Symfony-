<?php
namespace BlocksEdit\Command;

/**
 * Class MatchedCommand
 */
class MatchedCommand
{
    /**
     * @var string
     */
    protected $className;

    /**
     * string
     */
    protected $help;

    /**
     * @var array
     */
    protected $opts = [];

    /**
     * Constructor
     *
     * @param string $className
     * @param string $help
     * @param array  $opts
     */
    public function __construct(string $className, string $help, array $opts)
    {
        $this->className = $className;
        $this->help      = $help;
        $this->opts      = $opts;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return $this->help;
    }

    /**
     * @return array
     */
    public function getOpts(): array
    {
        return $this->opts;
    }
}
