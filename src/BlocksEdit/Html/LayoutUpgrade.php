<?php
namespace BlocksEdit\Html;

use BlocksEdit\Service\ChromeServiceInterface;
use BlocksEdit\Config\Config;
use BlocksEdit\IO\Paths;
use BlocksEdit\Util\Media;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use Gumlet\ImageResize;
use Redis;
use Repository\TemplatesRepository;
use simplehtmldom_1_5\simple_html_dom_node;
use simplehtmldom_1_5\simple_html_dom;

/**
 * Class LayoutUpgrade
 */
class LayoutUpgrade
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var ChromeServiceInterface
     */
    protected $chromeService;

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * Constructor
     *
     * @param Config                 $config
     * @param Redis                  $redis
     * @param Paths                  $paths
     * @param ChromeServiceInterface $chromeService
     * @param TemplatesRepository    $templatesRepository
     */
    public function __construct(
        Config $config,
        Redis $redis,
        Paths $paths,
        ChromeServiceInterface $chromeService,
        TemplatesRepository $templatesRepository
    )
    {
        $this->config              = $config;
        $this->redis               = $redis;
        $this->paths               = $paths;
        $this->chromeService       = $chromeService;
        $this->templatesRepository = $templatesRepository;
    }

    /**
     * @param int    $tid
     * @param string $html
     *
     * @return bool
     * @throws Exception
     */
    public function upgrade(int $tid, string $html): bool
    {
        $layouts = $this->templatesRepository->getLayouts($tid);
        if (count($layouts) > 0) {
            foreach($layouts as $layout) {
                $location = Paths::combine(
                    $this->paths->dirLayout($tid, $layout['tmp_id']),
                    $layout['tmp_location']
                );
                if (file_exists($location)) {
                    $layoutHTML = file_get_contents($location);
                    $layoutHTML = $this->createNewLayout($layoutHTML, $html);
                    file_put_contents($location, $layoutHTML);

                    $json = $this->chromeService->scrape($layoutHTML, [
                        'width' => 800
                    ]);
                    $screenshot = base64_decode($json['screenshot']);

                    $output = $this->paths->dirLayoutScreenshot($tid, $layout['tmp_id'], Paths::SCREENSHOT);
                    file_put_contents($output, $screenshot);
                    $output = $this->paths->dirLayoutScreenshot($tid, $layout['tmp_id'], Paths::SCREENSHOT_MOBILE);
                    file_put_contents($output, $screenshot);

                    $output = $this->paths->dirLayoutScreenshot($tid, $layout['tmp_id'], Paths::SCREENSHOT_200);
                    $cropped = new ImageResize($output);
                    $cropped->resizeToWidth('200', true);
                    $cropped->save($output, null, Media::JPEG_QUALITY);
                }

                $this->redis->del(sprintf('layouts:upgrading:%d', $layout['tmp_id']));
            }
        }

        return true;
    }

    /**
     * @param string $oldLayoutHTML
     * @param string $newTemplateHTML
     *
     * @return string
     */
    public function createNewLayout(string $oldLayoutHTML, string $newTemplateHTML): string
    {
        $lDOM   = new DOMDocument();
        $lDOM->registerNodeClass('DOMElement', JSLikeHTMLElement::class);
        @$lDOM->loadHTML($oldLayoutHTML);
        $lXpath = new DOMXPath($lDOM);

        $tDOM   = new DOMDocument();
        $tDOM->registerNodeClass('DOMElement', JSLikeHTMLElement::class);
        @$tDOM->loadHTML($newTemplateHTML);
        $tXpath = new DOMXPath($tDOM);

        /** @var JSLikeHTMLElement[] $lGroups */
        $lGroups = $lXpath->query('//*[@data-group]');
        foreach($lGroups as $lGroup) {
            $groupName      = $lGroup->getAttribute('data-group');
            $templateGroups = $tXpath->query("//*[@data-group=\"${groupName}\"]");

            if ($templateGroups->count() > 0) {
                /** @var JSLikeHTMLElement[] $lBlocks */
                $lBlocks           = $lXpath->query('//*[@data-block]', $lGroup);
                $lClone            = clone $lGroup;
                $lGroup->innerHTML = $templateGroups[0]->innerHTML;

                foreach($lBlocks as $i => $lBlock) {
                    $blockName      = $lBlock->getAttribute('data-block');
                    $templateBlocks = $tXpath->query("//*[@data-block=\"${blockName}\"]", $templateGroups[0]);
                    if ($templateBlocks->count() > 0) {
                        /** @var DOMElement $tBlock */
                        $tBlock = $templateBlocks[0];
                        switch($tBlock->tagName) {
                            case 'img':
                                $lBlock->setAttribute('src', $lClone->getAttribute('src'));
                                break;
                            case 'a':
                                $lBlock->setAttribute('href', $lClone->getAttribute('href'));
                                $lBlock->textContent = $lClone->textContent;
                                break;
                            default:
                                $lBlock->textContent = $lClone->textContent;;
                                break;
                        }
                    }
                }
            }

            die();
        }
die();
        /*return $lDOM->saveHTML();
        die();

        // Handle deprecated data-style attribute (replaced by data-group).
        $layoutDOM   = DomParser::fromString($oldLayoutHTML);
        $templateDOM = DomParser::fromString($newTemplateHTML);
        foreach($layoutDOM->find('a[data-style]') as $element) {
            $element->setAttribute('data-group', $element->getAttribute('data-style'));
        }
        foreach($templateDOM->find('a[data-style]') as $element) {
            $element->setAttribute('data-group', $element->getAttribute('data-style'));
        }

        foreach($layoutDOM->find('*[data-group]') as $layoutNode) {
            $groupName         = $layoutNode->getAttribute('data-group');
            $templateGroups = $templateDOM->find('*[data-group="' . $groupName . '"]');
            if ($templateGroups) {
                $this->mergeGroup($layoutNode, $templateGroups[0], $layoutDOM);
            }
        }

        return (string)$layoutDOM;*/
    }

    /**
     * @param simple_html_dom_node $layoutNode
     * @param simple_html_dom_node $templateNode
     * @param simple_html_dom      $dom
     */
    protected function mergeGroup(simple_html_dom_node $layoutNode, simple_html_dom_node $templateNode, simple_html_dom $dom)
    {
        $layoutNodeClone       = clone $layoutNode;
        // $layoutNode->outertext = $templateNode->outertext();
        $layoutNode->nodes    = $templateNode->nodes;
        $layoutNode->children = $templateNode->children;
        $layoutNode->attr     = $templateNode->attr;
        $layoutNode->_        = $templateNode->_;
        $dom->nodes[$layoutNode->_[HDOM_INFO_BEGIN]] = $layoutNode;
        unset($layoutNode->_[HDOM_INFO_INNER]);
        // dump($dom->nodes[$layoutNode->_[HDOM_INFO_BEGIN]]->makeup());die();

        foreach($layoutNode->find('*[data-block]') as $lnode) {
            $block          = $lnode->getAttribute('data-block');
            $templateBlocks = $layoutNodeClone->find('*[data-block="' . $block . '"]');
            if ($templateBlocks) {
                $node = $templateBlocks[0];
                switch($node->tag) {
                    case 'img':
                        $lnode->setAttribute('src', $node->getAttribute('src'));
                        break;
                    case 'a':
                        $lnode->setAttribute('href', $node->getAttribute('href'));
                        $lnode->innertext = $node->innertext();
                        break;
                    default:
                        $lnode->innertext = $node->innertext();
                        break;
                }
            }
        }

        dump($layoutNode->outertext());die();
    }
}
