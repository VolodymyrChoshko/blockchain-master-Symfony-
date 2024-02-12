<?php
namespace BlocksEdit\Database\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Column
{
    /**
     * @var string
     */
    public $value = '';

    /**
     * @var string
     */
    public $castTo = '';

    /**
     * @var string
     */
    public $type = '';

    /**
     * @var int
     */
    public $length;

    /**
     * @var bool
     */
    public $unsigned = false;

    /**
     * @var bool
     */
    public $nullable = false;

    /**
     * @var bool
     */
    public $autoIncrement = false;

    /**
     * @var string
     */
    public $defaultValue = '';

    /**
     * @var string
     */
    public $collate = '';

    /**
     * @var bool
     */
    public $json = false;
}
