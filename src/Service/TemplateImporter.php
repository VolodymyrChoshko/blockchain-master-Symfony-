<?php
namespace Service;

use BlocksEdit\Html\DomParser;
use BlocksEdit\Html\Imagify;
use BlocksEdit\Html\Scriptify;
use BlocksEdit\Html\StylesParser;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\FilesTrait;
use BlocksEdit\IO\Paths;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\Service\AsyncRequests;
use BlocksEdit\Service\ChromeServiceInterface;
use BlocksEdit\Logging\LoggerTrait;
use BlocksEdit\Util\Media;
use BlocksEdit\Util\UploadExtractor;
use Repository\Exception\CreateTemplateException;
use Repository\SectionsRepository;
use Repository\TemplateHistoryRepository;
use Repository\TemplatesRepository;
use Repository\ComponentsRepository;
use Entity\TemplateHistory;
use Gumlet\ImageResize;
use Exception;
use Sabberworm\CSS\Parsing\SourceException;
use simplehtmldom_1_5\simple_html_dom;

/**
 * Class TemplateImporter
 */
class TemplateImporter
{
    use PathsTrait;
    use FilesTrait;
    use LoggerTrait;

    const WIDTH_DESKTOP = 800;
    const WIDTH_MOBILE  = 420;

    /**
     * @var ChromeServiceInterface
     */
    protected $chromeService;

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepo;

    /**
     * @var ComponentsRepository
     */
    protected $componentsRepo;

    /**
     * @var SectionsRepository
     */
    protected $sectionsRepo;

    /**
     * @var TemplateHistoryRepository
     */
    protected $templateHistoryRepository;

    /**
     * @var UploadExtractor
     */
    protected $uploadExtractor;

    /**
     * @var Scriptify
     */
    protected $scriptify;

    /**
     * @var Imagify
     */
    protected $imagify;

    /**
     * @var UploadingStatus
     */
    protected $uploadingStatus;

    /**
     * @var CDNInterface
     */
    protected $cdn;

    /**
     * Constructor
     *
     * @param Scriptify                 $scriptify
     * @param CDNInterface              $cdn
     * @param Imagify                   $imagify
     * @param UploadingStatus           $uploadingStatus
     * @param ChromeServiceInterface    $chromeService
     * @param TemplatesRepository       $templatesRepo
     * @param ComponentsRepository      $componentsRepo
     * @param SectionsRepository        $sectionsRepo
     * @param TemplateHistoryRepository $templateHistoryRepository
     * @param UploadExtractor           $uploadExtractor
     */
    public function __construct(
        Scriptify $scriptify,
        CDNInterface $cdn,
        Imagify $imagify,
        UploadingStatus $uploadingStatus,
        ChromeServiceInterface $chromeService,
        TemplatesRepository $templatesRepo,
        ComponentsRepository $componentsRepo,
        SectionsRepository $sectionsRepo,
        TemplateHistoryRepository $templateHistoryRepository,
        UploadExtractor $uploadExtractor
    )
    {
        $this->cdn                       = $cdn;
        $this->imagify                   = $imagify;
        $this->scriptify                 = $scriptify;
        $this->uploadingStatus           = $uploadingStatus;
        $this->chromeService             = $chromeService;
        $this->templatesRepo             = $templatesRepo;
        $this->componentsRepo            = $componentsRepo;
        $this->sectionsRepo              = $sectionsRepo;
        $this->uploadExtractor           = $uploadExtractor;
        $this->templateHistoryRepository = $templateHistoryRepository;
    }

    /**
     * @param int    $uid
     * @param int    $oid
     * @param array  $file
     * @param string $uuid
     *
     * @return array
     * @throws CreateTemplateException
     * @throws IOException
     */
    public function createTemplate(int $uid, int $oid, array $file, string $uuid): array
    {
        return $this->create($uid, $oid, 0, $file, 0, '', $uuid);
    }

    /**
     * @param int    $uid
     * @param int    $oid
     * @param int    $tid
     * @param array  $file
     * @param string $uuid
     *
     * @return array
     * @throws CreateTemplateException
     * @throws IOException
     */
    public function createNewTemplateVersion(int $uid, int $oid, int $tid, array $file, string $uuid): array
    {
        return $this->create($uid, $oid, $tid, $file, 0, '', $uuid);
    }

    /**
     * @param int    $uid
     * @param int    $oid
     * @param int    $pid
     * @param array  $file
     * @param string $name
     * @param string $uuid
     *
     * @return array
     * @throws CreateTemplateException
     * @throws IOException
     */
    public function createLayout(int $uid, int $oid, int $pid, array $file, string $name, string $uuid): array
    {
        return $this->create($uid, $oid, 0, $file, $pid, $name, $uuid);
    }

    /**
     * @param int    $uid
     * @param int    $oid
     * @param int    $pid
     * @param array  $file
     * @param string $uuid
     *
     * @return array
     * @throws CreateTemplateException
     * @throws IOException
     */
    public function createNewLayout(int $uid, int $oid, int $pid, array $file, string $uuid): array
    {
        return $this->create($uid, $oid, 0, $file, $pid, '', $uuid);
    }

