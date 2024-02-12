<?php
namespace BlocksEdit\Database\Annotations;

use RuntimeException;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
class CacheTag
{
    /**
     * @var string
     */
    public $value = '';

    /**
     * @var string
     */
    public $column = 'id';

    /**
     * @var string
     */
    public $mergeTags = '';

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (empty($values['value']) && empty($values['mergeTags'])) {
            throw new RuntimeException(
                'CacheTag annotation requires a value or mergeTags value.'
            );
        }

        $this->value     = $values['value'] ?? '';
        $this->column    = $values['column'] ?? 'id';
        $this->mergeTags = $values['mergeTags'] ?? '';
    }
}
