<?php
namespace BlocksEdit\Database\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ManyToOne
{
    /**
     * @var string
     */
    public $value = '';

    /**
     * @var string
     */
    public $inversedBy = '';

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->value = $values['value'];
        if (empty($values['inversedBy'])) {
            throw new \InvalidArgumentException('Required parameter "inversedBy" missing.');
        }
        $this->inversedBy = $values['inversedBy'];
    }
}
