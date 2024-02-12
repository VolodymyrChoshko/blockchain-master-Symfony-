<?php
namespace BlocksEdit\Html;

use BlocksEdit\Integrations\FilesystemIntegrationInterface;
use BlocksEdit\Integrations\IntegrationInterface;
use BlocksEdit\Integrations\Services\SalesForceIntegration;
use BlocksEdit\IO\FilesTrait;
use BlocksEdit\IO\Paths;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\Config\Config;
use Entity\Image;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Repository\SourcesRepository;
use Service\DisplayService;
use Repository\ImagesRepository;
use Wa72\Url\Url;
use simplehtmldom_1_5\simple_html_dom;
use simplehtmldom_1_5\simple_html_dom_node;

/**
 * Class Imagify
 */
class Imagify
{
    use LoggerAwareTrait;
    use PathsTrait;
    use FilesTrait;

    const SWAP_RELATIVE = 'relative';
    const SWAP_MANUAL = 'manual';
    const SWAP_SOURCE = 'source';

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CDNInterface
     */
    protected $cdn;

    /**
     * @var ImagesRepository
     */
    protected $imagesRepository;

    /**
     * @var DisplayService
     */
    protected $displayService;

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
     * @param Config           $config
     * @param CDNInterface     $cdn
     * @param ImagesRepository $imagesRepository
     * @param LoggerInterface  $logger
     */
    public function __construct(
        Config $config,
        CDNInterface $cdn,
        ImagesRepository $imagesRepository,
        LoggerInterface $logger
    )
    {
        $this->uri              = $config->uri;
        $this->config           = $config;
        $this->cdn              = $cdn;
        $this->imagesRepository = $imagesRepository;
        $this->setLogger($logger);
    }

    /**
     * @param int $uid
     * @param int $oid
     *
     * @return void
     */
    public function setAuth(int $uid, int $oid)
    {
        $this->uid = $uid;
        $this->oid = $oid;
    }

