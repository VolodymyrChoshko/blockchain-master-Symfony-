<?php
namespace BlocksEdit\Util;

/**
 * Class UploadExtract
 */
class UploadExtract
{
    /**
     * @var string
     */
    protected $tempDir;

    /**
     * @var string
     */
    protected $htmlFile;

    /**
     * @var string
     */
    protected $baseName;

    /**
     * @var bool
     */
    protected $isZip = false;

    /**
     * Constructor
     *
     * @param string $tempDir
     * @param string $htmlFile
     * @param string $baseName
     * @param bool   $isZip
     */
    public function __construct(string $tempDir, string $htmlFile, string $baseName, bool $isZip)
    {
        $this->tempDir  = $tempDir;
        $this->htmlFile = $htmlFile;
        $this->baseName = $baseName;
        $this->isZip    = $isZip;
    }

    /**
     * @return string
     */
    public function getTempDir(): string
    {
        return $this->tempDir;
    }

    /**
     * @return string
     */
    public function getHtmlFile(): string
    {
        return $this->htmlFile;
    }

    /**
     * @return string
     */
    public function getBaseName(): string
    {
        return $this->baseName;
    }

    /**
     * @return bool
     */
    public function isZip(): bool
    {
        return $this->isZip;
    }
}
