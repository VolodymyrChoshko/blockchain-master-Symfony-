<?php
namespace Tests\BlocksEdit\Http;

use BlocksEdit\Http\Mime;
use BlocksEdit\Test\TestCase;
use Exception;

/**
 * @coversDefaultClass \BlocksEdit\Http\Mime
 */
class MimeTest extends TestCase
{
    /**
     * @covers ::getMimeType
     * @throws Exception
     */
    public function testGetMimeType()
    {
        $actual = Mime::getMimeType(__FILE__);
        $this->assertEquals('text/x-php', $actual);

        $actual = Mime::getMimeType(__DIR__ . '/fixtures/index.html');
        $this->assertEquals('text/html', $actual);

        $actual = Mime::getMimeType(__DIR__ . '/fixtures/index');
        $this->assertEquals('text/html', $actual);
    }
}
