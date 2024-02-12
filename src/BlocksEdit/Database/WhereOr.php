<?php
namespace BlocksEdit\Database;

/**
 * Class WhereOr
 */
class WhereOr
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
     * @return WhereOr
     */
    public function setValues(array $values): WhereOr
    {
        $this->values = $values;

        return $this;
    }
}
