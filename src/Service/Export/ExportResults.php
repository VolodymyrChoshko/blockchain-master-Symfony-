<?php
namespace Service\Export;

use BlocksEdit\Html\HtmlData;
use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\Files;
use BlocksEdit\IO\Paths;
use BlocksEdit\Util\Strings;
use DirectoryIterator;
use Entity\Email;
use Entity\Image;
use Repository\ImagesRepository;
use ZipArchive;

/**
 * Class ExportResults
 */
class ExportResults
{
    /**
     * @var Email
     */
    protected $email;

    /**
     * @var HtmlData
     */
    protected $htmlData;

    /**
     * @var Image[]
     */
    protected $images = [];

    /**
     * @var string
     */
    protected $emailDir = '';

    /**
     * @var Files
     */
    protected $files;

    /**
     * Constructor
     *
     * @param Email    $email
     * @param HtmlData $htmlData
     * @param array    $images
     * @param string   $emailDir
     * @param Files    $files
     */
    public function __construct(Email $email, HtmlData $htmlData, array $images, string $emailDir, Files $files)
    {
        $this->email    = $email;
        $this->htmlData = $htmlData;
        $this->emailDir = $emailDir;
        $this->images   = $images;
        $this->files    = $files;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        $title = $this->htmlData->getDom()->find('title', 0);
        if ($title) {
            $title = trim($title->innertext());
        }

        return (string)$title;
    }

    /**
     * @param string $fileExtension
     *
     * @return string
     */
    public function getCleanName(string $fileExtension = ''): string
    {
        $cleanName = Strings::getSlug($this->email->getTitle());
        if ($fileExtension) {
            $cleanName .= '.' . $fileExtension;
        }

        return $cleanName;
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->htmlData->getHtml();
    }

    /**
     * @return string
     * @throws IOException
     */
    public function getZip(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');
        $zip      = new ZipArchive();
        $zip->open($tempFile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        $zip->addFromString($this->email->getLocation(), $this->getHtml());

        if (file_exists($this->emailDir)) {
            $it = new DirectoryIterator($this->emailDir);
            foreach ($it as $file) {
                if ($file->isDir()) {
                    continue;
                }
                if ($file->getExtension() === 'html' || $file->getExtension() === 'bak') {
                    continue;
                }
                $zip->addFile(Paths::combine($this->emailDir, $file), $file->getFilename());
            }
        }

        if ($this->images) {
            ImagesRepository::batchDownload($this->images);
            foreach($this->images as $image) {
                $zip->addFile($image->getTempFile(), $image->getFilename());
            }
        }

        $zip->close();
        $this->files->remove($this->images);

        return $tempFile;
    }
}
