<?php
namespace BlocksEdit\System;

use Redis;

/**
 * Class RedisFactory
 */
class RedisFactory
{
    /**
     * @param array $config
     *
     * @return Redis
     */
    public static function create(array $config): Redis
    {
        $obj = new Redis();
        $obj->connect($config['host'], $config['port']);

        return $obj;
    }
}
