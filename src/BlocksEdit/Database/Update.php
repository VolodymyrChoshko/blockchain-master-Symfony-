<?php
namespace BlocksEdit\Database;

/**
 * Class Update
 */
class Update
{
    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var array
     */
    protected $wheres = [];

    /**
     * @var array
     */
    protected $ignoreColumns = [];

    /**
     * @var int|null
     */
    protected $limit = 1;

    /**
     * Constructor
     *
     * @param array $values
     * @param array $wheres
     */
    public function __construct(array $values, array $wheres = [])
    {
        $this->values = $values;
        $this->wheres = $wheres;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     *
     * @return Update
     */
    public function setValues(array $values): Update
    {
        $this->values = $values;

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
     * @return Update
     */
    public function setWheres(array $wheres): Update
    {
        $this->wheres = $wheres;

        return $this;
    }

    /**
     * @return array
     */
    public function getIgnoreColumns(): array
    {
        return $this->ignoreColumns;
    }

    /**
     * @param array $ignoreColumns
     *
     * @return Update
     */
    public function setIgnoreColumns(array $ignoreColumns): Update
    {
        $this->ignoreColumns = $ignoreColumns;

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
     * @return Update
     */
    public function setLimit(?int $limit): Update
    {
        $this->limit = $limit;

        return $this;
    }
}
