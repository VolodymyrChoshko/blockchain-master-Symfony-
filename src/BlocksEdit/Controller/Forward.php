<?php
namespace BlocksEdit\Controller;

/**
 * Class Forward
 */
class Forward
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $methodName;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * Constructor
     *
     * @param string $className
     * @param string $methodName
     * @param array  $params
     */
    public function __construct(string $className, string $methodName, array $params = [])
    {
        $this->className  = $className;
        $this->methodName = $methodName;
        $this->params     = $params;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->className . '::' . $this->methodName;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
