<?php
namespace Tests\BlocksEdit\Util;

use BlocksEdit\Util\Strings;
use BlocksEdit\Test\TestCase;
use Exception;

/**
 * @coversDefaultClass \BlocksEdit\Util\Strings
 */
class StringsTest extends TestCase
{
    /**
     * @covers ::startsWith
     * @throws Exception
     */
    public function testStartsWith()
    {
        $this->assertTrue(Strings::startsWith('Testing', 'Test'));
        $this->assertFalse(Strings::startsWith('Testing', 'test'));
        $this->assertTrue(Strings::startsWith('Testing', 'test', false));
    }

    /**
     * @covers ::endsWith
     * @throws Exception
     */
    public function testEndsWith()
    {
        $this->assertTrue(Strings::endsWith('Testing', 'ing'));
        $this->assertFalse(Strings::endsWith('Testing', 'ING'));
        $this->assertTrue(Strings::endsWith('Testing', 'ING', false));
    }

    /**
     * @covers ::getSlug
     * @throws Exception
     */
    public function testGetSlug()
    {
        $this->assertEquals('testing1', Strings::getSlug('tes*ting(1)'));
        $this->assertEquals('tes-ting1', Strings::getSlug('tes ting(1)'));
    }

    /**
     * @covers ::camelToSnake
     * @return void
     */
    public function testCamelToSnake()
    {
        $this->assertEquals('usr_id', Strings::camelToSnake('usrId'));
        $this->assertEquals('tmp_usr_id', Strings::camelToSnake('TmpUsrId'));
    }

    /**
     * @covers ::snakeToCamel
     * @return void
     */
    public function testSnakeToCamel()
    {
        $this->assertEquals('usrId', Strings::snakeToCamel('usr_id'));
        $this->assertEquals('tmpUsrId', Strings::snakeToCamel('tmp_usr_id'));
    }
}
