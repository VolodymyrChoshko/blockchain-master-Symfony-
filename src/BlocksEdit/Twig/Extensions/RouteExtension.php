<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Http\RouteGenerator;
use Exception;
use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class RouteExtension
 */
class RouteExtension extends AbstractExtension
{
    /**
     * @var RouteGenerator
     */
    protected $routeGenerator;

    /**
     * Constructor
     *
     * @param RouteGenerator $routeGenerator
     */
    public function __construct(RouteGenerator $routeGenerator)
    {
        $this->routeGenerator = $routeGenerator;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('path', [$this, 'path'])
        ];
    }

    /**
     * @param string $name
     * @param array  $params
     * @param string $type
     * @param null   $oid
     *
     * @return string
     */
    public function path(string $name, array $params = [], string $type = 'relative', $oid = null): string
    {
        try {
            $path = $this->routeGenerator->generate($name, $params, $type, $oid);
        } catch (Exception $e) {
            trigger_error($e->getMessage());
            $path = '';
        }

        return $path;
    }
}
