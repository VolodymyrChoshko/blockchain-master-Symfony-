<?php
namespace Command;

use BlocksEdit\Command\Args;
use BlocksEdit\Command\Command;
use BlocksEdit\Command\InputInterface;
use BlocksEdit\Command\OutputInterface;
use BlocksEdit\Html\DomParser;
use BlocksEdit\IO\FilesTrait;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\Service\AsyncRequests;
use BlocksEdit\Service\ChromeServiceInterface;
use BlocksEdit\Util\Media;
use Exception;
use Gumlet\ImageResize;
use Repository\SectionLibraryRepository;
use Repository\TemplatesRepository;
use Service\LibraryThumbnailsMessageQueue;
use simplehtmldom_1_5\simple_html_dom;

/**
 * Class SectionLibraryThumbnailsCommand
 */
class SectionLibraryThumbnailsCommand extends Command
{
    use PathsTrait;
    use FilesTrait;

    static $name = 'section:library:thumbnails';

    /**
     * @var LibraryThumbnailsMessageQueue
     */
    protected $libraryThumbnailsMessageQueue;

    /**
     * @var SectionLibraryRepository
     */
    protected $sectionLibraryRepository;

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @var ChromeServiceInterface
     */
    protected $chromeService;

    /**
     * @var CDNInterface
     */
    protected $cdn;

    /**
     * Constructor
     *
     * @param LibraryThumbnailsMessageQueue $libraryThumbnailsMessageQueue
     * @param SectionLibraryRepository      $sectionLibraryRepository
     * @param TemplatesRepository           $templatesRepository
     * @param ChromeServiceInterface        $chromeService
     * @param CDNInterface                  $cdn
     */
    public function __construct(
        LibraryThumbnailsMessageQueue $libraryThumbnailsMessageQueue,
        SectionLibraryRepository $sectionLibraryRepository,
        TemplatesRepository $templatesRepository,
        ChromeServiceInterface $chromeService,
        CDNInterface $cdn
    )
    {
        $this->libraryThumbnailsMessageQueue = $libraryThumbnailsMessageQueue;
        $this->sectionLibraryRepository = $sectionLibraryRepository;
        $this->templatesRepository = $templatesRepository;
        $this->chromeService = $chromeService;
        $this->cdn = $cdn;
    }

    /**
     * {@inheritDoc}
     */
    public static function getHelp(): string
    {
        return 'Process library section thumbnails.';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Args $args, OutputInterface $output, InputInterface $input)
    {
        $desktop = $args->getOpt('desktop');
        $mobile  = $args->getOpt('mobile');
        $output->writeLine("Desktop: $desktop, Mobile: $mobile");

        $sectionDesktop = null;
        $sectionMobile  = null;

        try {
            $sectionDesktop = $this->sectionLibraryRepository->findByID($desktop);
            $sectionMobile  = $this->sectionLibraryRepository->findByID($mobile);
            if (!$sectionDesktop || !$sectionMobile) {
                $output->errorLine('Sections not found.');
                die(0);
            }

            $template = $this->templatesRepository->findByID($sectionDesktop->getTmpId(), true);
            $htmlData = $this->templatesRepository->getHtml($sectionDesktop->getTmpId());
            $dom      = $htmlData->getDom();
            $html     = $this->injectSection($dom, $sectionDesktop->getHtml());
            $tmpDir   = $this->paths->createTempDirectory();

            $asyncRequests = new AsyncRequests();
            $asyncRequests->add(
                $html,
                [
                    'width'     => 800,
                    'isLibrary' => true
                ]
            );
            $asyncRequests->add(
                $html,
                [
                    'width'     => 420,
                    'isLibrary' => true
                ]
            );

            $responses   = $this->chromeService->scrapeAsync($asyncRequests);
            $dimsDesktop = $responses[0]['sections'][0];
            $dimsMobile  = $responses[1]['sections'][0];
            $dataDesktop = base64_decode($responses[0]['screenshotSections']);
            $dataMobile  = base64_decode($responses[1]['screenshotSections']);
            $fileDesktop = $tmpDir . '/desktop.jpg';
            $fileMobile  = $tmpDir . '/mobile.jpg';

            $this->files->write($fileDesktop, $dataDesktop);
            $cropped = new ImageResize($fileDesktop);
            $cropped->freecrop(
                (int)$dimsDesktop['width'],
                (int)$dimsDesktop['height'],
                (int)$dimsDesktop['left'],
                (int)$dimsDesktop['top']
            );
            $cropped->save($fileDesktop, null, Media::JPEG_QUALITY);

            $this->files->write($fileMobile, $dataMobile);
            $cropped = new ImageResize($fileMobile);
            $cropped->freecrop(
                (int)$dimsMobile['width'],
                (int)$dimsMobile['height'],
                (int)$dimsMobile['left'],
                (int)$dimsMobile['top']
            );
            $cropped->save($fileMobile, null, Media::JPEG_QUALITY);

            $urls = $this->cdn
                ->prefixed($template->getOrganization()->getId())
                ->batchUpload(CDNInterface::SYSTEM_IMAGES, ['desktop.jpg', 'mobile.jpg'], [$fileDesktop, $fileMobile]);

            $sectionDesktop->setThumbnail($urls[0]);
            $sectionMobile->setThumbnail($urls[1]);
            $this->sectionLibraryRepository->update($sectionDesktop);
            $this->sectionLibraryRepository->update($sectionMobile);

            $this->paths->remove($tmpDir);
            $output->writeLine('Finished');
            die(0);
        } catch (Exception $e) {
            $output->errorLine($e->getMessage());
            $output->errorLine($e->getTraceAsString());
            if (isset($tmpDir)) {
                $this->paths->remove($tmpDir);
            }
            if (strpos($e->getMessage(), '.block-section') !== false) {
                $this->sectionLibraryRepository->delete($sectionDesktop);
                $this->sectionLibraryRepository->delete($sectionMobile);
                die(0);
            }

            die(1);
        }
    }

    /**
     * @param simple_html_dom $dom
     * @param string          $html
     *
     * @return string
     * @throws Exception
     */
    protected function injectSection(simple_html_dom $dom, string $html): string
    {
        $hDom = DomParser::fromString('<!doctype html><html><body id="be-body">' . $html . '</body></html>');
        $body = $hDom->find('#be-body');
        $html = $body[0]->innertext();

        $sections = $dom->find('.block-section');
        if (!$sections) {
            throw new Exception('.block-section not found.');
        }

        $firstSection = null;
        foreach($sections as $section) {
            if (!$firstSection && strpos($section->getAttribute('class'), 'be-code-edit') === false) {
                $firstSection = $section;
            } else {
                $section->outertext = '';
            }
        }

        $firstSection->outertext = $html;

        return (string)$dom;
    }
}
