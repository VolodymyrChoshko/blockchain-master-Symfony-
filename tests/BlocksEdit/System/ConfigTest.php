<?php
namespace Tests\BlocksEdit\System;

use BlocksEdit\Config\Config;
use BlocksEdit\Test\TestCase;
use Exception;

/**
 * @coversDefaultClass \BlocksEdit\Config\Config
 */
class ConfigTest extends TestCase
{
    /**
     * @covers ::get
     * @throws Exception
     */
    public function testGet()
    {
        $config = new \BlocksEdit\Config\Config('dev', realpath(__DIR__ . '/fixtures'), __DIR__);
        $this->assertEquals('https://dev25.blocksedit.com', $config->get('uri'));
        $this->assertEquals('https://dev25.blocksedit.com', $config->uri);
        $this->assertIsArray($config->get('email'));
        $this->assertIsArray($config->email);
        $this->assertEquals('testing', $config->get('public'));
        $this->assertEquals('74acfc25', $config->get('assetsVersion'));
    }

    /**
     * @covers ::toArray
     * @throws Exception
     */
    public function testToArray()
    {
        $config = new Config('dev', realpath(__DIR__ . '/fixtures'), __DIR__);
        $this->assertIsArray($config->toArray());
    }
}
