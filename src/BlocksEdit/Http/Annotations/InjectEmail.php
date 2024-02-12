<?php
namespace BlocksEdit\Http\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class InjectEmail
{
    /**
     * @var string
     */
    public $value = 'id';

    /**
     * @var string
     */
    public $param = 'email';

    /**
     * @var bool
     */
    public $includeTemplate = false;

    /**
     * @var string
     */
    public $templateParam = 'template';
}
