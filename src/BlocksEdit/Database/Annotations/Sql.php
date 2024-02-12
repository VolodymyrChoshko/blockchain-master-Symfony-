<?php
namespace BlocksEdit\Database\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Sql
{
    /**
     * @var string
     */
    public $value = '';
}
