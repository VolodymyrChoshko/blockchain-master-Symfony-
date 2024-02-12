<?php
namespace Tests\BlocksEdit\Http;

use BlocksEdit\Http\RouteGenerator;
use BlocksEdit\System\ClassFinderInterface;
use BlocksEdit\Test\TestCase;
use Exception;
use Repository\SourcesRepository;

/**
 * @coversDefaultClass \BlocksEdit\Http\RouteGenerator
 */
class RouteGeneratorTest extends TestCase
{
    /**
     * @var RouteGenerator
     */
    public $routeGenerator;

    /**
     *
     */
    public function setUp(): void
    {
        $sourcesRepo = $this->createMock(SourcesRepository::class);
        $sourcesRepo->method('getAvailableIntegrations')
            ->willReturn([]);

        $classFinder = $this->createMock(ClassFinderInterface::class);
        $classFinder->method('getNamespaceClasses')
            ->willReturn([
                'Tests\\BlocksEdit\\Http\\fixtures\\IndexController'
            ]);

        $config = $this->getConfig();
        $config->uri = 'https://app.blocksedit.com';

        $this->routeGenerator = new RouteGenerator(
            'dev',
            realpath(__DIR__ . '/fixtures'),
            $config,
            $sourcesRepo,
            $classFinder
        );
        $this->routeGenerator->setControllerNamespace('Tests\\BlocksEdit\\Http');
    }

    /**
     *
     */
    public function tearDown(): void
    {
        $cacheDir = realpath(__DIR__ . '/fixtures');
        if (file_exists($cacheDir . '/routes.php')) {
            unlink($cacheDir . '/routes.php');
        }
    }

    /**
     * @covers ::generate
     * @throws Exception
     */
    public function testGenerate()
    {
        $actual = $this->routeGenerator->generate('test1');
        $this->assertEquals('/test1', $actual);

        $actual = $this->routeGenerator->generate('test2', ['id' => 176]);
        $this->assertEquals('/test2/176', $actual);

        $actual = $this->routeGenerator->generate('test2', ['id' => 176], 'absolute');
        $this->assertEquals('https://app.blocksedit.com/test2/176', $actual);

        $actual = $this->routeGenerator->generate('test2', ['id' => 176], 'absolute', 176);
        $this->assertEquals('https://176.app.blocksedit.com/test2/176', $actual);
    }
}
