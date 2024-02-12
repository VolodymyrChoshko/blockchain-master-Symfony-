<?php
namespace BlocksEdit\Database;

use BlocksEdit\Config\Config;

/**
 * Class PDOFactory
 */
class PDOFactory
{
    /**
     * @param Config $config
     *
     * @return \PDO
     */
    public static function create(Config $config): \PDO
    {
        $dsn = sprintf(
            '%s:host=%s;dbname=%s',
            $config->pdo['adapter'],
            $config->pdo['host'],
            $config->pdo['name']
        );

        return new \PDO(
            $dsn,
            $config->pdo['username'],
            $config->pdo['password'],
            [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"
            ]
        );
    }
}
