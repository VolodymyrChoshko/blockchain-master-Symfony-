<?php
namespace Tests\BlocksEdit\Command;

use BlocksEdit\Command\ArgsParser;
use BlocksEdit\Test\TestCase;

/**
 * @coversDefaultClass \BlocksEdit\Command\ArgsParser
 */
class ArgsParserTest extends TestCase
{
    /**
     * @covers ::parse
     */
    public function testParse()
    {
        $argv = [
            'foo',
            '--bar',
            'baz'
        ];
        $argsParser = new ArgsParser();
        $actual     = $argsParser->parse($argv);
        $this->assertEquals('foo', $actual->getCommand());
        $this->assertEquals(['bar' => 'baz'], $actual->getOpts());
    }
}
