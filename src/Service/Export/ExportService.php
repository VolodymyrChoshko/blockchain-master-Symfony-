<?php
namespace Service\Export;

use BlocksEdit\Html\DomParser;
use BlocksEdit\Html\Imagify;
use BlocksEdit\Html\Scriptify;
use BlocksEdit\Html\Utils;
use BlocksEdit\Integrations\IntegrationInterface;
use BlocksEdit\Integrations\Services\SalesForceIntegration;
use BlocksEdit\IO\FilesTrait;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\System\Required;
use BlocksEdit\Util\Salesforce;
use Entity\Email;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Repository\EmailRepository;
use Repository\ImagesRepository;
use Repository\SourcesRepository;
use Repository\TemplatesRepository;
use RuntimeException;
use simplehtmldom_1_5\simple_html_dom;

/**
 * Class ExportService
 */
class ExportService
{
    use PathsTrait;
    use FilesTrait;

    /**
     * @var HtmlToText
     */
    protected $htmlToText;

    /**
     * @var int
     */
    protected $oid = 0;

    /**
     * @var int
     */
    protected $uid = 0;

    /**
     * Constructor
     *
     * @param HtmlToText $htmlToText
     * @param int $oid
     * @param int $uid
     */
    public function __construct(HtmlToText $htmlToText, int $uid, int $oid)
    {
        $this->htmlToText = $htmlToText;
        $this->oid = $oid;
        $this->uid = $uid;
    }

    /**
     * @param int $eid
     * @param int $version
     *
     * @return ExportResults
     * @throws Exception
     */
    public function forSendLink(int $eid, int $version = 0): ExportResults
    {
        $email    = $this->getEmail($eid);
        $dir      = $this->paths->dirEmail($eid, $version);
        $htmlData = $this->emailRepository->getHtml($eid, $version);
        $dom      = $htmlData->getDom();
        $dom      = Utils::restoreBlockHide($dom);
        $htmlData->setHtml($this->sanitize($dom));

        return new ExportResults($email, $htmlData, [], $dir, $this->files);
    }

    /**
     * @param int  $eid
     * @param int  $version
     * @param bool $isRelative
     * @param int  $sourceID
     *
     * @return ExportResults
     * @throws GuzzleException
     * @throws Exception
     */
    public function forBuilder(
        int $eid,
        int $version,
        bool $isRelative = true,
        int $sourceID = 0
    ): ExportResults
    {
        // $imagesRepo->findByTemplateAndVersion($email['ema_tmp_id'], $args['templateVersion'])
        $integration = null;
        if (!$isRelative && $sourceID) {
            $integration = $this->getIntegration($this->oid, $this->uid, $sourceID);
            if (!$integration) {
                throw new RuntimeException("Could not find integration $sourceID.");
            }
        }

        $email    = $this->getEmail($eid);
        $template = $this->templatesRepository->findByID($email->getTemplate()->getId());
        if (!$template) {
            throw new RuntimeException('Template ' . $email->getTemplate()->getId() . ' not found.');
        }

        $swapType = $isRelative ? Imagify::SWAP_RELATIVE : Imagify::SWAP_MANUAL;
        if ($sourceID) {
            $swapType = Imagify::SWAP_SOURCE;
        }

        $dir      = $this->paths->dirEmail($eid, $version);
        $htmlData = $this->emailRepository->getHtml($eid, $version);
        $dom      = $htmlData->getDom();
        $dom      = Utils::restoreBlockHide($dom);
        $images   = $this->imagify->swapImages($dom, $swapType, $template['tmp_img_base_url'], $integration);
        $htmlData->setHtml($this->sanitize($dom));

        return new ExportResults($email, $htmlData, $images, $dir, $this->files);
    }

