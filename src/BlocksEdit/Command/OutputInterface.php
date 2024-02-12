<?php
namespace BlocksEdit\Command;

/**
 * Class Output
 */
interface OutputInterface
{
    /**
     * @return resource
     */
    public function getStdOut();

    /**
     * @param resource $fileDescriptor
     */
    public function appendStdOut($fileDescriptor);

    /**
     * @return resource
     */
    public function getStdErr();

    /**
     * @param resource $fileDescriptor
     */
    public function appendStdErr($fileDescriptor);

    /**
     * @param string $str
     * @param string|int ...$args
     *
     * @return bool
     */
    public function writeLine(string $str, string ...$args): bool;

    /**
     * @param string $str
     * @param string ...$args
     *
     * @return bool
     */
    public function errorLine(string $str, string ...$args): bool;
}
