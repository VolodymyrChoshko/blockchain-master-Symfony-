<?php
namespace BlocksEdit\Database\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class OneToMany
{
    /**
     * @var string
     */
    public $value = '';

    /**
     * @var string
     */
    public $mappedBy = '';

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->value = $values['value'];
        if (empty($values['mappedBy'])) {
            throw new \InvalidArgumentException('Required parameter "mappedBy" missing.');
        }
        $this->mappedBy = $values['mappedBy'];
    }
}
