<?php
namespace BlocksEdit\Database\Annotations;

use InvalidArgumentException;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Join
{
    /**
     * @var string
     */
    protected $entity = '';

    /**
     * @var string
     */
    protected $references = 'id';

    /**
     * @var string
     */
    protected $onDelete = '';

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->entity     = $values['value'] ?? '';
        $this->references = $values['references'] ?? '';
        $this->onDelete   = strtoupper($values['onDelete'] ?? '');
        if (empty($this->entity) || !class_exists($this->entity)) {
            throw new InvalidArgumentException(
                'Value for @Join cannot be empty and must reference a repository.'
            );
        }
        if (empty($this->references)) {
            throw new InvalidArgumentException(
                'Reference column for @Join cannot be empty.'
            );
        }
        if (!empty($this->onDelete) && $this->onDelete !== 'CASCADE') {
            throw new InvalidArgumentException(
                '@Join onCascade value must be empty or "CASCADE".'
            );
        }
    }

    /**
     * @return string
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getReferences(): string
    {
        return $this->references;
    }

    /**
     * @return string
     */
    public function getOnDelete(): string
    {
        return $this->onDelete;
    }
}
