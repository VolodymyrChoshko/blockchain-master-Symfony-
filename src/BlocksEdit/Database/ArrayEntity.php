<?php
namespace BlocksEdit\Database;

use ArrayAccess;
use BlocksEdit\Util\Strings;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Class ArrayEntity
 */
class ArrayEntity implements ArrayAccess
{
    /**
     * @var string
     */
    private $columnPrefix = '';

    /**
     * @var int
     */
    private $columnPrefixLen = 0;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * Constructor
     */
    public function __construct(string $columnPrefix)
    {
        $this->columnPrefix     = $columnPrefix;
        $this->columnPrefixLen  = strlen($columnPrefix);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();
        $this->propertyAccessor = new ReflectionPropertyAccessor($this->propertyAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        $offset = $this->offsetToProp($offset);

        return $this->propertyAccessor->isReadable($this, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $offset = $this->offsetToProp($offset);

        return $this->propertyAccessor->getValue($this, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (!$offset) {
            throw new NoSuchPropertyException('Cannot set value without index.');
        }
        $offset = $this->offsetToProp($offset);

        $this->propertyAccessor->setValue($this, $offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $offset = $this->offsetToProp($offset);

        $this->propertyAccessor->setValue($this, $offset, null);
    }

    /**
     * @param string $offset
     *
     * @return string
     */
    private function offsetToProp(string $offset): string
    {
        if (substr($offset, 0, $this->columnPrefixLen) === $this->columnPrefix) {
            $offset = substr($offset, $this->columnPrefixLen);
        }

        return Strings::snakeToCamel($offset);
    }
}
