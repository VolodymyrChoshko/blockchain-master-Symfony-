<?php
namespace BlocksEdit\Command;

/**
 * Class Input
 */
interface InputInterface
{
    /**
     * @param string $msg
     * @param mixed $default
     *
     * @return string
     */
    public function read(string $msg, $default = '');

    /**
     * @param string $msg
     * @param string $opt
     * @param string $default
     *
     * @return string
     */
    public function readOrOpt(string $msg, string $opt, $default = '');

    /**
     * @param string $msg
     * @param array  $options
     * @param string $default
     *
     * @return string
     */
    public function readOption(string $msg, array $options = ['y', 'n'], $default = 'n');
}