    /**
     * @param int    $uid
     * @param int    $oid
     * @param int    $tid
     * @param array  $file
     * @param int    $pid
     * @param string $name
     * @param string $uuid
     *
     * @return array
     * @throws CreateTemplateException
     * @throws IOException
     * @throws Exception
     */
    protected function create(int $uid, int $oid, int $tid, array $file, int $pid, string $name, string $uuid): array
    {
        set_time_limit(60 * 2);
        $extract = $this->uploadExtractor->extract($file);
        if ($extract->isZip()) {
            $this->uploadingStatus->update($uuid, 'Unzipping template.', 10);
        } else {
            $this->uploadingStatus->update($uuid, 'Parsing template.', 10);
        }

        try {
            $dom = DomParser::fromFile($extract->getHtmlFile());
            if (!$pid) {
                $dom = $this->onboadTemplate($dom);
            }
            $this->files->write($extract->getHtmlFile(), (string)$dom);

            if ($pid) {
                if (!$name) {
                    $templateHistory = $this->templatesRepo->createNewVersion($uid, $oid, $pid, $extract, true);
                } else {
                    $templateHistory = $this->templatesRepo->create($uid, $oid, $extract, $name, $pid, true);
                }
            } else {
                if ($tid) {
                    $templateHistory = $this->templatesRepo->createNewVersion($uid, $oid, $tid, $extract);
                } else {
                    $templateHistory = $this->templatesRepo->create($uid, $oid, $extract);
                }
            }

            $htmlData = $this->templatesRepo->getHtml($templateHistory->getTmpId());
            $this->generateScreenshots(
                $oid,
                $templateHistory,
                $templateHistory->getTmpId(),
                $templateHistory->getVersion(),
                $htmlData->getHtml(),
                $pid,
                $uuid
            );

            $dom = $this->removeComponents($htmlData->getDom());
            $dom = $this->fixLocalImages($dom);
            $templateHistory->setHtml((string)$dom);
            $this->templateHistoryRepository->update($templateHistory);

            $this->uploadingStatus->update($uuid, 'Done.', 100, $templateHistory->getTmpId(), [
                'version'           => $templateHistory->getVersion(),
                'screenshotDesktop' => $templateHistory->getThumbNormal(),
                'screenshotMobile'  => $templateHistory->getThumbMobile()
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->uploadingStatus->update($uuid, 'Failed.', 100);
            throw $e;
        } finally {
            $this->uploadExtractor->cleanUp($extract);
        }

        return [];
    }

    /**
     * @param int             $oid
     * @param TemplateHistory $templateHistory
     * @param int             $id
     * @param int             $version
     * @param string          $html
     * @param int             $pid
     * @param string          $uuid
     *
     * @return void
     * @throws Exception
     */
    protected function generateScreenshots(
        int $oid,
        TemplateHistory $templateHistory,
        int $id,
        int $version,
        string $html,
        int $pid,
        string $uuid
    )
    {
        $this->uploadingStatus->update($uuid, 'Generating thumbnails.', 15);
        $asyncRequests = new AsyncRequests();
        $asyncRequests->add($html, [
            'width'    => self::WIDTH_DESKTOP,
            'isLayout' => $pid !== 0,
            'uuid'     => $uuid
        ]);
        $asyncRequests->add($html, [
            'width'    => self::WIDTH_MOBILE,
            'isLayout' => $pid !== 0
        ]);
        $resp = $this->chromeService->scrapeAsync($asyncRequests);
        $this->uploadingStatus->update($uuid, 'Saving thumbnails.', 80);

        $screenshots = [
            Paths::SCREENSHOT        => $this->saveScreenshot($id, $version, $resp[0]['screenshot'], Paths::SCREENSHOT, $pid),
            Paths::SCREENSHOT_200    => $this->saveScreenshot($id, $version, $resp[0]['screenshot'], Paths::SCREENSHOT_200, $pid),
            Paths::SCREENSHOT_360    => $this->saveScreenshot($id, $version, $resp[0]['screenshot'], Paths::SCREENSHOT_360, $pid),
            Paths::SCREENSHOT_MOBILE => $this->saveScreenshot($id, $version, $resp[1]['screenshot'], Paths::SCREENSHOT_MOBILE, $pid)
        ];
        if (!$pid) {
            $screenshots += $this->saveAreaScreenshots($id, $version, $resp[0]['components'], 'components', false);
            $screenshots += $this->saveAreaScreenshots($id, $version, $resp[0]['sections'], 'sections', false);
            $screenshots += $this->saveAreaScreenshots($id, $version, $resp[1]['components'], 'components', true);
            $screenshots += $this->saveAreaScreenshots($id, $version, $resp[1]['sections'], 'sections', true);
        }

        $localFiles = [];
        $filenames  = [];
        foreach($screenshots as $filename => $screenshot) {
            $filenames[]  = $filename;
            $localFiles[] = $screenshot;
        }

        $this->uploadingStatus->update($uuid, 'Finalizing.', 90);
        $urls = $this->cdn->prefixed($oid)->batchUpload(CDNInterface::SYSTEM_SCREENSHOTS, $filenames, $localFiles);

        $templateHistory->setThumbNormal(array_shift($urls));
        $templateHistory->setThumb200(array_shift($urls));
        $templateHistory->setThumb360(array_shift($urls));
        $templateHistory->setThumbMobile(array_shift($urls));
        $this->templateHistoryRepository->update($templateHistory);

        foreach($urls as $url) {
            preg_match('/(sections|components)-(\d+)\.jpg$/', $url, $matches);
            $type = $matches[1];
            $id   = (int)$matches[2];
            if ($type === 'sections') {
                $this->sectionsRepo->updateScreenshot($id, $url);
            } else {
                $this->componentsRepo->updateScreenshot($id, $url);
            }
        }
    }

    /**
     * @param int    $id
     * @param int    $version
     * @param string $screenshot
     * @param string $filename
     * @param int    $pid
     *
     * @return string
     * @throws IOException
     * @throws Exception
     */
    protected function saveScreenshot(
        int $id,
        int $version,
        string $screenshot,
        string $filename,
        int $pid = 0
    ): string
    {
        $screenshotFile = $pid
            ? $this->paths->dirLayoutScreenshot($pid, $id, $filename, $version)
            : $this->paths->dirTemplateScreenshot($id, $filename, $version);
        if (file_exists($screenshotFile)) {
            $this->files->remove($screenshotFile);
        }
        $this->files->write($screenshotFile, base64_decode($screenshot));

        if ($filename === Paths::SCREENSHOT_200) {
            $cropped = new ImageResize($screenshotFile);
            $cropped->resizeToWidth(200, true);
            $cropped->save($screenshotFile, null, Media::JPEG_QUALITY);
        } else if ($filename === Paths::SCREENSHOT_360) {
            $resizer = new ImageResize($screenshotFile);
            $resizer->crop(360, 360, true, ImageResize::CROPTOP);
            $resizer->save($screenshotFile, null, Media::JPEG_QUALITY);
        }

        return $screenshotFile;
    }

    /**
     * @param int    $tid
     * @param int    $version
     * @param array  $areas
     * @param string $type
     * @param bool   $isMobile
     *
     * @return array
     * @throws Exception
     */
    protected function saveAreaScreenshots(
        int $tid,
        int $version,
        array $areas,
        string $type,
        bool $isMobile
    ): array
    {
        $files = [];
        foreach($areas as $i => $area) {
            if (empty($area['thumb'])) {
                continue;
            }
            $nr            = $i + 1;
            $area['style'] = !empty($area['style']) ? $area['style'] : '';
            $area['block'] = !empty($area['block']) ? $area['block'] : '';
            $area['title'] = !empty($area['title']) ? $area['title'] : '';
            if ($type === 'components') {
                $id = $this->componentsRepo->create(
                    $nr,
                    $area['html'],
                    $area['style'],
                    $area['block'],
                    $area['title'],
                    $tid,
                    $isMobile,
                    $version
                );
            } else {
                $id = $this->sectionsRepo->create(
                    $nr,
                    $area['html'],
                    $area['style'],
                    $area['block'],
                    $area['title'],
                    $tid,
                    $isMobile,
                    $version
                );
            }

            if ($type === 'components') {
                $file = $this->paths->dirComponentScreenshot($id, $isMobile, $version);
            } else {
                $file = $this->paths->dirSectionScreenshot($id, $isMobile, $version);
            }
            $this->files->write($file, base64_decode($area['thumb']));
            $files[$type . '-' . $id . '.jpg'] = $file;
        }

        return $files;
    }

    /**
     * @param simple_html_dom $dom
     *
     * @return simple_html_dom
     */
    protected function fixLocalImages(simple_html_dom $dom): simple_html_dom
    {
        foreach($dom->find('img') as $element) {
            $src = $element->getAttribute('src');
            if ($this->imagify->isLocalImage($src)) {
                $src = str_replace('./', '', $src);
                $element->setAttribute('src', 'https://local-images-should-not-be-used.com/' . $src);
            }
        }

        return $dom;
    }

    /**
     * @param simple_html_dom $dom
     *
     * @return simple_html_dom
     */
    protected function removeComponents(simple_html_dom $dom): simple_html_dom
    {
        foreach ($dom->find('.block-component') as $element) {
            if ($element->getAttribute('data-be-keep')) {
                continue;
            }
            $style = $element->getAttribute('style');
            if (!$style) {
                $style = '';
            }
            $element->setAttribute('orig-style', $style);
            $style .= ';display: none;';
            $element->setAttribute('style', $style);
            $element->setAttribute('data-be-component-hidden', 'true');
        }

        return $dom;
    }

    /**
     * @param simple_html_dom $dom
     *
     * @return simple_html_dom
     * @throws SourceException
     */
    protected function onboadTemplate(simple_html_dom $dom): simple_html_dom
    {
        (new StylesParser())->inlineStylesheetBEStyles($dom);
        $dom = $this->scriptify->hideScriptTags($dom);
        foreach($dom->find('[data-be-hosted]') as $item) {
            $item->removeAttribute('data-be-hosted');
            $item->removeAttribute('data-be-img-id');
        }

        return $dom;
    }
}
