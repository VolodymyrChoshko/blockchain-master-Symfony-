<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Twig\AbstractExtension;
use Monolog\Logger;
use Twig\TwigFilter;

/**
 * Class StringExtension
 */
class StringExtension extends AbstractExtension
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('pluralize', [$this, 'pluralize']),
            new TwigFilter('ucwords', [$this, 'ucwords']),
            new TwigFilter('str_pad', 'str_pad'),
            new TwigFilter('logLevelName', [$this, 'logLevelName']),
            new TwigFilter('jsonFormat', [$this, 'jsonFormat'])
        ];
    }

    /**
     * @param string $str
     * @param bool   $replaceUnderscores
     *
     * @return string
     */
    public function ucwords(string $str, bool $replaceUnderscores = false): string
    {
        if (!$replaceUnderscores) {
            return ucwords($str);
        }

        return join(' ', array_map('ucwords', explode('_', $str)));
    }

    /**
     * @param int    $num
     * @param string $singular
     * @param string $plural
     *
     * @return string
     */
    public function pluralize(int $num, string $singular, string $plural): string
    {
        return ($num == 1) ? $singular : $plural;
    }

    /**
     * @param int $level
     *
     * @return string
     */
    public function logLevelName(int $level): string
    {
        return Logger::getLevelName($level);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function jsonFormat(string $value): string
    {
        $arr   = json_decode($value);
        $value = json_encode($arr, JSON_PRETTY_PRINT);

        return stripslashes($value);
    }
}
