<?php
namespace BlocksEdit\Http;

use BlocksEdit\Http\Exception\NotFoundException;
use BlocksEdit\Http\Exception\RouterException;
use BlocksEdit\Http\Exception\StatusCodeException;
use BlocksEdit\Integrations\RoutableIntegrationInterface;
use BlocksEdit\Controller\ControllerInvokerInterface;
use BlocksEdit\Config\Config;
use BlocksEdit\Twig\TwigRender;
use Doctrine\Common\Annotations\AnnotationException;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Repository\SourcesRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Exception;

/**
 * Dispatches requests to controllers.
 */
class Router
{
    const DEFAULT_METHODS = ['GET', 'POST', 'PUT', 'DELETE'];

    /**
     * @var RouteGeneratorInterface
     */
    protected $routeGenerator;

    /**
     * @var ControllerInvokerInterface
     */
    protected $controllerInvoker;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RoutableIntegrationInterface[]
     */
    protected $integrations = [];

    /**
     * @var TwigRender
     */
    protected $twigRender;

    /**
     * Constructor
     *
     * @param RouteGeneratorInterface    $routeGenerator
     * @param ControllerInvokerInterface $controllerInvoker
     * @param Config                     $config
     * @param SourcesRepository          $sourcesRepo
     * @param LoggerInterface            $logger
     * @param TwigRender                 $twigRender
     */
    public function __construct(
        RouteGeneratorInterface $routeGenerator,
        ControllerInvokerInterface $controllerInvoker,
        Config $config,
        SourcesRepository $sourcesRepo,
        LoggerInterface $logger,
        TwigRender $twigRender
    )
    {
        $this->routeGenerator    = $routeGenerator;
        $this->controllerInvoker = $controllerInvoker;
        $this->config            = $config;
        $this->logger            = $logger;
        $this->twigRender        = $twigRender;
        foreach($sourcesRepo->getAvailableIntegrations() as $integration) {
            if ($integration instanceof RoutableIntegrationInterface) {
                $this->integrations[] = $integration;
            }
        }
    }

    /**
     * Routes the request to the correct controller method
     *
     * @param ContainerInterface $container
     * @param Request            $request
     *
     * @return ResponseInterface
     * @throws AnnotationException
     * @throws NotFoundException
     * @throws ReflectionException
     * @throws RouterException
     * @throws Exception
     */
    public function dispatch(ContainerInterface $container, Request $request)
    {
        $routes  = $this->getRoutes();
        $context = new RequestContext(
            $request->getPath(),
            $request->getMethod()
        );

        try {
            // Creating our own Route instance, which is a holdover from
            // an earlier version of this router.
            $matcher = new UrlMatcher($routes, $context);
            try {
                $params = $matcher->match(rtrim($request->getPath(), '/'));
            } catch (MethodNotAllowedException | ResourceNotFoundException $e) {
                // Pass the request to React (and react router).
                $params = $matcher->match('/');
            }
            $route = new Route(
                $params['_route'],
                $params['_match'],
                $params,
                $params['_grants'],
                $params['_integration']
            );
            $request->setRoute($route);

            $statusCode = StatusCodes::OK;
            $class      = $params['_controller'];
            $method     = $params['_method'];
            if (!class_exists($class) || !method_exists($class, $method)) {
                throw new NotFoundException();
            }

            // Integrations can define routes, and when triggered the integration
            // acts like a controller.
            if ($integration = $params['_integration']) {
                if ($integration instanceof $class) {
                    $controller = $integration;
                    $controller->setLogger($this->logger);
                } else {
                    $controller = new $class($integration);
                }
            } else if ($container->has($class)) {
                $controller = $container->get($class);
            } else {
                // Otherwise, create an instance of the controller class.
                $controller = new $class($container);
            }

            // Calls the matched controller method.
            $response = $this->controllerInvoker->invokeAction(
                $request,
                $controller,
                $method
            );

            // Numeric responses get turned into http status code exceptions.
            if (is_numeric($response)) {
                $statusCode = (int)$response;
                if ($statusCode === StatusCodes::NOT_FOUND) {
                    throw new NotFoundException();
                } else {
                    throw new StatusCodeException('', $statusCode);
                }
            }
        } catch (MethodNotAllowedException | ResourceNotFoundException $e) {
            throw new NotFoundException();
        } catch (StatusCodeException $e) {
            $template   = $e->getTemplate();
            $statusCode = $e->getCode();
            $response   = $this->twigRender->render($template, [
                'message' => $e->getMessage(),
                'code'    => $e->getCode()
            ]);
        }

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        return new Response($response, $statusCode);
    }

    /**
     * Routes on defined on controller methods using the @Route annotation, which
     * the RouteGenerator class converts into a PHP array which gets saved to
     * the directory var/cache/routes.php. Integrations may also define routes.
     *
     * @return RouteCollection
     * @throws ReflectionException
     * @throws RouterException
     * @throws AnnotationException
     */
    protected function getRoutes(): RouteCollection
    {
        $routes = $this->routeGenerator->getRoutes();

        $integrationRoutes = [];
        foreach($this->integrations as $integration) {
            foreach($integration->getRoutes() as $name => $route) {
                $integrationRoutes[$name] = $integration;
                $routes[$name] = $route;
            }
        }

        $cleaned = [];
        foreach($routes as $name => $route) {
            $integration = null;
            if (isset($integrationRoutes[$name])) {
                $integration = $integrationRoutes[$name];
            }
            $route = $this->cleanRoute($route, $name, $integration);
            $route['integration'] = $integration;
            $cleaned[$name] = $route;
        }

        $routes = new RouteCollection();
        foreach($cleaned as $name => $r) {
            $route = new SymfonyRoute(
                $r['match'],
                [
                    '_controller'  => $r['class'],
                    '_method'      => $r['method'],
                    '_integration' => $r['integration'],
                    '_grants'      => $r['grants'],
                    '_match'       => $r
                ]
            );
            $route->setMethods($r['methods']);
            $routes->add($name, $route);
        }

        return $routes;
    }

    /**
     * Makes sure all routes have all expected values
     *
     * @param array                             $route
     * @param string                            $name
     * @param RoutableIntegrationInterface|null $integration
     *
     * @return array
     * @throws RouterException
     */
    protected function cleanRoute(array $route, string $name, ?RoutableIntegrationInterface $integration): array
    {
        if (empty($route['match'])) {
            throw new RouterException(
                sprintf('Route "%s" missing "match" argument.', $name)
            );
        }
        if (empty($route['class']) && $integration) {
            $route['class'] = get_class($integration);
        }
        if (empty($route['class'])) {
            throw new RouterException(
                sprintf('Route "%s" missing "class" argument.', $name)
            );
        }
        if (empty($route['method'])) {
            throw new RouterException(
                sprintf('Route "%s" missing "method" argument.', $name)
            );
        }
        if (!isset($route['methods'])) {
            $route['methods'] = self::DEFAULT_METHODS;
        }
        if (!is_array($route['methods'])) {
            throw new RouterException(
                sprintf('Route "%s" methods must be an array.', $name)
            );
        }
        if (!isset($route['grants'])) {
            $route['grants'] = [];
        }

        return $route;
    }
}
