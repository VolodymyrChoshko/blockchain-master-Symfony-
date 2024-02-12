<?php
namespace BlocksEdit\Http;

use BlocksEdit\Integrations\RoutableIntegrationInterface;

/**
 * Class Route
 */
class Route
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $match;

    /**
     * @var array
     */
    public $grants;

    /**
     * @var ParameterBag
     */
    public $params;

    /**
     * @var RoutableIntegrationInterface
     */
    public $integration;

    /**
     * Constructor
     *
     * @param string                            $name
     * @param array                             $match
     * @param array                             $params
     * @param array                             $grants
     * @param RoutableIntegrationInterface|null $integration
     */
    public function __construct(
        string $name,
        array $match,
        array $params,
        array $grants,
        ?RoutableIntegrationInterface $integration = null
    )
    {
        $this->name        = $name;
        $this->match       = $match;
        $this->grants      = $grants;
        $this->params      = new ParameterBag($params);
        $this->integration = $integration;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * @return array
     */
    public function getGrants()
    {
        return $this->grants;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->match['match'];
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->match['class'];
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->match['method'];
    }

    /**
     * @return array
     */
    public function getMiddleware()
    {
        return $this->match['middleware'];
    }

    /**
     * @return RoutableIntegrationInterface|null
     */
    public function getIntegration(): ?RoutableIntegrationInterface
    {
        return $this->integration;
    }
}
