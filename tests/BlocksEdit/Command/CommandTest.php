<?php
namespace Tests\BlocksEdit\Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Test\TestCase;

/**
 * @coversDefaultClass \BlocksEdit\Command\Command
 */
class CommandTest extends TestCase
{
    /**
     * @var Command
     */
    public $fixture;

    /**
     * Called before each test
     */
    public function setUp(): void
    {
        $this->fixture = new class extends Command
        {
            public function run(Args $args) {}
        };
    }

    /**
     * @covers ::writeLine
     */
    public function testWriteLn()
    {
        $stdOut = fopen('php://memory', 'rw');
        $this->fixture->setOut($stdOut);
        $this->fixture->writeLine('%s, %s!', 'Hello', 'World');

        rewind($stdOut);
        $actual = stream_get_contents($stdOut);
        $this->assertEquals("Hello, World!\n", $actual);
    }

    /**
     * @covers ::errorLine
     */
    public function testErrorLine()
    {
        $stdErr = fopen('php://memory', 'rw');
        $this->fixture->setErr($stdErr);
        $this->fixture->errorLine('%s, %s!', 'Hello', 'World');

        rewind($stdErr);
        $actual = stream_get_contents($stdErr);
        $this->assertEquals("Hello, World!\n", $actual);
    }
}
