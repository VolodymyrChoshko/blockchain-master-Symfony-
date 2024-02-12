<?php
namespace BlocksEdit\Util;

use BlocksEdit\IO\Exception\IOException;
use BlocksEdit\IO\Files;
use BlocksEdit\IO\Paths;
use DirectoryIterator;
use Repository\Exception\CreateTemplateException;
use RuntimeException;
use ZipArchive;

/**
 * Class UploadExtractor
 */
class UploadExtractor
{
    const MAX_UPLOAD_SIZE = 52428800;
    const ALLOWED_MIME_TYPES = ['application/zip', 'text/html'];
    const ALLOWED_EXTENSIONS = ['html', 'htm', 'jpg', 'png', 'bmp', 'gif', 'svg'];

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var Files
     */
    protected $files;

    /**
     * Constructor
     *
     * @param Paths $paths
     * @param Files $files
     */
    public function __construct(Paths $paths, Files $files)
    {
        $this->paths = $paths;
        $this->files = $files;
    }

    /**
     * @param array $file
     *
     * @return UploadExtract
     * @throws CreateTemplateException
     * @throws IOException
     */
    public function extract(array $file): UploadExtract
    {
        $pathParts = pathinfo($file['name']);
        if ($file['type'] === 'application/octet-stream' && strtolower($pathParts['extension']) === 'zip') {
            $file['type'] = 'application/zip';
        }
        if (!in_array($file['type'], self::ALLOWED_MIME_TYPES)) {
            throw new CreateTemplateException('File type not allowed!');
        }
        if ($file['size'] > self::MAX_UPLOAD_SIZE) {
            throw new CreateTemplateException('Your file is too big. It needs to be less than 50 megabytes.');
        }

        $isZip = false;
        $temp = $this->paths->createTempDirectory();
        if ($file['type'] === 'application/zip') {
            $isZip = true;
            $zip = new ZipArchive();
            if (!$zip->open($file['tmp_name'])) {
                throw new RuntimeException("Failed to unzip $file[tmp_name].");
            }
            $zip->extractTo($temp);
            $zip->close();

            $htmlFile = $this->checkFiles($temp);
            $name     = $this->cleanName($htmlFile);
        } else {
            $name     = $this->cleanName($pathParts['basename']);
            $htmlFile = tempnam(sys_get_temp_dir(), 'upload');
            if (isset($file['be_tmp_name'])) {
                $this->files->copy($file['be_tmp_name'], $htmlFile);
            } else {
                $this->files->moveUploaded($file['tmp_name'], $htmlFile);
            }
        }

        return new UploadExtract($temp, $htmlFile, $name, $isZip);
    }

    /**
     * @param UploadExtract $extract
     *
     * @return void
     * @throws IOException
     */
    public function cleanUp(UploadExtract $extract)
    {
        $this->paths->remove($extract->getTempDir());
        $this->files->remove($extract->getHtmlFile());
    }

    /**
     * @param string $src
     *
     * @return string
     * @throws IOException|CreateTemplateException
     */
    protected function checkFiles(string $src): string
    {
        $foundHtmlFile = '';
        $it = new DirectoryIterator($src);
        foreach($it as $file) {
            if ($file->isDir()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
                $this->files->remove($src . '/' . $file);
                continue;
            }
            if (!$foundHtmlFile && ($extension === 'html' || $extension === 'htm')) {
                $foundHtmlFile = Paths::combine($src, $file);
            }
        }

        if (!$foundHtmlFile) {
            throw new CreateTemplateException('HTML file not found in zip archive.');
        }

        return $foundHtmlFile;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function cleanName(string $name): string
    {
        $name = pathinfo($name, PATHINFO_BASENAME);
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['html', 'htm'])) {
            if ('htm' === $ext) {
                $name = str_replace(['.htm', '.HTM'], '.html', $name);
            }

            return preg_replace('/[^\s\w\d_.-]/i', '', $name);
        } else {
            return $name;
        }
    }
}