    /**
     * @param DisplayService $displayService
     *
     * @return void
     */
    public function setDisplayService(DisplayService $displayService)
    {
        $this->displayService = $displayService;
    }

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
     * Returns the IDs of found images.
     *
     * @param simple_html_dom $dom
     * @param int             $id
     * @param int             $oid
     * @param int             $version
     * @param string          $wdir
     * @param bool            $isEmail
     *
     * @return int[]
     * @throws Exception
     */
    public function upgradeHostedImages(
        simple_html_dom $dom,
        int $id,
        int $oid,
        int $version,
        string $wdir = '',
        bool $isEmail = false
    ): array
    {
        $foundIDs = [];
        $nextIDs  = [];
        if ($isEmail) {
            foreach($this->imagesRepository->findNext($id) as $image) {
                $image
                    ->setEmaVersion($version)
                    ->setIsNext(false);
                $this->imagesRepository->update($image);
                $nextIDs[]  = $image->getId();
                $foundIDs[] = $image->getId();
            }
        }

        $toCopyImages   = [];
        $toCopyItems    = [];
        $toUploadBases  = [];
        $toUploadItems  = [];
        $toUploadLocals = [];
        foreach($dom->find('img,.block-background') as $item) {
            $src = $this->getAnySource($item);
            if (
                !$src
                || $item->getAttribute('data-be-custom-src')
                || (substr($src, 0, 4) === 'http' && !$item->getAttribute('data-be-hosted'))
            ) {
                continue;
            }

            $image = $this->getNodeImage($item);
            if ($image && $image->isDownloadable()) {
                if (!in_array($image->getId(), $nextIDs)) {
                    $toCopyImages[] = $image;
                    $toCopyItems[]  = $item;
                }
                continue;
            }

            $basename = pathinfo($src, PATHINFO_BASENAME);
            $filename = '';
            if ($this->isImagifyUrl($src)) {
                $filename = $this->displayService->getEmailImageFile($src);
                if (!$filename) {
                    continue;
                }
            } else if ($this->isTmpUpload($src)) {
                $filename = Paths::combine($this->config->dirs['tmpUploads'], $basename);
                if (!file_exists($filename)) {
                    continue;
                }
            } else if ($wdir) {
                $filename = Paths::combine($wdir, str_replace('./', '', $src));
                if (!file_exists($filename)) {
                    continue;
                }
            }

            if ($filename && $basename) {
                $toUploadLocals[] = $filename;
                $toUploadBases[]  = $basename;
                $toUploadItems[]  = $item;
            }
        }

        if ($toCopyImages) {
            $newImages = $this->imagesRepository->batchCopy($toCopyImages, $version);
            foreach($toCopyImages as $i => $oldImage) {
                $toCopyItems[$i]->setAttribute('data-be-hosted', '1');
                $toCopyItems[$i]->setAttribute('data-be-img-id', $newImages[$i]->getId());
                $this->setAnySource($dom, $toCopyItems[$i], $newImages[$i]->getCdnUrl());
                $foundIDs[] = $newImages[$i]->getId();
                if ($isEmail) {
                    $newImages[$i]->setEmaId($id)
                        ->setEmaVersion($version);
                    $this->imagesRepository->update($newImages[$i]);
                }
            }
        }

        if ($toUploadBases) {
            $urls = $this->cdn->prefixed($oid)
                ->batchUpload(CDNInterface::SYSTEM_IMAGES, $toUploadBases, $toUploadLocals);
            foreach($urls as $i => $url) {
                $image = (new Image())
                    ->setOrgId($oid)
                    ->setIsHosted(true)
                    ->setCdnUrl($url)
                    ->setFilename($toUploadBases[$i]);
                if ($isEmail) {
                    $image
                        ->setEmaId($id)
                        ->setEmaVersion($version);
                } else if ($id) {
                    $image
                        ->setTmpId($id)
                        ->setTmpVersion($version);
                } else {
                    $image->setIsTemp(true);
                }
                $this->imagesRepository->insert($image);
                $toUploadItems[$i]->setAttribute('data-be-hosted', '1');
                $toUploadItems[$i]->setAttribute('data-be-img-id', $image->getId());
                $this->setAnySource($dom, $toUploadItems[$i], $image->getCdnUrl());
                $foundIDs[] = $image->getId();
            }
        }

        $this->files->remove($toCopyImages);
        $this->files->remove($toUploadLocals);

        return $foundIDs;
    }

