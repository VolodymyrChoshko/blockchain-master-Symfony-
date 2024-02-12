<?php
namespace BlocksEdit\Controller;

use BlocksEdit\Http\Exception\StatusCodeException;
use BlocksEdit\Http\MiddlewareInterface;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\RouteGeneratorInterface;
use BlocksEdit\Controller\Exception\ForwardLoopException;
use Entity\User;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Repository\UserRepository;
use Service\AuthService;
use Psr\Container\ContainerInterface;
use Repository\OrganizationsRepository;

/**
 * Class ControllerInvoker
 */
class ControllerInvoker implements ControllerInvokerInterface
{
    const SCALARS = ['int', 'string', 'float', 'bool', 'array'];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RouteGeneratorInterface
     */
    protected $routeGenerator;

    /**
     * @var array
     */
    protected $forwardStack = [];

    /**
     * Constructor
     *
     * @param ContainerInterface      $container
     * @param RouteGeneratorInterface $routeGenerator
     */
    public function __construct(ContainerInterface $container, RouteGeneratorInterface $routeGenerator)
    {
        $this->container      = $container;
        $this->routeGenerator = $routeGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function invokeAction(Request $request, $controller, string $method)
    {
        try {
            if (is_string($controller)) {
                if ($this->container->has($controller)) {
                    $controller = $this->container->get($controller);
                } else {
                    $controller = new $controller($this->container);
                }
            }

            $middleware = [];
            $params = $request->route->params->all();
            foreach($this->routeGenerator->getMiddleware() as $className) {
                $middleware[] = new $className($this->container);
            }

            /** @phpstan-ignore-next-line */
            usort($middleware, function(MiddlewareInterface $a, MiddlewareInterface $b) {
                return $a->getPriority() > $b->getPriority();
            });
            foreach($middleware as $obj) {
                $cRef = new ReflectionClass($obj);
                $mRef = $cRef->getMethod('request');
                $args = $this->gatherArgs($mRef, $request, $params, $request->oid);
                $resp = $mRef->invokeArgs($obj, $args);
                if ($resp) {
                    if ($resp instanceof Forward) {
                        return $this->forward($request, $resp);
                    }
                    return $resp;
                }
            }

            // Middleware modifies the params.
            $params = $request->route->params->all();
            $cRef   = new ReflectionClass($controller);
            $mRef   = $cRef->getMethod($method);
            $args   = $this->gatherArgs($mRef, $request, $params, $request->oid);
            $resp   = $mRef->invokeArgs($controller, $args);
            if ($resp instanceof Forward) {
                return $this->forward($request, $resp);
            }

            return $resp;
        } catch (ReflectionException $e) {
            throw new StatusCodeException('404');
        }
    }

    /**
     * @param Request $request
     * @param Forward $forward
     *
     * @return mixed
     * @throws ForwardLoopException
     * @throws StatusCodeException
     */
    protected function forward(Request $request, Forward $forward)
    {
        if (in_array($forward->toString(), $this->forwardStack)) {
            throw new ForwardLoopException(
                sprintf('Detected circular forward loop forwarding for %s.', $forward->toString()),
                500
            );
        }
        $this->forwardStack[] = $forward->toString();

        $params = $forward->getParams();
        if ($params) {
            $request->route->params->add($params);
        }

        return $this->invokeAction($request, $forward->getClassName(), $forward->getMethodName());
    }

    /**
     * @param ReflectionMethod $mRef
     * @param Request          $request
     * @param array            $params
     * @param int              $oid
     *
     * @return array
     * @throws Exception
     */
    protected function gatherArgs(ReflectionMethod $mRef, Request $request, array $params, int $oid): array
    {
        $args = [];
        foreach ($mRef->getParameters() as $param) {
            $name = $param->getName();
            $type = (string)$param->getType();
            if ($name === 'request' && $type === Request::class) {
                $args[] = $request;
            } else if ($name === 'oid' && empty($params['oid'])) {
                $args[] = $oid;
            } else if ($name === 'organization' && empty($params['organization'])) {
                $args[] = $this->container->get(OrganizationsRepository::class)->findByID($oid, true);
            } else if ($name === 'user' && $type === 'array' && empty($params['user'])) {
                $user = [];
                $uid  = $this->container->get(AuthService::class)->getLoginId();
                if ($uid) {
                    $user = $this->container->get(UserRepository::class)->findByID($uid);
                }
                $args[] = $user;
            } else if ($name === 'user' && $type === User::class && empty($params['user'])) {
                $user = null;
                $uid  = $this->container->get(AuthService::class)->getLoginId();
                if ($uid) {
                    $user = $this->container->get(UserRepository::class)->findByID($uid, true);
                }
                $args[] = $user;
            } else if ($name === 'uid' && empty($params['uid'])) {
                $args[] = $this->container->get(AuthService::class)->getLoginId();
            } else if ($name === 'org' && $type === 'array' && empty($params['org'])) {
                $args[] = $this->container->get(OrganizationsRepository::class)->findByID($oid);
            } else if ($type && !in_array($type, self::SCALARS) && $this->container->has($type)) {
                $args[] = $this->container->get($type);
            } else {
                if (isset($params[$name])) {
                    $args[] = $params[$name];
                } else if ($param->isOptional()) {
                    try {
                        $args[] = $param->getDefaultValue();
                    } catch (\ReflectionException $e) {
                        $args[] = null;
                    }
                } else {
                    $args[] = null;
                }
            }
        }

        return $args;
    }
}
