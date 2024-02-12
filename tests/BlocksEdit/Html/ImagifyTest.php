<?php
namespace Tests\BlocksEdit\Html;

use BlocksEdit\Html\DomParser;
use BlocksEdit\Html\Imagify;
use BlocksEdit\Test\TestCase;
use Exception;

/**
 * @coversDefaultClass \BlocksEdit\Html\Imagify
 */
class ImagifyTest extends TestCase
{
    public $imagify;

    /**
     *
     */
    public function setUp(): void
    {
        $config = $this->getConfig();
        $config->uri = 'https://app.blocksedit.com';
        $this->imagify = new Imagify($config);
    }

    /**
     * @covers ::replaceOriginals
     * @throws Exception
     */
    public function testReplaceOriginals()
    {
        $html = <<< HTML
            <!doctype html>
            <html>
                <body>
                    <img src="image.jpg" alt="1" />
                    <img src="image-2.jpg" alt="2" />
                </body>
            </html>
HTML;
        $dom    = DomParser::fromString($html);
        $actual = (string)$this->imagify->replaceOriginals($dom, [
            'image.jpg'   => 'https://blocksedit.com/image.jpg',
            'image-2.jpg' => 'https://blocksedit.com/image-2.jpg'
        ]);

        $this->assertStringContainsString('<img src="https://blocksedit.com/image.jpg" alt="1" />', $actual);
        $this->assertStringContainsString('<img src="https://blocksedit.com/image-2.jpg" alt="2" />', $actual);
    }
}