    /**
     * Returns the IDs of found images.
     *
     * @param simple_html_dom $dom
     * @param int             $id
     * @param int             $oid
     * @param int             $version
     * @param string          $wdir
     * @param bool            $isEmail
     *
     * @return int[]
     * @throws Exception
     */
    public function upgradeHostedImages2(
        simple_html_dom $dom,
        int $id,
        int $oid,
        int $version,
        string $wdir = '',
        bool $isEmail = false
    ): array
    {
        $foundIDs = [];
        $nextIDs  = [];
        /*if ($isEmail) {
            foreach($this->imagesRepository->findNext($id) as $image) {
                $image
                    ->setEmaVersion($version)
                    ->setIsNext(false);
                $this->imagesRepository->update($image);
                $nextIDs[]  = $image->getId();
                $foundIDs[] = $image->getId();
            }
        }*/

        $toCopyImages   = [];
        $toCopyItems    = [];
        $toUploadBases  = [];
        $toUploadItems  = [];
        $toUploadLocals = [];
        foreach($dom->find('img,.block-background') as $item) {
            $src = $this->getAnySource($item);
            if (
                !$src
                || $item->getAttribute('data-be-custom-src')
                || (substr($src, 0, 4) === 'http' && !$this->isImagifyUrl($src))
            ) {
                continue;
            }

            $image = $this->getNodeImage($item);
            if ($image && $image->isDownloadable()) {
                if (!in_array($image->getId(), $nextIDs)) {
                    $toCopyImages[] = $image;
                    $toCopyItems[]  = $item;
                }
                continue;
            }

            $basename = pathinfo($src, PATHINFO_BASENAME);
            $filename = '';
            if ($this->isImagifyUrl($src)) {
                $filename = $this->displayService->getEmailImageFile2($src);
                if (!$filename) {
                    continue;
                }
            } else if ($this->isTmpUpload($src)) {
                $filename = Paths::combine($this->config->dirs['tmpUploads'], $basename);
                if (!file_exists($filename)) {
                    continue;
                }
            } else if ($wdir) {
                $filename = Paths::combine($wdir, str_replace('./', '', $src));
                if (!file_exists($filename)) {
                    continue;
                }
            }

            if ($filename && $basename) {
                $toUploadLocals[] = $filename;
                $toUploadBases[]  = $basename;
                $toUploadItems[]  = $item;
            }
        }

        if ($toCopyImages) {
            /*$newImages = $this->imagesRepository->batchCopy($toCopyImages, $version);
            foreach($toCopyImages as $i => $oldImage) {
                $toCopyItems[$i]->setAttribute('data-be-hosted', '1');
                $toCopyItems[$i]->setAttribute('data-be-img-id', $newImages[$i]->getId());
                $this->setAnySource($dom, $toCopyItems[$i], $newImages[$i]->getCdnUrl());
                $foundIDs[] = $newImages[$i]->getId();
            }*/
        }

        if ($toUploadBases) {
            $urls = $this->cdn->prefixed($oid)
                ->batchUpload(CDNInterface::SYSTEM_IMAGES, $toUploadBases, $toUploadLocals);
            dump($urls);
            foreach($urls as $i => $url) {
                $image = (new Image())
                    ->setOrgId($oid)
                    ->setIsHosted(true)
                    ->setCdnUrl($url)
                    ->setFilename($toUploadBases[$i]);
                if ($isEmail) {
                    $image
                        ->setEmaId($id)
                        ->setEmaVersion($version);
                } else if ($id) {
                    $image
                        ->setTmpId($id)
                        ->setTmpVersion($version);
                } else {
                    $image->setIsTemp(true);
                }
                $this->imagesRepository->insert($image);
                $toUploadItems[$i]->setAttribute('data-be-hosted', '1');
                $toUploadItems[$i]->setAttribute('data-be-img-id', $image->getId());
                $this->setAnySource($dom, $toUploadItems[$i], $image->getCdnUrl());
                $foundIDs[] = $image->getId();
            }
        }

        // $this->files->remove($toCopyImages);
        // $this->files->remove($toUploadLocals);

        return $foundIDs;
    }

    /**
     * @param simple_html_dom           $dom
     * @param string                    $type
     * @param string                    $imageBase
     * @param IntegrationInterface|null $integration
     *
     * @return Image[]
     * @throws Exception
     * @throws GuzzleException
     */
    public function swapImages(
        simple_html_dom $dom,
        string $type,
        string $imageBase = '',
        ?IntegrationInterface $integration = null
    ): array
    {
        if ($imageBase) {
            // $imageBase = Url::parse($imageBase)->getHost();
        }

        $swappedImages = [];
        foreach($dom->find('[data-be-img-id]') as $item) {
            $image = $this->getNodeImage($item);
            if ($image && $image->isHosted() && strpos($image->getCdnUrl(), 'assets.blocksedit.com') !== false) {
                switch($type) {
                    case self::SWAP_RELATIVE:
                        $this->setAnySource($dom, $item, $image->getFilename());
                        break;
                    case self::SWAP_MANUAL:
                        $url = Url::parse($imageBase);
                        $url->appendPathSegment($image->getFilename());
                        $this->setAnySource($dom, $item, $url->write());
                        break;
                    case self::SWAP_SOURCE:
                        $check = $integration;
                        if ($image->getSrcId()) {
                            $check = $this->getIntegration($image->getSrcId());
                        }

                        if ($image->getSrcUrl()) {
                            $this->setAnySource($dom, $item, $image->getSrcUrl());
                        } else if ($check instanceof FilesystemIntegrationInterface && $image->getSrcPath()) {
                            if ($url = $check->getFileURL($image->getSrcPath())) {
                                $this->setAnySource($dom, $item, $url);
                            }
                        } else {
                            $url = Url::parse($imageBase);
                            $url->appendPathSegment($image->getFilename());
                            $url->setPath('/' . $image->getFilename());
                            $this->setAnySource($dom, $item, $url->write());
                        }
                        break;
                }

                $swappedImages[] = $image;
            }
        }

        return $swappedImages;
    }

