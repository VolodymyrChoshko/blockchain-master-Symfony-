<?php
namespace Tests\BlocksEdit\IO;

use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\Paths;
use BlocksEdit\Test\TestCase;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Repository\ComponentsRepository;
use Repository\EmailRepository;
use Repository\SectionLibraryRepository;
use Repository\TemplatesRepository;
use SplFileInfo;

/**
 * @coversDefaultClass Paths
 */
class PathsTest extends TestCase
{
    static $fixturesDir = '';

    /**
     * @var Paths
     */
    public $fixture;

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        umask(0);
        self::$fixturesDir = __DIR__ . '/fixtures';
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->fixture = new Paths(
            $this->getConfig(),
            $this->createStub(TemplatesRepository::class),
            $this->createStub(EmailRepository::class),
            $this->createStub(ComponentsRepository::class),
            $this->createStub(SectionLibraryRepository::class)
        );
        $this->fixture->setModifiableDirs([
            __DIR__,
        ]);
        if (!file_exists(self::$fixturesDir)) {
            mkdir(self::$fixturesDir);
        }
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::$fixturesDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $fileInfo */
        foreach ($files as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getRealPath());
            } else {
                unlink($fileInfo->getRealPath());
            }
        }

        rmdir(self::$fixturesDir);
    }

    /**
     * @covers ::combine
     */
    public function testCombine()
    {
        $actual = Paths::combine('foo', '/bar/', '/', 'baz');
        $this->assertEquals('/foo/bar/baz', $actual);

        $actual = Paths::combine('/foo', '/bar/', '/', 'baz');
        $this->assertEquals('/foo/bar/baz', $actual);
    }

    /**
     * @covers ::remove
     * @throws Exception
     */
    public function testRemoveDirectory()
    {
        $dir = self::$fixturesDir . '/path_test';
        mkdir($dir);
        file_put_contents($dir . '/test1.txt', 'Hello');
        file_put_contents($dir . '/test2.txt', 'World');

        $dir = self::$fixturesDir . '/path_test/deep';
        mkdir($dir);
        file_put_contents($dir . '/test1.txt', 'Hello');

        $this->assertTrue(file_exists(self::$fixturesDir . '/path_test'));
        $this->assertTrue($this->fixture->remove(self::$fixturesDir . '/path_test'));
        $this->assertFalse(file_exists(self::$fixturesDir . '/path_test'));
    }

    /**
     * @covers ::createTempDirectory
     * @throws Exception
     */
    public function testCreateTempDirectory()
    {
        $actual = $this->fixture->createTempDirectory('testing', self::$fixturesDir);
        $this->assertTrue(file_exists($actual) && is_dir($actual));

        if (file_exists($actual)) {
            rmdir($actual);
        }
    }

    /**
     * @covers ::copy
     * @return void
     * @throws IOException
     */
    public function testCopyDirectory()
    {
        $source = self::$fixturesDir . '/path_source';
        $target = self::$fixturesDir . '/path_target';

        mkdir($source);
        file_put_contents($source . '/test1.txt', 'Hello');
        file_put_contents($source . '/test2.txt', 'World');
        mkdir($source . '/sub_dir');
        file_put_contents($source . '/sub_dir/test3.txt', 'Hello');
        file_put_contents($source . '/sub_dir/test4.txt', 'World');

        $this->fixture->copy($source, $target, 0700);

        $this->assertTrue(file_exists($target));
        $this->assertTrue(file_exists($target . '/test1.txt'));
        $this->assertTrue(file_exists($target . '/test2.txt'));
        $this->assertTrue(file_exists($target . '/sub_dir/test3.txt'));
        $this->assertTrue(file_exists($target . '/sub_dir/test4.txt'));

        $perms = substr(sprintf('%o', fileperms($target)), -4);
        $this->assertEquals('0700', $perms);
        $perms = substr(sprintf('%o', fileperms($target . '/sub_dir')), -4);
        $this->assertEquals('0700', $perms);
        $perms = substr(sprintf('%o', fileperms($target . '/sub_dir/test3.txt')), -4);
        $this->assertEquals('0700', $perms);
    }

    /**
     * @covers ::copy
     * @return void
     * @throws IOException
     */
    public function testCopyDirectoryFilter()
    {
        $source = self::$fixturesDir . '/path_source';
        $target = self::$fixturesDir . '/path_target';

        mkdir($source);
        file_put_contents($source . '/test1.txt', 'Hello');
        file_put_contents($source . '/test2.txt', 'World');
        mkdir($source . '/sub_dir');
        file_put_contents($source . '/sub_dir/test3.txt', 'Hello');
        file_put_contents($source . '/sub_dir/test4.txt', 'World');

        $this->fixture->copy($source, $target, 0700, function(\SplFileInfo $fileInfo) {
            return !$fileInfo->isDir();
        });

        $this->assertTrue(file_exists($target));
        $this->assertTrue(file_exists($target . '/test1.txt'));
        $this->assertTrue(file_exists($target . '/test2.txt'));
        $this->assertFalse(file_exists($target . '/sub_dir/test3.txt'));
        $this->assertFalse(file_exists($target . '/sub_dir/test4.txt'));
    }

    /**
     * @covers ::move
     * @return void
     * @throws IOException
     */
    public function testMoveDirectory()
    {
        $source = self::$fixturesDir . '/path_source';
        $target = self::$fixturesDir . '/path_target';

        mkdir($source);
        file_put_contents($source . '/test1.txt', 'Hello');
        file_put_contents($source . '/test2.txt', 'World');
        mkdir($source . '/sub_dir');
        file_put_contents($source . '/sub_dir/test3.txt', 'Hello');
        file_put_contents($source . '/sub_dir/test4.txt', 'World');

        $this->fixture->move($source, $target, 0700);

        $this->assertFalse(file_exists($source));
        $this->assertTrue(file_exists($target));
        $this->assertTrue(file_exists($target . '/test1.txt'));
        $this->assertTrue(file_exists($target . '/test2.txt'));
        $this->assertTrue(file_exists($target . '/sub_dir/test3.txt'));
        $this->assertTrue(file_exists($target . '/sub_dir/test4.txt'));

        $perms = substr(sprintf('%o', fileperms($target)), -4);
        $this->assertEquals('0700', $perms);
        $perms = substr(sprintf('%o', fileperms($target . '/sub_dir')), -4);
        $this->assertEquals('0700', $perms);
        $perms = substr(sprintf('%o', fileperms($target . '/sub_dir/test3.txt')), -4);
        $this->assertEquals('0700', $perms);
    }

    /**
     * @covers ::getCanonicalPath
     * @return void
     */
    public function testGetCanonicalPath()
    {
        $source = self::$fixturesDir . '/../../../path_source';
        $this->assertEquals(
            '/media/sean/work2/www/blocksedit25/tests/path_source',
            $this->fixture->getCanonicalPath($source)
        );

        $source = '../..';
        $this->assertEquals(
            '',
            $this->fixture->getCanonicalPath($source)
        );
    }

    /**
     * @covers ::isModifiable
     * @return void
     * @throws IOException
     */
    public function testIsModifiable()
    {
        $this->fixture->setModifiableDirs([
            'templates',
            'screenshots'
        ]);
        $dirs = [
            '/media/sean/work2/www/blocksedit25/public/screenshots/' => true,
            '/media/sean/work2/www/blocksedit25/templates/' => true,
            '/media/sean/work2/www/blocksedit25/public/screenshots/components/mobile' => true,
            '/media/sean/work2/www/blocksedit25/config/' => false,
            '/media/sean/work2/www/blocksedit25/public/screenshots/../../config' => false
        ];
        foreach($dirs as $dir => $expected) {
            $this->assertEquals($expected, $this->fixture->isModifiable($dir));
        }
    }
}
