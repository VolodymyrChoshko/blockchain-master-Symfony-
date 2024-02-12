<?php
namespace Tests\BlocksEdit\Html;

use BlocksEdit\Html\Flasher;
use BlocksEdit\Http\Session;
use BlocksEdit\Test\TestCase;
use Exception;

/**
 * @coversDefaultClass \BlocksEdit\Html\Flasher
 */
class FlasherTest extends TestCase
{
    /**
     * @covers ::flash
     * @throws Exception
     */
    public function testFlash()
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->any())
            ->method('set')
            ->with(
                $this->equalTo(Flasher::SESSION_KEY),
                $this->logicalOr(
                    $this->equalTo([
                        Flasher::FLASH_SUCCESS => [],
                        Flasher::FLASH_ERROR   => []
                    ]),
                    $this->equalTo([
                        Flasher::FLASH_SUCCESS => ['Testing'],
                        Flasher::FLASH_ERROR   => []
                    ])
                )
            )
            ->will($this->returnValue(true));

        $flasher = new Flasher($session);
        $actual  = $flasher->flash(Flasher::FLASH_SUCCESS, 'Testing');
        $this->assertTrue($actual);
    }

    /**
     * @covers ::success
     * @throws Exception
     */
    public function testSuccess()
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->any())
            ->method('set')
            ->with(
                $this->equalTo(Flasher::SESSION_KEY),
                $this->logicalOr(
                    $this->equalTo([
                        Flasher::FLASH_SUCCESS => [],
                        Flasher::FLASH_ERROR   => []
                    ]),
                    $this->equalTo([
                        Flasher::FLASH_SUCCESS => ['Testing'],
                        Flasher::FLASH_ERROR   => []
                    ])
                )
            )
            ->will($this->returnValue(true));

        $flasher = new Flasher($session);
        $actual  = $flasher->success('Testing');
        $this->assertTrue($actual);
    }

    /**
     * @covers ::error
     * @throws Exception
     */
    public function testError()
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->any())
            ->method('set')
            ->with(
                $this->equalTo(Flasher::SESSION_KEY),
                $this->logicalOr(
                    $this->equalTo([
                        Flasher::FLASH_SUCCESS => [],
                        Flasher::FLASH_ERROR   => []
                    ]),
                    $this->equalTo([
                        Flasher::FLASH_SUCCESS => [],
                        Flasher::FLASH_ERROR   => ['Testing']
                    ])
                )
            )
            ->will($this->returnValue(true));

        $flasher = new Flasher($session);
        $actual  = $flasher->error('Testing');
        $this->assertTrue($actual);
    }
}