    /**
     * @param simple_html_dom $dom
     * @param int             $version
     *
     * @return simple_html_dom
     */
    public function addVersion(simple_html_dom $dom, int $version): simple_html_dom
    {
        foreach($dom->find('img') as $image) {
            $src = $image->src;
            if ($this->isImagifyUrl($src)) {
                $src = $this->removeQueryParams($src, ['version']);
                $sep = strpos($src, '?') === false ? '?' : '&';
                $image->setAttribute('src', $src . $sep . 'version=' . $version);
            }
        }

        foreach($dom->find('*[background]') as $image) {
            $src = $image->getAttribute('background');
            if ($this->isImagifyUrl($src)) {
                $src = $this->removeQueryParams($src, ['version']);
                $sep = strpos($src, '?') === false ? '?' : '&';
                $image->setAttribute('background', $src . $sep . 'version=' . $version);
            }
        }

        return $dom;
    }

    /**
     * @param simple_html_dom $dom
     * @param array           $imageUrls
     *
     * @return simple_html_dom
     * @throws Exception
     */
    public function replaceOriginals(simple_html_dom $dom, array $imageUrls): simple_html_dom
    {
        foreach($imageUrls as $filename => $url) {
            foreach($dom->find(sprintf('*[original-bg="%s"]', $filename)) as $el) {
                $link = $el->getAttribute('original-bg-link');
                if ($link) {
                    if (strpos($url, '/') === 0 && strpos($url, '/imagify') !== 0) {
                        $url = $link;
                    }
                    $el->removeAttribute('original-bg');
                    $el->removeAttribute('original-bg-link');
                    $el->setAttribute('background', $url);

                    $el->outertext = preg_replace('/https:\/\/([\d]+)\.app\.blocksedit\.com/', '', $el->outertext);
                    $el->innertext = preg_replace('/https:\/\/([\d]+)\.app\.blocksedit\.com/', '', $el->innertext);
                    $el->innertext = str_replace($link, $url, $el->innertext);
                    $el->outertext = str_replace($link, $url, $el->outertext);
                    $el->outertext = str_replace('"' . $url . '"', $url, $el->outertext); // Hack! Sorry!
                } else if ($el->getAttribute('background') !== false) {
                    $el->setAttribute('background', $url);
                    $blockName = $el->getAttribute('data-block');

                    // We need to find the matching <v:fill /> tag and replace the src attribute. It's
                    // inside of a comment, making this a bit complicated.
                    $this->traverse($dom, function($childNode) use($url, $blockName) {
                        if ($childNode->tag === 'comment' && stripos($childNode->innertext(), '<v:fill') !== false) {
                            $comment = $childNode->innertext();
                            if (strpos($comment, $blockName) !== false) {
                                preg_match('/<v:fill[^>]*>/', $comment, $matches);

                                if ($matches) {
                                    // SimpleXMLElement chokes on the v:fill tag.
                                    $x       = new \SimpleXMLElement(str_replace('v:fill', 'be-fill', $matches[0]));
                                    $attribs = $x[0]->attributes();
                                    if (((string)$attribs['data-block']) === $blockName) {
                                        $attribs['src']       = $url;
                                        $xml                  = str_replace("<?xml version=\"1.0\"?>\n", '', $x->asXML());
                                        $xml                  = str_replace('be-fill', 'v:fill', $xml);
                                        $childNode->innertext = str_replace($matches[0], $xml, $comment);
                                    }
                                }
                            }
                        }
                    });
                }
            }

            /** @phpstan-ignore-next-line */
            $dom = DomParser::fromString($dom->outertext);
            foreach($dom->find(sprintf('img[src="%s"]', $filename)) as $image) {
                $image->setAttribute('src', $url);
                $image->removeAttribute('original');
            }
        }

        foreach($dom->find('*[data-be-hosted="1"]') as $img) {
            $src   = '';
            $image = $this->getNodeImage($img);
            if ($image) {
                $src = $image->getFilename();
            }
            if (!$src) {
                $src = pathinfo($this->getAnySource($img), PATHINFO_BASENAME);
            }
            if (isset($imageUrls[$src])) {
                if ($img->nodeName() === 'meta') {
                    $img->setAttribute('content', $imageUrls[$src]);
                } else {
                    $img->setAttribute('src', $imageUrls[$src]);
                }
                $img->removeAttribute('data-be-hosted');
            }
        }

        foreach($dom->find('img') as $img) {
            if ($this->isImagifyUrl($img->getAttribute('src'))) {
                $path = parse_url($img->getAttribute('src'), PHP_URL_PATH);
                $src  = pathinfo($path, PATHINFO_BASENAME);
                if (isset($imageUrls[$src])) {
                    $img->setAttribute('src', $imageUrls[$src]);
                }
            }
        }

        return $dom;

        // Why was I doing this?
        // return DomParser::fromString($dom->innertext);
    }

