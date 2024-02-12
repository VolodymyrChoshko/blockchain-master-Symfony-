<?php
namespace Tests\BlocksEdit\Html;

use BlocksEdit\Html\DomParser;
use BlocksEdit\Html\StylesParser;
use BlocksEdit\Test\TestCase;

/**
 * @coversDefaultClass \BlocksEdit\Html\StylesParser
 */
class StylesParserTest extends TestCase
{
    /**
     * @covers ::inlineStylesheetBEStyles
     */
    public function testInlineStylesheetBEStyles()
    {
        $dom    = DomParser::fromFile(__DIR__ . '/fixtures/styles-template.html');
        $parser = new StylesParser();
        $result = $parser->inlineStylesheetBEStyles($dom);
        $this->assertTrue($result);

        $html = (string)$dom;
        $this->assertStringContainsString('<tr style="-block-no-bold: true;">', $html);
        $this->assertStringContainsString('<td class="block-section">', $html);
    }
}
