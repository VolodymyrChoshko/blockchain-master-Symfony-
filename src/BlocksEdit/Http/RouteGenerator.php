<?php
namespace BlocksEdit\Http;

use BlocksEdit\Http\Exception\RouteGeneratorException;
use BlocksEdit\Integrations\RoutableIntegrationInterface;
use BlocksEdit\System\ClassFinderInterface;
use BlocksEdit\Http\Annotations\IsGranted as IsGrantedAnnotation;
use BlocksEdit\Http\Annotations\Route as RouteAnnotation;
use BlocksEdit\Config\Config;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionException;
use Repository\SourcesRepository;
use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * Finds and caches routes defined on controllers using the @Route annotation
 */
class RouteGenerator implements RouteGeneratorInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $env;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var array
     */
    protected $routes = null;

    /**
     * @var array
     */
    protected $middleware = null;

    /**
     * @var ClassFinderInterface
     */
    protected $classFinder;

    /**
     * @var SourcesRepository
     */
    protected $sourcesRepo;

    /**
     * @var string
     */
    protected $controllerNamespace = 'Controller';

    /**
     * Constructor
     *
     * @param string               $env
     * @param string               $cacheDir
     * @param Config               $config
     * @param SourcesRepository    $sourcesRepo
     * @param ClassFinderInterface $classFinder
     */
    public function __construct(
        string $env,
        string $cacheDir,
        Config $config,
        SourcesRepository $sourcesRepo,
        ClassFinderInterface $classFinder
    )
    {
        $this->env         = $env;
        $this->cacheDir    = $cacheDir;
        $this->config      = $config;
        $this->sourcesRepo = $sourcesRepo;
        $this->classFinder = $classFinder;
    }

    /**
     * {@inheritDoc}
     */
    public function setControllerNamespace(string $controllerNamespace)
    {
        $this->controllerNamespace = $controllerNamespace;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getControllerNamespace(): string
    {
        return $this->controllerNamespace;
    }

    /**
     * {@inheritDoc}
     */
    public function generate(string $name, array $params = [], string $type = 'relative', ?int $oid = null)
    {
        try {
            $routes = $this->getRoutes();
        } catch (AnnotationException $e) {
            throw new RouteGeneratorException($e->getMessage(), $e->getCode(), $e);
        } catch (ReflectionException $e) {
            throw new RouteGeneratorException($e->getMessage(), $e->getCode(), $e);
        }
        if (!isset($routes[$name])) {
            if (strpos($name, '?unlock') !== false) {
                die();
            }
            throw new RouteGeneratorException("Route with name ${name} not found.");
        }

        // This sanitizes the path which may look like "{tid<\d>?0}" so that
        // it looks like "{tid}".
        $route = new SymfonyRoute($routes[$name]['match']);
        $path  = $route->getPath();
        foreach($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }

        if ($type !== 'relative') {
            if ($type === 'absolute') {
                if ($oid !== null) {
                    $parts = parse_url($this->config->uri);
                    return sprintf('%s://%s.%s%s', $parts['scheme'], $oid, $parts['host'], $path);
                }

                return sprintf('%s%s', $this->config->uri, $path);
            }

            return sprintf('%s%s', $type, $path);
        }

        return $path;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoutes(): array
    {
        if ($this->routes === null) {
            $this->routes = [];
            if ($this->env === 'dev' || !file_exists($this->cacheDir . '/routes.php')) {
                $this->routes = $this->writeRoutesConfig();
            } else {
                $this->routes = require($this->cacheDir . '/routes.php');
            }
        }

        return $this->routes;
    }

    /**
     * {@inheritDoc}
     */
    public function getMiddleware(): array
    {
        if ($this->middleware === null) {
            $this->middleware = [];
            if ($this->env === 'dev' || !file_exists($this->cacheDir . '/middleware.php')) {
                $this->middleware = $this->writeMiddlewareConfig();
            } else {
                $this->middleware = require($this->cacheDir . '/middleware.php');
            }
        }

        return $this->middleware;
    }

    /**
     * {@inheritDoc}
     */
    public function getJavascriptRoutes(): array
    {
        $routes = [];

        foreach($this->getRoutes() as $name => $route) {
            $sRoute        = new SymfonyRoute($route['match']);
            $sPath         = $sRoute->getPath();
            $sRequirements = $sRoute->getRequirements();

            if (preg_match_all('/{([^}]+)}/', $sPath, $matches)) {
                for($i = 0, $l = count($matches[0]); $i < $l; $i++) {
                    $req = $matches[1][$i];
                    if (!isset($sRequirements[$req])) {
                        $sRequirements[$req] = '*';
                    }
                }
            }

            $routes[$name] = [
                'path' => $sPath,
                'keys' => $sRequirements
            ];
        }

        return $routes;
    }

    /**
     * {@inheritDoc}
     */
    public function writeMiddlewareConfig(): array
    {
        $classes = $this->classFinder->getNamespaceClasses('Middleware');
        $php     = var_export($classes, true);
        $output  = "<?php\n# This file is auto-generated by the command bin/console routes:generate\nreturn ${php};\n";
        file_put_contents($this->cacheDir . '/middleware.php', $output);
        @chmod($this->cacheDir . '/middleware.php', 0777);

        return $classes;
    }

    /**
     * {@inheritDoc}
     */
    public function writeRoutesConfig(): array
    {
        $routes = $this->findAnnotatedRoutes();
        foreach($this->sourcesRepo->getAvailableIntegrations() as $integration) {
            if ($integration instanceof RoutableIntegrationInterface) {
                foreach($integration->getRoutes() as $name => $route) {
                    $routes[$name] = $route;
                }
            }
        }

        $php    = var_export($routes, true);
        $output = "<?php\n# This file is auto-generated by the command bin/console routes:generate\nreturn ${php};\n";
        file_put_contents($this->cacheDir . '/routes.php', $output);
        @chmod($this->cacheDir . '/routes.php', 0777);

        $devUrlGenerator = [];
        foreach($routes as $name => $route) {
            $sRoute        = new SymfonyRoute($route['match']);
            $sPath         = $sRoute->getPath();
            $sRequirements = $sRoute->getRequirements();

            if (preg_match_all('/{([^}]+)}/', $sPath, $matches)) {
                for($i = 0, $l = count($matches[0]); $i < $l; $i++) {
                    $req = $matches[1][$i];
                    if (!isset($sRequirements[$req])) {
                        $sRequirements[$req] = '*';
                    }
                }
            }

            if (!empty($route['class']) && !empty($route['method'])) {
                $variables = [];
                foreach($sRequirements as $n => $value) {
                    $variables[] = ['variable', '/', $value, $n, true];
                }
                $variables[] = ['text', $route['match']];
                $devUrlGenerator[$name] = [
                    array_keys($sRequirements),
                    ['_controller' => $route['class'] . '::'. $route['method']],
                    $sRequirements,
                    $variables,
                    [],
                    []
                ];
            }
        }

        $php    = var_export($devUrlGenerator, true);
        $output = "<?php\n# This file provides auto completion of routes in phpstorm.\nreturn ${php};\n";
        file_put_contents($this->cacheDir . '/UrlGenerator.php', $output);
        @chmod($this->cacheDir . '/UrlGenerator.php', 0777);

        return $routes;
    }

    /**
     * {@inheritDoc}
     */
    public function findAnnotatedRoutes(): array
    {
        $reader = new AnnotationReader();

        $config  = [];
        $classes = $this->classFinder->getNamespaceClasses($this->controllerNamespace);
        foreach($classes as $className) {
            if (!class_exists($className)) {
                continue;
            }

            $reflectionClass = new ReflectionClass($className);

            /**
             * @var RouteAnnotation     $classRoute
             * @var IsGrantedAnnotation $classIsGranted
             */
            $classRoute     = $reader->getClassAnnotation($reflectionClass, RouteAnnotation::class);
            $classIsGranted = $reader->getClassAnnotation($reflectionClass, IsGrantedAnnotation::class);

            $pathPrefix    = '';
            $namePrefix    = '';
            $methodsPrefix = [];
            if ($classRoute /** @phpstan-ignore-line */) {
                $pathPrefix    = $classRoute->getValue();
                $namePrefix    = $classRoute->getName();
                $methodsPrefix = $classRoute->getMethods();
            }

            foreach($reflectionClass->getMethods() as $method) {
                /**
                 * @var RouteAnnotation     $methodRoute
                 * @var IsGrantedAnnotation $methodIsGranted
                 */
                $methodRoute     = $reader->getMethodAnnotation($method, RouteAnnotation::class);
                $methodIsGranted = $reader->getMethodAnnotation($method, IsGrantedAnnotation::class);
                // $methodIsAllowed = $reader->getMethodAnnotation($method, IsAllowedAnnotation::class);
                if ($methodRoute /** @phpstan-ignore-line */) {
                    if ($pathPrefix) {
                        $methodRoute->setValue($pathPrefix . $methodRoute->getValue());
                    }
                    if ($namePrefix) {
                        $methodRoute->setName($namePrefix . $methodRoute->getName());
                    }
                    if ($methodsPrefix) {
                        $methodRoute->setMethods($methodsPrefix);
                    }

                    $grants = [];
                    if ($classIsGranted) {
                        $grants = $classIsGranted->getValue();
                    }
                    if ($methodIsGranted) {
                        $grants = array_merge($grants, $methodIsGranted->getValue());
                    }

                    $config[$methodRoute->getName()] = [
                        'match'   => $methodRoute->getValue(),
                        'class'   => $className,
                        'method'  => $method->getName(),
                        'methods' => $methodRoute->getMethods(),
                        'grants'  => $grants,
                        'allowed' => []
                    ];
                }
            }
        }

        return $config;
    }
}
