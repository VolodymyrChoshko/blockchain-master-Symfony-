<?php
namespace Tests\BlocksEdit\Http;

use BlocksEdit\Http\Session;
use BlocksEdit\Test\TestCase;
use BlocksEdit\Http\Exception\BadRequestException;
use Exception;
use SessionHandlerInterface;

/**
 * @coversDefaultClass \BlocksEdit\Http\Session
 */
class SessionTest extends TestCase
{
    /**
     * @var Session
     */
    public $session;

    /**
     *
     */
    public function setUp(): void
    {
        $_SESSION      = [];
        $handler       = $this->createMock(SessionHandlerInterface::class);
        $this->session = new Session($handler);
    }

    /**
     * @covers ::get
     * @throws Exception
     */
    public function testGet()
    {
        $_SESSION['testing'] = 'foo';
        $this->assertEquals('foo', $this->session->get('testing'));
        $this->assertEquals('bar', $this->session->get('testing2', 'bar'));
        $this->assertNull($this->session->get('testing3'));
    }

    /**
     * @covers ::getOrBadRequest
     * @throws Exception
     */
    public function testGetOrBadRequest()
    {
        $this->expectException(BadRequestException::class);
        $this->session->getOrBadRequest('testing');
    }

    /**
     * @covers ::set
     * @throws Exception
     */
    public function testSet()
    {
        $this->session->set('foo', 'bar');
        $this->assertEquals('bar', $_SESSION['foo']);

        $this->session->set('bar', ['foo']);
        $this->assertEquals(['foo'], $_SESSION['bar']);
    }

    /**
     * @covers ::has
     * @throws Exception
     */
    public function testHas()
    {
        $this->assertFalse($this->session->has('testing'));
        $_SESSION['testing'] = 'foo';
        $this->assertTrue($this->session->has('testing'));
    }

    /**
     * @covers ::remove
     * @throws Exception
     */
    public function testRemove()
    {
        $_SESSION['testing'] = 'foo';
        $this->session->remove('testing');
        $this->assertArrayNotHasKey('testing', $_SESSION);
    }
}
