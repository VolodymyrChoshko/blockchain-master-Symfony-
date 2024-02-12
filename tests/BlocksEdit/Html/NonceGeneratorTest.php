<?php
namespace Tests\BlocksEdit\Html;

use BlocksEdit\Html\FlasherInterface;
use BlocksEdit\Html\NonceGenerator;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\SessionInterface;
use BlocksEdit\Test\TestCase;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Redis;

/**
 * @coversDefaultClass \BlocksEdit\Html\NonceGenerator
 */
class NonceGeneratorTest extends TestCase
{
    /**
     * @var NonceGenerator
     */
    public $fixture;

    /**
     * @var MockObject|Redis
     */
    public $redis;

    /**
     * @var FlasherInterface|MockObject
     */
    public $flasher;

    /**
     *
     */
    public function setUp(): void
    {
        $this->redis   = $this->createMock(Redis::class);
        $this->flasher = $this->createMock(FlasherInterface::class);
        $this->fixture = new NonceGenerator($this->redis, $this->flasher);
    }

    /**
     * @covers ::generate
     * @throws Exception
     */
    public function testGenerate()
    {
        $this->redis->expects($this->any())
            ->method('setex')
            ->with(
                $this->equalTo(NonceGenerator::PREFIX . ':testing'),
                $this->equalTo(3600),
                $this->callback(function($v) {
                    return (bool)preg_match('/[a-zA-Z0-9]{32}/', $v);
                })
            );
        $actual = $this->fixture->generate('testing', 3600);
        $this->assertRegExp('/[a-zA-Z0-9]{32}/', $actual);

        $actual2 = $this->fixture->generate('testing', 3600);
        $this->assertNotEquals($actual, $actual2);
    }

    /**
     * @covers ::verify
     * @throws Exception
     */
    public function testVerify()
    {
        $nonce = '3e1eb5caeb86d1b15e46133d0bdb4726';
        $cache = [
            NonceGenerator::PREFIX . ':testing' => $nonce
        ];

        $this->redis->expects($this->exactly(2))
            ->method('del')
            ->with(
                $this->equalTo(NonceGenerator::PREFIX . ':testing')
            )
            ->willReturnCallback(function($key) use(&$cache) {
                unset($cache[$key]);
            });
        $this->redis->expects($this->exactly(2))
            ->method('get')
            ->with(
                $this->equalTo(NonceGenerator::PREFIX . ':testing')
            )
            ->willReturnCallback(function($key) use(&$cache) {
                if (isset($cache[$key])) {
                    return $cache[$key];
                }
                return false;
            });

        $actual = $this->fixture->verify('testing', $nonce);
        $this->assertTrue($actual);

        $actual = $this->fixture->verify('testing', $nonce);
        $this->assertFalse($actual);
    }

    /**
     * @covers ::verifyRequest
     * @throws Exception
     */
    public function testVerifyRequest()
    {
        $nonce   = '3e1eb5caeb86d1b15e46133d0bdb4726';
        $session = $this->createStub(SessionInterface::class);
        $request = new Request($session, ['HTTP_HOST' => '176.app.blocksedit.com'], ['token' => $nonce], [], [], []);
        $cache   = [
            NonceGenerator::PREFIX . ':testing' => $nonce
        ];

        $this->redis->expects($this->exactly(2))
            ->method('del')
            ->with(
                $this->equalTo(NonceGenerator::PREFIX . ':testing')
            )
            ->willReturnCallback(function($key) use(&$cache) {
                unset($cache[$key]);
            });
        $this->redis->expects($this->exactly(2))
            ->method('get')
            ->with(
                $this->equalTo(NonceGenerator::PREFIX . ':testing')
            )
            ->willReturnCallback(function($key) use(&$cache) {
                if (isset($cache[$key])) {
                    return $cache[$key];
                }
                return false;
            });

        $this->flasher->expects($this->once())
            ->method('error');

        $actual = $this->fixture->verifyRequest('testing', $request);
        $this->assertTrue($actual);

        $actual = $this->fixture->verifyRequest('testing', $request);
        $this->assertFalse($actual);
    }
}
