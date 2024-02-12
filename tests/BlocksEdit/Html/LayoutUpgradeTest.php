<?php
namespace Tests\BlocksEdit\Html;

use BlocksEdit\Html\LayoutUpgrade;
use BlocksEdit\IO\Paths;
use BlocksEdit\Service\ChromeServiceInterface;
use BlocksEdit\Config\Config;
use BlocksEdit\Test\TestCase;
use Exception;
use Redis;
use Repository\TemplatesRepository;

/**
 * @coversDefaultClass \BlocksEdit\Html\LayoutUpgrade
 */
class LayoutUpgradeTest extends TestCase
{
    /**
     * @var LayoutUpgrade
     */
    public $templateUpgrade;

    /**
     *
     */
    public function setUp(): void
    {
        $config                = $this->createStub(Config::class);
        $redis                 = $this->createStub(Redis::class);
        $chromeService         = $this->createStub(ChromeServiceInterface::class);
        $templatesRepository   = $this->createStub(TemplatesRepository::class);
        $paths                 = $this->createStub(Paths::class);
        $this->templateUpgrade = new LayoutUpgrade(
            $config,
            $redis,
            $paths,
            $chromeService,
            $templatesRepository
        );
    }

    /**
     * @covers ::createNewLayout
     * @throws Exception
     */
    public function testCreateNewLayout()
    {
        $templateHTML = file_get_contents(__DIR__ . '/fixtures/template.html');
        $layoutHTML   = file_get_contents(__DIR__ . '/fixtures/layout.html');

        $actual = $this->templateUpgrade->createNewLayout($layoutHTML, $templateHTML);
        $this->assertStringContainsString(
            '<div data-block="large-title-title" class="block-edit block-no-link block-no-bold block-no-italic">This is a testing title.</div>',
            $actual
        );
    }
}
