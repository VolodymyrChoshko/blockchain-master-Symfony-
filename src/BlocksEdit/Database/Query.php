<?php
namespace BlocksEdit\Database;

/**
 * Class Query
 */
class Query
{
    /**
     * @var string
     */
    protected $sql = '';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * Constructor
     *
     * @param string $sql
     * @param array  $params
     */
    public function __construct(string $sql, array $params)
    {
        $this->sql    = $sql;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @param string $sql
     *
     * @return Query
     */
    public function setSql(string $sql): Query
    {
        $this->sql = $sql;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array $params
     *
     * @return Query
     */
    public function setParams(array $params): Query
    {
        $this->params = $params;

        return $this;
    }
}