    /**
     * @param int $eid
     * @param int $version
     *
     * @return ExportResults
     * @throws Exception
     */
    public function forScreenshot(int $eid, int $version): ExportResults
    {
        $email    = $this->getEmail($eid);
        $dir      = $this->paths->dirEmail($eid, $version);
        $htmlData = $this->emailRepository->getHtml($eid, $version);
        $dom      = $htmlData->getDom();
        $htmlData->setHtml($this->sanitize($dom));

        return new ExportResults($email, $htmlData, [], $dir, $this->files);
    }

    /**
     * @param int $eid
     * @param int $version
     *
     * @return ExportResults
     * @throws Exception
     */
    public function forPdf(int $eid, int $version): ExportResults
    {
        $email    = $this->getEmail($eid);
        $dir      = $this->paths->dirEmail($eid, $version);
        $htmlData = $this->emailRepository->getHtml($eid, $version);
        $dom      = $htmlData->getDom();
        $htmlData->setHtml($this->sanitize($dom, false));

        return new ExportResults($email, $htmlData, [], $dir, $this->files);
    }

    /**
     * @param int  $eid
     * @param int  $version
     * @param bool $isRelative
     * @param int  $sourceID
     *
     * @return ExportResults
     * @throws Exception|GuzzleException
     */
    public function forText(
        int $eid,
        int $version,
        bool $isRelative = true,
        int $sourceID = 0
    ): ExportResults
    {
        $integration = null;
        if (!$isRelative && $sourceID) {
            $integration = $this->getIntegration($this->oid, $this->uid, $sourceID);
            if (!$integration) {
                throw new RuntimeException("Could not find integration $sourceID.");
            }
        }

        $email    = $this->getEmail($eid);
        $template = $this->templatesRepository->findByID($email->getTemplate()->getId());
        if (!$template) {
            throw new RuntimeException('Template ' . $email->getTemplate()->getId() . ' not found.');
        }

        $swapType = $isRelative ? Imagify::SWAP_RELATIVE : Imagify::SWAP_MANUAL;
        if ($sourceID) {
            $swapType = Imagify::SWAP_SOURCE;
        }

        $dir      = $this->paths->dirEmail($eid, $version);
        $htmlData = $this->emailRepository->getHtml($eid, $version);
        $dom      = $htmlData->getDom();
        $dom      = Utils::restoreBlockHide($dom);
        $images   = $this->imagify->swapImages($dom, $swapType, $template['tmp_img_base_url'], $integration);

        $text = $this->htmlToText->toText($this->sanitize($dom, false));
        $htmlData->setHtml($text);

        return new ExportResults($email, $htmlData, $images, $dir, $this->files);
    }

    /**
     * @param int $eid
     * @param int $version
     *
     * @return ExportResults
     * @throws Exception
     */
    public function forRichText(int $eid, int $version): ExportResults
    {
        $email    = $this->getEmail($eid);
        $dir      = $this->paths->dirEmail($eid, $version);
        $htmlData = $this->emailRepository->getHtml($eid, $version);
        $dom      = $htmlData->getDom();

        $text = $this->htmlToText->toRichText($this->sanitize($dom, false));
        $htmlData->setHtml($text);

        return new ExportResults($email, $htmlData, [], $dir, $this->files);
    }

    /**
     * @param int $eid
     *
     * @return Email
     * @throws Exception
     */
    protected function getEmail(int $eid): Email
    {
        $email = $this->emailRepository->findByID($eid, true);
        if (!$email) {
            throw new RuntimeException("Email $eid not found.");
        }

        return $email;
    }

    /**
     * @param int $oid
     * @param int $uid
     * @param int $sourceID
     *
     * @return IntegrationInterface|null
     * @throws Exception
     */
    protected function getIntegration(int $oid, int $uid, int $sourceID): ?IntegrationInterface
    {
        $source = $this->sourcesRepository->findByID($sourceID);
        if ($source) {
            $integration = $this->sourcesRepository->integrationFactory(
                $source,
                $uid,
                $oid
            );
            if ($integration instanceof SalesForceIntegration) {
                $integration->clearLocalCache();
            }

            return $integration;
        }

        return null;
    }

