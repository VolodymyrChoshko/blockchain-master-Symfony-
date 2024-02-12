<?php
namespace BlocksEdit\Test;

use BlocksEdit\Config\Config;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

/**
 * Class TestCase
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $services = [];

    /**
     *
     */
    public static function setUpBeforeClass(): void
    {
        $_SERVER['APP_ENV'] = 'testing';
    }

    /**
     * @param array $services
     *
     * @phpstan-ignore-next-line
     * @return MockObject|ContainerInterface
     */
    public function getContainer(array $services = []): MockObject
    {
        $this->services  = $services;
        $this->container = $this->createStub(ContainerInterface::class);
        $this->container->method('get')
            ->willReturnCallback(function($id) {
                if (!isset($this->services[$id])) {
                    throw new Exception\NotFoundException("You have requested a non-existent service \"$id\".");
                }
                return $this->services[$id];
            });
        $this->container->method('has')
            ->willReturnCallback(function($id) {
                return isset($this->services[$id]);
            });

        /** @phpstan-ignore-next-line */
        return $this->container;
    }

    /**
     * @param string $name
     * @param        $obj
     *
     * @return void
     */
    public function addContainerService(string $name, $obj)
    {
        $this->services[$name] = $obj;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return new Config(
            'testing',
            $this->getRootDir() . '/config',
            $this->getRootDir()
        );
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return realpath(__DIR__ . '/../../../');
    }
}
