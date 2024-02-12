<?php
namespace Tests\BlocksEdit\Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Console;
use BlocksEdit\System\ClassFinderInterface;
use BlocksEdit\Config\Config;
use BlocksEdit\Test\TestCase;
use Command\CacheClearCommand;
use Exception;

/**
 * @coversDefaultClass \BlocksEdit\Command\Console
 */
class ConsoleTest extends TestCase
{
    /**
     * @covers ::run
     * @throws Exception
     */
    public function testRun()
    {
        $root            = $this->getRootDir();
        $classFinderMock = $this->createStub(ClassFinderInterface::class);
        $classFinderMock->method('getNamespaceClasses')
            ->willReturn([
                "$root/src/Command/CacheClearCommand.php" => CacheClearCommand::class
            ]);
        $container = $this->getContainer([
            Config::class               => $this->getConfig(),
            ClassFinderInterface::class => $classFinderMock
        ]);

        $console = new Console($container);
        $stdOut  = fopen('php://memory', 'rw');
        $console->setOut($stdOut);
        $console->run(new Args('test'), false);

        rewind($stdOut);
        $actual = stream_get_contents($stdOut);
        $this->assertStringContainsString('Example:', $actual);
    }
}
