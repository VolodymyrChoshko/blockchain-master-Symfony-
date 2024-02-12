<?php
namespace BlocksEdit\Database\Annotations;

use InvalidArgumentException;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ForeignKey
{
    /**
     * @var string
     */
    public $value = '';

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $references = '';

    /**
     * @var string
     */
    public $onDelete = '';

    /**
     * @var string
     */
    public $onUpdate = '';

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->value      = $values['value'] ?? '';
        $this->name       = $values['name'] ?? '';
        $this->references = $values['references'] ?? '';
        $this->onDelete   = strtoupper($values['onDelete'] ?? '');
        $this->onUpdate   = strtoupper($values['onUpdate'] ?? '');
        if ($this->onDelete && !in_array($this->onDelete, ['CASCADE', 'RESTRICT'])) {
            throw new InvalidArgumentException(
                sprintf('@ForeignKey onDelete on %s must be one of "CASCADE" or "RESTRICT".', $this->value)
            );
        }
        if ($this->onUpdate && !in_array($this->onUpdate, ['CASCADE', 'RESTRICT'])) {
            throw new InvalidArgumentException(
                sprintf('@ForeignKey onUpdate on %s must be one of "CASCADE" or "RESTRICT".', $this->value)
            );
        }
    }
}