    /**
     * @param simple_html_dom|simple_html_dom_node $dom
     * @param callable        $fn
     */
    protected function traverse($dom, callable $fn)
    {
        foreach($dom->childNodes() as $childNode) {
            $fn($childNode);
            $this->traverse($childNode, $fn);
        }
    }

    /**
     * @param simple_html_dom $dom
     *
     * @return Image[]
     * @throws Exception
     */
    public function findHostedImages(simple_html_dom $dom): array
    {
        $images = [];
        foreach($dom->find('*[data-be-hosted="1"]') as $img) {
            $image = $this->getNodeImage($img);
            if ($image) {
                $images[] = $image;
            }
        }

        return $images;
    }

    /**
     * @param simple_html_dom $dom
     * @param bool            $baseName
     *
     * @return array
     * @throws Exception
     */
    public function findHosted(simple_html_dom $dom, bool $baseName = true): array
    {
        $hosted = [];
        foreach($dom->find('*[data-be-hosted="1"]') as $img) {
            $src = $this->getAnySource($img);
            if (!in_array($src, $hosted)) {
                $src = $this->getAnySource($img);
                if ($baseName) {
                    $src   = '';
                    $image = $this->getNodeImage($img);
                    if ($image) {
                        $src = $image->getFilename();
                    }
                    if (!$src) {
                        $path = parse_url($src, PHP_URL_PATH);
                        $src  = pathinfo($path, PATHINFO_BASENAME);
                    }
                }
                if (!in_array($src, $hosted)) {
                    $hosted[] = $src;
                }
            }
        }

        return $hosted;
    }

    /**
     * @param simple_html_dom $dom
     * @param string          $replacement
     *
     * @return simple_html_dom
     */
    public function replaceHostedImagesWithRelative(simple_html_dom $dom, string $replacement = './'): simple_html_dom
    {
        foreach($dom->find('*[data-be-hosted="1"]') as $img) {
            $src  = $this->getAnySource($img);
            $path = parse_url($src, PHP_URL_PATH);
            $src  = pathinfo($path, PATHINFO_BASENAME);
            $img->setAttribute('src', $replacement . $src);
        }

        return $dom;
    }