    /**
     * @param simple_html_dom $dom
     * @param bool            $replaceScripts
     *
     * @return string
     */
    protected function sanitize(simple_html_dom $dom, bool $replaceScripts = true): string
    {
        foreach($dom->find('*[style=";"]') as $element) {
            $element->removeAttribute('style');
        }
        foreach($dom->find('*[style=null]') as $element) {
            $element->removeAttribute('style');
        }
        foreach($dom->find('a[alias=""]') as $element) {
            if ($element->getAttribute('alias') === '') {
                $element->removeAttribute('alias');
            }
        }
        foreach($dom->find('img') as $element) {
            $element->removeAttribute('original');
        }
        foreach($dom->find('*[contenteditable="true"]') as $element) {
            $element->removeAttribute('contenteditable');
        }
        foreach($dom->find('style') as $element) {
            if (strpos($element->innertext(), '[contenteditable=true]:focus') !== false) {
                $element->outertext = '';
            }
        }
        foreach($dom->find('*[style]') as $element) {
            $style = $element->getAttribute('style');
            if ($style === '') {
                $element->removeAttribute('style');
            }
        }
        foreach($dom->find('.block-edit-empty') as $item) {
            $item->outertext = '';
        }

        if ($replaceScripts) {
            $codeBlocks = [];
            $headBlocks = [];

            // Replace AMPscript HTML blocks with plain text.
            foreach ($dom->find('.block-script-sec') as $item) {
                $script = $item->find('.block-script')[0];
                $code   = str_replace('<br />', "\r\n", $script->innertext());
                $code   = str_replace('<br/>', "\r\n", $code);
                $code   = str_replace('<br>', "\r\n", $code);
                $code   = str_replace('&lt;', '<', $code);
                $code   = str_replace('&gt;', '>', $code);

                if ($item->getAttribute('data-area') === 'head') {
                    $headBlocks[] = [
                        $item->outertext(),
                        $code
                    ];
                } else {
                    $codeBlocks[] = [
                        $item->outertext(),
                        $code
                    ];
                }
            }

            if ($codeBlocks) {
                $html = (string)$dom;
                foreach ($codeBlocks as $codeBlock) {
                    $html = str_replace($codeBlock[0], $codeBlock[1], $html);
                }
                $dom = DomParser::fromString($html);
            }
            if ($headBlocks) {
                $head = $dom->find('head');
                if ($head) {
                    $head = $head[0];
                    foreach ($headBlocks as $headBlock) {
                        $head->innertext = $head->innertext . "\n" . $headBlock[1] . "\n";
                    }
                }
                foreach ($dom->find('.block-script-sec[data-area="head"]') as $item) {
                    $item->outertext = '';
                }
            }
        }

        foreach($dom->find('*[data-be-component-hidden]') as $element) {
            $element->outertext = '';
        }

        $html       = (string)$this->filterBlocksEditClassesAndStyles($dom);
        // $salesforce = new Salesforce();
        // $html       = $salesforce->restoreAmpScript($html);
        $html       = str_replace('<!-- block-hide -->', '', $html);
        $html       = str_replace('<!-- end-block-hide -->', '', $html);
        $html       = str_replace('‌', '&zwnj;', $html);

        // Removes duplicate blank lines which get left behind after removing
        // components.
        $output      = '';
        $isLastBlank = false;
        $lines       = array_map('rtrim', preg_split('/\r?\n/', $html));
        foreach($lines as $line) {
            if ($line === '' && $isLastBlank) {
                continue;
            }
            /** @phpstan-ignore-next-line */
            if ($line === '' && !$isLastBlank) {
                $isLastBlank = true;
                $output .= "\n";
                continue;
            }

            $isLastBlank = false;
            $output .= $line . "\n";
        }

        return $output;
    }

