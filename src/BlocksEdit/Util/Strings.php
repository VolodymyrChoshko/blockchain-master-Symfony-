<?php
namespace BlocksEdit\Util;

/**
 * Class Strings
 */
class Strings
{
    /**
     * @param string $haystack
     * @param string $needle
     * @param bool   $caseSensitive
     *
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle, bool $caseSensitive = true): bool
    {
        if (!$caseSensitive) {
            $haystack = strtolower($haystack);
            $needle   = strtolower($needle);
        }
        $length = strlen($needle);

        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param bool   $caseSensitive
     *
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle, bool $caseSensitive = true): bool
    {
        if (!$caseSensitive) {
            $haystack = strtolower($haystack);
            $needle   = strtolower($needle);
        }
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function getSlug(string $string): string
    {
        $string = strtolower($string);
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
        $string = preg_replace("/[\s-]+/", " ", $string);
        $string = preg_replace("/[\s_]/", "-", $string);

        return $string;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function snakeToCamel(string $string): string
    {
        $str    = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        $str[0] = strtolower($str[0]);

        return $str;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public static function random(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * @return string
     */
    public static function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
