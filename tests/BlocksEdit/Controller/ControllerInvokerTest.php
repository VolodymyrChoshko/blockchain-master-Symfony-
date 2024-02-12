<?php
namespace Tests\BlocksEdit\Controller;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Controller\ControllerInvoker;
use BlocksEdit\Html\FlasherInterface;
use BlocksEdit\Html\NonceGeneratorInterface;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\Route;
use BlocksEdit\Http\RouteGeneratorInterface;
use BlocksEdit\Config\Config;
use BlocksEdit\Test\TestCase;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \BlocksEdit\Controller\ControllerInvoker
 */
class ControllerInvokerTest extends TestCase
{
    /**
     * @covers ::invokeAction
     * @throws Exception
     */
    public function testInvokeAction()
    {
        $routeGenerator = $this->createMock(RouteGeneratorInterface::class);
        $routeGenerator->method('getMiddleware')
            ->willReturn([]);

        $container = $this->getContainer([
            Config::class                  => $this->getConfig(),
            LoggerInterface::class         => new NullLogger(),
            FlasherInterface::class        => $this->createStub(FlasherInterface::class),
            NonceGeneratorInterface::class => $this->createStub(NonceGeneratorInterface::class),
            RouteGeneratorInterface::class => $routeGenerator

        ]);

        $request        = $this->createStub(Request::class);
        $request->oid   = 176;
        $request->route = new Route('test_route', [], [], []);

        $controller = new class($container) extends Controller
        {
            public function testAction(int $oid, Request $request)
            {
                return new Response($oid . $request->route->getName());
            }
        };

        $invoker = new ControllerInvoker($container, $routeGenerator);
        $actual  = $invoker->invokeAction($request, $controller, 'testAction');
        $this->assertInstanceOf(Response::class, $actual);
        $this->assertEquals('176test_route', $actual->getContent());
    }
}