    /**
     * @param simple_html_dom $dom
     *
     * @return simple_html_dom
     */
    public function filterBlocksEditClassesAndStyles(simple_html_dom $dom): simple_html_dom
    {
        $empty = $dom->find('.block-section-empty');
        if ($empty) {
            $empty[0]->outertext = '';
        }

        foreach(Attributes::$classes as $cssClass) {
            foreach($dom->find('[class=' . $cssClass . ']') as $element) {
                $newClasses = [];
                foreach($this->extractList($element->class) as $c) {
                    if ($c !== $cssClass) {
                        $newClasses[] = $c;
                    }
                }
                $element->class = join(' ', $newClasses);
            }
        }

        // Variable classes, like "block-maxchar-", have unique names
        // like "block-maxchar-250". Removing them would be easier if
        // the dom parser could use wildcards like "block-maxchar-*" but
        // it can't. So doing this the hard way.
        foreach($dom->find('[class]') as $element) {
            $newClasses = [];
            foreach($this->extractList($element->class) as $cssClass) {
                $found = false;
                foreach(Attributes::$variables as $variableClass) {
                    if (strpos($cssClass, $variableClass) === 0) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $newClasses[] = $cssClass;
                }
            }
            $element->class = join(' ', $newClasses);
        }

        foreach(Attributes::$styles as $style) {
            foreach($dom->find('[style=' . $style . ']') as $element) {
                $newStyles = [];
                foreach($this->extractList($element->style, ';') as $s) {
                    if ($s !== $style) {
                        $newStyles[] = $style;
                    }
                }
                $element->style = join(';', $newStyles);
            }
        }

        foreach(Attributes::$datas as $dataAttrib) {
            foreach($dom->find('[' . $dataAttrib . ']') as $element) {
                $element->removeAttribute($dataAttrib);
            }
        }

        // Removes data-block attribute found in comments.
        foreach($dom->find('comment') as $element) {
            if (strpos($element->outertext(), 'data-block') !== false) {
                $element->outertext = preg_replace('/data-block="([^"]+)"/', '', $element->outertext());
            }
            if (strpos($element->outertext(), 'data-variable') !== false) {
                $element->outertext = preg_replace('/data-variable="([^"]+)"/', '', $element->outertext());
            }
        }

        foreach($dom->find('*[class]') as $element) {
            if ($element->hasAttribute('class') && $element->getAttribute('class') === '') {
                $element->removeAttribute('class');
            }
        }

        return $dom;
    }

    /**
     * @param string $classes
     * @param string $sep
     *
     * @return array
     */
    protected function extractList(string $classes, string $sep = ' '): array
    {
        return array_filter(array_map('trim', explode($sep, $classes)));
    }

    /**
     * @var Imagify
     */
    protected $imagify;

    /**
     * @var Scriptify
     */
    protected $scriptify;

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @var EmailRepository
     */
    protected $emailRepository;

    /**
     * @var ImagesRepository
     */
    protected $imagesRepository;

    /**
     * @var SourcesRepository
     */
    protected $sourcesRepository;

    /**
     * @Required()
     * @param SourcesRepository $sourcesRepository
     */
    public function setSourcesRepository(SourcesRepository $sourcesRepository)
    {
    	$this->sourcesRepository = $sourcesRepository;
    }

    /**
     * @Required()
     * @param ImagesRepository $imagesRepository
     */
    public function setImagesRepository(ImagesRepository $imagesRepository)
    {
    	$this->imagesRepository = $imagesRepository;
    }

    /**
     * @Required()
     * @param Scriptify $scriptify
     */
    public function setScriptify(Scriptify $scriptify)
    {
        $this->scriptify = $scriptify;
    }

    /**
     * @Required()
     * @param Imagify $imagify
     */
    public function setImagify(Imagify $imagify)
    {
        $this->imagify = $imagify;
    }

    /**
     * @Required()
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templatesRepository = $templatesRepository;
    }

    /**
     * @Required()
     * @param EmailRepository $emailRepository
     */
    public function setEmailRepository(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }
}
