<?php
namespace BlocksEdit\Http;

use Doctrine\Common\Annotations\AnnotationException;
use ReflectionException;
use Exception;

/**
 * Finds and caches routes defined on controllers using the @Route annotation
 */
interface RouteGeneratorInterface
{
    /**
     * @param string $controllerNamespace
     *
     * @return $this
     */
    public function setControllerNamespace(string $controllerNamespace);

    /**
     * @return string
     */
    public function getControllerNamespace(): string;

    /**
     * Generates the url for a route
     *
     * @param string   $name
     * @param array    $params
     * @param string   $type
     * @param int|null $oid
     *
     * @return mixed
     * @throws Exception
     */
    public function generate(string $name, array $params = [], string $type = 'relative', ?int $oid = null);

    /**
     * Returns all defined routes
     *
     * @return array
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function getRoutes(): array;

    /**
     * @return array
     */
    public function getMiddleware(): array;

    /**
     * @return array
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function getJavascriptRoutes(): array;

    /**
     *
     */
    public function writeMiddlewareConfig();

    /**
     * @return array
     * @throws ReflectionException
     * @throws AnnotationException
     */
    public function writeRoutesConfig(): array;

    /**
     * @return array
     * @throws AnnotationException
     * @throws ReflectionException
     */
    public function findAnnotatedRoutes(): array;
}
