<?php
namespace BlocksEdit\Http;

/**
 * Class RouteParamsParser
 */
class RouteParamsParser
{
    /**
     * @param string $match
     *
     * @return string
     */
    public static function parse(string $match): string
    {
        if (strpos($match, ':') !== false) {
            $match = preg_replace_callback(
                '/:(\w+)(\\[([^\\]]+)\\])?\b/',
                [RouteParamsParser::class, 'matchesParamRegex'],
                $match
            );
        }

        return $match;
    }

    /**
     * @param array $match
     *
     * @return string
     */
    public static function matchesParamRegex(array $match): string
    {
        if (!empty($match[3])) {
            return sprintf('(?<%s>%s)', $match[1], $match[3]);
        }
        if ($match[1] === 'id') {
            return '(?<id>\d+)';
        }

        return sprintf('(?<%s>.*)', $match[1]);
    }
}
