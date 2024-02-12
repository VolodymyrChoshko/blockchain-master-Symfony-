<?php
namespace BlocksEdit\Http\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class InjectTemplate
{
    /**
     * @var string
     */
    public $value = 'id';

    /**
     * @var string
     */
    public $param = 'template';
}
