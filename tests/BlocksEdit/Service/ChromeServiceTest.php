<?php
namespace Tests\BlocksEdit\Service;

use BlocksEdit\Service\ChromeService;
use BlocksEdit\Test\TestCase;
use Exception;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @coversDefaultClass \BlocksEdit\Service\ChromeService
 */
class ChromeServiceTest extends TestCase
{
    /**
     * @covers ::pdf
     * @throws Exception
     */
    public function testPdf()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn('testing');

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('https://blocksedit.com/screenshot/pdf')
            )
            ->willReturn($response);

        $chrome = new ChromeService('https://blocksedit.com', $client);
        $actual = $chrome->pdf('<body />');
        $this->assertEquals('testing', $actual);
    }

    /**
     * @covers ::screenshot
     * @throws Exception
     */
    public function testScreenshot()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn('testing');

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('https://blocksedit.com/screenshot')
            )
            ->willReturn($response);

        $chrome = new ChromeService('https://blocksedit.com', $client);
        $actual = $chrome->screenshot('<body />');
        $this->assertEquals('testing', $actual);
    }

    /**
     * @covers ::scrape
     * @throws Exception
     */
    public function testScrape()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode('testing'));

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('https://blocksedit.com/scrape')
            )
            ->willReturn($response);

        $chrome = new ChromeService('https://blocksedit.com', $client);
        $actual = $chrome->scrape('<body />');
        $this->assertEquals('testing', $actual);
    }
}
