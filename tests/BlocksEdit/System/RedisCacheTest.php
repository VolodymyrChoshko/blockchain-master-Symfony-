<?php
namespace Tests\BlocksEdit\System;

use BlocksEdit\Cache\RedisCache;
use BlocksEdit\Cache\CacheTag;
use BlocksEdit\Test\TestCase;
use Redis;

/**
 * @coversDefaultClass RedisCache
 */
class RedisCacheTest extends TestCase
{
    /**
     * @var Redis
     */
    protected static $redis;

    /**
     * @var \BlocksEdit\Cache\RedisCache
     */
    protected $fixture;

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        self::$redis = new Redis();
        self::$redis->connect('127.0.0.1');
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->fixture = new RedisCache(self::$redis);
    }

    /**
     * @covers ::set
     * @covers ::get
     * @return void
     */
    public function testSetGet()
    {
        $rand = mt_rand(0, 100000);
        $key  = "testing:$rand";

        $this->fixture->set($key, 'foo', 1);
        $actual = $this->fixture->get($key);
        $this->assertEquals('foo', $actual);
        sleep(1);
        $this->assertNull($this->fixture->get($key));

        $this->fixture->set("$key:foo", ['foo', 'bar']);
        $actual = $this->fixture->get("$key:foo");
        $this->assertEquals(['foo', 'bar'], $actual);
    }

    /**
     * @covers ::exists
     * @depends testSetGet
     * @return void
     */
    public function testExists()
    {
        $rand = mt_rand(0, 100000);
        $key  = "testing:$rand";

        $this->fixture->set($key, 'foo', 1);
        $actual = $this->fixture->exists($key);
        $this->assertTrue($actual);

        $actual = $this->fixture->exists("$key:foo");
        $this->assertFalse($actual);
    }

    /**
     * @covers ::delete
     * @depends testSetGet
     * @return void
     */
    public function testDelete()
    {
        $rand = mt_rand(0, 100000);
        $key  = "testing:$rand";

        $this->fixture->set($key, 'foo');
        $this->assertEquals('foo', $this->fixture->get($key));
        $this->fixture->delete($key);
        $this->assertNull($this->fixture->get($key));
    }

    /**
     * @covers ::deleteByTags
     * @depends testSetGet
     * @depends testExists
     * @return void
     */
    public function testDeleteByTags()
    {
        $rand = mt_rand(0, 100000);
        $key  = "testing:$rand";

        $this->fixture->set("$key:foo1", 'foo1', 0, [
            new CacheTag('baz1'),
            new CacheTag('bar')
        ]);
        $this->fixture->set("$key:foo2", 'foo2', 0, [
            new CacheTag('baz2'),
            new CacheTag('bar')
        ]);
        $this->assertTrue($this->fixture->exists("$key:foo1"));
        $this->assertTrue($this->fixture->exists("$key:foo2"));

        $this->fixture->deleteByTag(new CacheTag('baz1'));
        $this->assertFalse($this->fixture->exists("$key:foo1"));
        $this->assertTrue($this->fixture->exists("$key:foo2"));

        $this->fixture->deleteByTag(new CacheTag('baz2'));
        $this->assertFalse($this->fixture->exists("$key:foo2"));

        $this->fixture->set("$key:foo1", 'foo1', 0, [
            new CacheTag('baz1'),
            new CacheTag('bar')
        ]);
        $this->fixture->set("$key:foo2", 'foo2', 0, [
            new CacheTag('baz2'),
            new CacheTag('bar')
        ]);
        $this->assertTrue($this->fixture->exists("$key:foo1"));
        $this->assertTrue($this->fixture->exists("$key:foo2"));
        $this->fixture->deleteByTag(new Cachetag('bar'));
        $this->assertFalse($this->fixture->exists("$key:foo1"));
        $this->assertFalse($this->fixture->exists("$key:foo2"));
    }
}
