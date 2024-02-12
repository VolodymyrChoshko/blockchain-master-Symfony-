<?php
namespace BlocksEdit\Database;

/**
 * Class PDO
 */
class PDO
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return \PDO
     */
    public function instance(): \PDO
    {
        $dsn = sprintf(
            '%s:host=%s;dbname=%s',
            $this->config['adapter'],
            $this->config['host'],
            $this->config['name']
        );

        return new \PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"
            ]
        );
    }
}
