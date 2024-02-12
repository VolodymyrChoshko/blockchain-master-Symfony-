<?php
namespace BlocksEdit\Database\Annotations;

use RuntimeException;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Table
{
    /**
     * @var string
     */
    public $value = '';

    /**
     * @var string
     */
    public $prefix = '';

    /**
     * @var string
     */
    public $repository = '';

    /**
     * @var array
     */
    public $indexes = [];

    /**
     * @var array
     */
    public $uniqueIndexes = [];

    /**
     * @var string
     */
    public $charSet = '';

    /**
     * @var string
     */
    public $collate = '';

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->value         = $values['value'] ?? '';
        $this->prefix        = $values['prefix'] ?? '';
        $this->repository    = $values['repository'] ?? '';
        $this->indexes       = $values['indexes'] ?? [];
        $this->uniqueIndexes = $values['uniqueIndexes'] ?? [];
        $this->charSet       = $values['charSet'] ?? '';
        $this->collate       = $values['collate'] ?? '';
        if ($this->repository && !class_exists($this->repository)) {
            throw new RuntimeException(
                sprintf('Repository %s does not exist.', $this->repository)
            );
        }
        if ($this->indexes && !is_array($this->indexes)) {
            throw new RuntimeException(
                sprintf('Indexes on %s must be an array.', $this->value)
            );
        }
        if ($this->uniqueIndexes && !is_array($this->uniqueIndexes)) {
            throw new RuntimeException(
                sprintf('Unique indexes on %s must be an array.', $this->value)
            );
        }
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getRepository(): string
    {
        return $this->repository;
    }

    /**
     * @return array
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @return array
     */
    public function getUniqueIndexes(): array
    {
        return $this->uniqueIndexes;
    }

    /**
     * @return string
     */
    public function getCharSet(): string
    {
        return $this->charSet;
    }

    /**
     * @return string
     */
    public function getCollate(): string
    {
        return $this->collate;
    }
}