    /**
     * @param string $src
     *
     * @return string[]
     */
    public function getImagifyParts(string $src): array
    {
        if (!preg_match('~/imagify/([^/]+)/(.*)$~', $src, $matches)) {
            return ['', ''];
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * @param string $src
     *
     * @return bool
     */
    public function isImagifyUrl(string $src): bool
    {
        if (!$src) {
            return false;
        }
        if (substr($src, 0, 4) === 'http') {
            $src = parse_url($src, PHP_URL_PATH);
        }

        return strpos($src, '/imagify/') === 0;
    }

    /**
     * @param string $src
     *
     * @return bool
     */
    public function isTmpUpload(string $src): bool
    {
        if (!$src) {
            return false;
        }
        if (substr($src, 0, 4) === 'http') {
            $src = parse_url($src, PHP_URL_PATH);
        }

        return strpos($src, '/tmp-uploads/') === 0;
    }

    /**
     * @param string $src
     *
     * @return bool
     */
    public function isLocalImage(string $src): bool
    {
        return $src && ((strpos($src, './') === 0 || strpos($src, '/') === false)) && strpos($src, '{{') !== 0;
    }

    /**
     * @param simple_html_dom_node $node
     *
     * @return int
     */
    public function getNodeImageID(simple_html_dom_node $node): int
    {
        return (int)$node->getAttribute('data-be-img-id');
    }

    /**
     * @param simple_html_dom_node $node
     *
     * @return Image|null
     * @throws Exception
     */
    public function getNodeImage(simple_html_dom_node $node): ?Image
    {
        $imgID = $this->getNodeImageID($node);
        if ($imgID) {
            return $this->imagesRepository->findByID($imgID);
        }

        return null;
    }

    /**
     * @param simple_html_dom      $dom
     * @param simple_html_dom_node $node
     * @param string               $src
     *
     * @return simple_html_dom_node
     * @throws Exception
     */
    protected function setAnySource(simple_html_dom $dom, simple_html_dom_node $node, string $src): simple_html_dom_node
    {
        if ($node->nodeName() === 'meta') {
            $node->setAttribute('content', $src);
        } else if ($node->nodeName() !== 'img') {
            $this->setBackgroundSource($dom, $node, $src);
        } else {
            $node->setAttribute('src', $src);
        }

        return $node;
    }

    /**
     * @param simple_html_dom_node $node
     *
     * @return string
     */
    protected function getAnySource(simple_html_dom_node $node): string
    {
        if ($node->nodeName() === 'img') {
            $src = $node->getAttribute('src');
        } else if ($node->nodeName() === 'meta') {
            $src = $node->getAttribute('content');
        } else {
            $src = $this->getBackgroundStyleSource($node);
        }

        return $src;
    }

    /**
     * @param simple_html_dom      $dom
     * @param simple_html_dom_node $node
     * @param string               $src
     *
     * @return void
     * @throws Exception
     */
    public function setBackgroundSource(simple_html_dom $dom, simple_html_dom_node $node, string $src)
    {
        if ($this->getBackgroundStyleSource($node)) {
            $this->setBackgroundStyleSource($node, $src);
        }
        if ($node->getAttribute('background')) {
            $node->setAttribute('background', $src);
        }

        // The node may have a child with a background attribute as well as a background style
        // attribute which need to be set.
        $childBackground = $node->find('[background]', 0);
        if ($childBackground) {
            $childBackground->setAttribute('background', $src);
            $this->setBackgroundStyleSource($childBackground, $src);
        }

        // There may also be a <v:fill> tag with the source which needs to be changed.
        // The <v:fill> is embedded in a html comment.
        $attr      = 'data-block';
        $blockName = $node->getAttribute('data-block');
        if (!$blockName) {
            $attr      = 'data-variable';
            $blockName = $node->getAttribute('data-variable');
            if (!$blockName) {
                return;
            }
        }

        $this->traverse($dom, function($childNode) use($src, $attr, $blockName) {
            if ($childNode->tag === 'comment' && stripos($childNode->innertext(), '<v:fill') !== false) {
                $comment = $childNode->innertext();
                if (strpos($comment, sprintf('%s="%s"', $attr, $blockName)) !== false) {
                    preg_match('/<v:fill[^>]*>/', $comment, $matches);

                    if ($matches) {
                        // SimpleXMLElement chokes on the v:fill tag.
                        $x       = new \SimpleXMLElement(str_replace('v:fill', 'be-fill', $matches[0]));
                        $attribs = $x[0]->attributes();
                        if (((string)$attribs[$attr]) === $blockName) {
                            /** @phpstan-ignore-next-line */
                            $attribs['src']       = $src;
                            $xml                  = str_replace("<?xml version=\"1.0\"?>\n", '', $x->asXML());
                            $xml                  = str_replace('be-fill', 'v:fill', $xml);
                            $childNode->innertext = str_replace($matches[0], $xml, $comment);
                        }
                    }
                }
            }
        });
    }

    /**
     * @param simple_html_dom_node $node
     *
     * @return string
     */
    public function getBackgroundStyleSource(simple_html_dom_node $node): string
    {
        $style = $node->getAttribute('style');
        if ($style) {
            if (preg_match('/background(-image)?\s*:.*?url\(([^)]+)\)/', $style, $matches)) {
                return str_replace('&quot;', '', trim($matches[2], '"\''));
            }
            if (preg_match('/list-style\s*:.*?url\(([^)]+)\)/', $style, $matches)) {
                return str_replace('&quot;', '', trim($matches[1], '"\''));
            }
        }

        return '';
    }

    /**
     * @param simple_html_dom_node $node
     * @param string               $src
     *
     * @return simple_html_dom_node
     */
    public function setBackgroundStyleSource(simple_html_dom_node $node, string $src): simple_html_dom_node
    {
        $style = $node->getAttribute('style');
        if (!$style) {
            return $node;
        }

        $style = str_replace('&quot;', "'", $style);
        $style = preg_replace_callback('/background(-image)?(\s*:.*?)url\((\'|")?([^)\'"]+)(\'|")?\)/', function($matches) use ($src) {
            return sprintf('background%s%surl(%s%s%s)', $matches[1], $matches[2], $matches[3], $src, $matches[5] ?? '');
        }, $style);
        $style = preg_replace_callback('/list-style(\s*:.*?)url\((\'|")?([^)\'"]+)(\'|")?\)/', function($matches) use ($src) {
            return sprintf('list-style%surl(%s%s%s)', $matches[1], $matches[2], $src, $matches[4] ?? '');
        }, $style);
        $node->setAttribute('style', $style);

        return $node;
    }

    /**
     * @param string $src
     * @param string[] $params
     *
     * @return string
     */
    protected function removeQueryParams(string $src, array $params): string
    {
        $parts = parse_url($src);
        $query = $this->getQueryParams($src);
        foreach($params as $param) {
            if (isset($query[$param])) {
                unset($query[$param]);
            }
        }
        $path = $parts['path'];
        if (!empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        return $path;
    }

    /**
     * @param string $src
     * @param string $param
     *
     * @return string
     */
    protected function getQueryParam(string $src, string $param): string
    {
        $query = $this->getQueryParams($src);
        if (isset($query[$param])) {
            return $query[$param];
        }

        return '';
    }

    /**
     * @param string $src
     *
     * @return array
     */
    protected function getQueryParams(string $src): array
    {
        $parts = parse_url(str_replace('&amp;', '&', $src));
        parse_str($parts['query'] ?? '', $query);

        return $query;
    }

    /**
     * @param int $sourceID
     *
     * @return IntegrationInterface|null
     * @throws Exception
     */
    protected function getIntegration(int $sourceID): ?IntegrationInterface
    {
        $source = $this->sourcesRepository->findByID($sourceID);
        if ($source) {
            $integration = $this->sourcesRepository->integrationFactory(
                $source,
                $this->uid,
                $this->oid
            );
            if ($integration instanceof SalesForceIntegration) {
                $integration->clearLocalCache();
            }

            return $integration;
        }

        return null;
    }
}
