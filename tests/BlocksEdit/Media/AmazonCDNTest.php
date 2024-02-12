<?php
namespace Tests\BlocksEdit\Media;

use BlocksEdit\Http\Mime;
use BlocksEdit\Media\AmazonCDN;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\System\S3ClientFactory;
use BlocksEdit\Test\TestCase;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @coversDefaultClass \BlocksEdit\Media\AmazonCDN
 */
class AmazonCDNTest extends TestCase
{
    static $fixturesDir = '';

    /**
     * @var AmazonCDN
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
        $config = $this->getConfig();
        $s3 = S3ClientFactory::create($config);
        $this->fixture = new AmazonCDN($s3, $config->cdn, new Mime());

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
     * @return void
     */
    public function testBatchUpload()
    {
        $resp = $this->fixture->batchUpload(CDNInterface::SYSTEM_TESTING, ['test1.txt', 'test2.txt'], ['Hello', 'World']);
        $this->assertCount(2, $resp);
    }
}
