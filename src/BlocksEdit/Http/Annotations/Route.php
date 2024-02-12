<?php
namespace BlocksEdit\Http\Annotations;

use Doctrine\Common\Annotations\Annotation;
use BlocksEdit\Http\Router;

/**
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class Route
{
    /**
     * @var string
     * @Required
     */
    protected $value;

    /**
     * @var string
     * @Required
     */
    protected $name;

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->value   = $values['value'] ?? '';
        $this->name    = $values['name'] ?? '';
        $this->methods = $values['methods'] ?? Router::DEFAULT_METHODS;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param string $value
     *
     * @return Route
     */
    public function setValue(string $value): Route
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return Route
     */
    public function setName(string $name): Route
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param array $methods
     *
     * @return Route
     */
    public function setMethods(array $methods): Route
    {
        $this->methods = $methods;

        return $this;
    }
}
