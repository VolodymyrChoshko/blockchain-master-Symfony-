<?php
namespace BlocksEdit\Command;

/**
 * Class Args
 */
class Args
{
    /**
     * @var string
     */
    protected $command;

    /**
     * @var array
     */
    protected $opts = [];

    /**
     * @var array
     */
    protected $args = [];

    /**
     * Constructor
     *
     * @param string $command
     */
    public function __construct(string $command)
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     *
     * @return Args
     */
    public function setCommand(string $command): Args
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @return array
     */
    public function getOpts(): array
    {
        return $this->opts;
    }

    /**
     * @param string $key
     * @param string $default
     *
     * @return string|bool
     */
    public function getOpt(string $key, $default = '')
    {
        if (!isset($this->opts[$key])) {
            return $default;
        }

        return $this->opts[$key];
    }

    /**
     * @param array $opts
     *
     * @return Args
     */
    public function setOpts(array $opts): Args
    {
        $this->opts = $opts;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return Args
     */
    public function addOpt(string $key, $value): Args
    {
        $this->opts[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param int   $index
     * @param mixed $default
     *
     * @return string
     */
    public function getArg(int $index, $default = ''): string
    {
        if (!isset($this->args[$index])) {
            return $default;
        }

        return $this->args[$index];
    }

    /**
     * @param array $args
     *
     * @return Args
     */
    public function setArgs(array $args): Args
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Args
     */
    public function addArg(string $value): Args
    {
        $this->args[] = $value;

        return $this;
    }
}
