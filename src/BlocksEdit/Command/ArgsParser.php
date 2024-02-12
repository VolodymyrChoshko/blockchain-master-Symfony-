<?php
namespace BlocksEdit\Command;

/**
 * Class ArgsParser
 *
 * @see https://github.com/samejack/php-argv
 */
class ArgsParser
{
    const MAX_ARGV = 1000;

    /**
     * Parse arguments
     *
     * @param array $argv
     * @return Args
     */
    public function parse(array $argv): Args
    {
        $cmd   = array_shift($argv);
        $index = 0;
        $args  = new Args($cmd);
        while ($index < self::MAX_ARGV && isset($argv[$index])) {
            if (preg_match('/^([^-\=]+.*)$/', $argv[$index], $matches) === 1) {
                $args->addArg($matches[1]);
            } else if (preg_match('/^-+(.+)$/', $argv[$index], $matches) === 1) {
                if (preg_match('/^-+(.+)\=(.+)$/', $argv[$index], $subMatches) === 1) {
                    $args->addOpt($subMatches[1], $subMatches[2]);
                } else if (isset($argv[$index + 1]) && preg_match('/^[^-\=]+$/', $argv[$index + 1]) === 1) {
                    $args->addOpt($matches[1], $argv[$index + 1]);
                    $index++;
                } else {
                    $args->addOpt($matches[1], true);
                }
            }

            $index++;
        }

        return $args;
    }
}
