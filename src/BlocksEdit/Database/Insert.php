<?php
namespace BlocksEdit\Database;

/**
 * Class Insert
 */
class Insert
{
    /**
     * @var array
     */
    protected $values = [];

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
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
     * @return Insert
     */
    public function setValues(array $values): Insert
    {
        $this->values = $values;

        return $this;
    }
}
