<?php
namespace Tests\BlocksEdit\Http;

use BlocksEdit\Http\SessionHandler;
use BlocksEdit\Test\TestCase;
use Exception;
use PDO;
use PDOStatement;

/**
 * @coversDefaultClass \BlocksEdit\Http\SessionHandler
 */
class SessionHandlerTest extends TestCase
{
    /**
     * @covers ::read
     * @throws Exception
     */
    public function testRead()
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute');
        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'data' => 'foo'
            ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')
            ->with(
                $this->equalTo('SELECT `data` FROM `sessions` WHERE id = ?')
            )
            ->willReturn($stmt);

        $handler = new SessionHandler($pdo);
        $actual  = $handler->read('testing');
        $this->assertEquals('foo', $actual);
    }

    /**
     * @covers ::write
     * @throws Exception
     */
    public function testWrite()
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(
                $this->callback(function($data) {
                    return count($data) === 3 && $data[0] === 'testing' && $data[2] === 'foo';
                })
            );

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')
            ->with(
                $this->equalTo('REPLACE INTO `sessions` VALUES (?, ?, ?)')
            )
            ->willReturn($stmt);

        $handler = new SessionHandler($pdo);
        $this->assertTrue($handler->write('testing', 'foo'));
    }

    /**
     * @covers ::destroy
     * @throws Exception
     */
    public function testDestroy()
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')
            ->with(
                $this->equalTo('DELETE FROM `sessions` WHERE `id` = ?')
            )
            ->willReturn($stmt);

        $handler = new SessionHandler($pdo);
        $this->assertTrue($handler->destroy('testing'));
    }

    /**
     * @covers ::gc
     * @throws Exception
     */
    public function testGc()
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')
            ->with(
                $this->equalTo('DELETE FROM `sessions` WHERE `access` < ?')
            )
            ->willReturn($stmt);

        $handler = new SessionHandler($pdo);
        $this->assertTrue($handler->gc(0));
    }

    /**
     * @covers ::close
     * @throws Exception
     */
    public function testClose()
    {
        $pdo = $this->createStub(PDO::class);
        $handler = new SessionHandler($pdo);
        $this->assertTrue($handler->close());
    }
}
