<?php
namespace BlocksEdit\Database;

/**
 * Class Select
 */
class Select
{
    /**
     * @var string
     */
    protected $column = '*';

    /**
     * @var array
     */
    protected $wheres = [];

    /**
     * @var array
     */
    protected $orderBy = [];

    /**
     * @var int|null
     */
    protected $limit = null;

    /**
     * @var int|null
     */
    protected $offset = null;

    /**
     * @var string[]
     */
    protected $excludedColumns = [];

    /**
     * Constructor
     *
     * @param string $column
     * @param array  $wheres
     */
    public function __construct(string $column = '*', array $wheres = [])
    {
        $this->column = $column;
        $this->wheres = $wheres;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @param string $column
     *
     * @return Select
     */
    public function setColumn(string $column): Select
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @return array
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * @param array $wheres
     *
     * @return Select
     */
    public function setWheres(array $wheres): Select
    {
        $this->wheres = $wheres;

        return $this;
    }

    /**
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @param array $orderBy
     *
     * @return Select
     */
    public function setOrderBy(array $orderBy): Select
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int|null $limit
     *
     * @return Select
     */
    public function setLimit(?int $limit): Select
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * @param int|null $offset
     *
     * @return Select
     */
    public function setOffset(?int $offset): Select
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getExcludedColumns(): array
    {
        return $this->excludedColumns;
    }

    /**
     * @param string[] $excludedColumns
     *
     * @return Select
     */
    public function setExcludedColumns(array $excludedColumns): Select
    {
        $this->excludedColumns = $excludedColumns;

        return $this;
    }
}
