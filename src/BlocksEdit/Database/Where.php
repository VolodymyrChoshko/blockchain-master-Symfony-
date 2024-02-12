<?php
namespace BlocksEdit\Database;

/**
 * Class Where
 */
class Where
{
    /**
     * @var string
     */
    protected $column = '';

    /**
     * @var string
     */
    protected $condition = '';

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var bool
     */
    protected $raw = false;

    /**
     * Constructor
     *
     * @param string $column
     * @param string $condition
     * @param mixed  $value
     * @param bool   $raw
     */
    public function __construct(string $column, string $condition, $value, bool $raw = false)
    {
        $this->setColumn($column);
        $this->setCondition($condition);
        $this->setValue($value);
        $this->setRaw($raw);
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
     * @return Where
     */
    public function setColumn(string $column): Where
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     *
     * @return Where
     */
    public function setCondition(string $condition): Where
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return Where
     */
    public function setValue($value): Where
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRaw(): bool
    {
        return $this->raw;
    }

    /**
     * @param bool $raw
     *
     * @return Where
     */
    public function setRaw(bool $raw): Where
    {
        $this->raw = $raw;

        return $this;
    }
}
