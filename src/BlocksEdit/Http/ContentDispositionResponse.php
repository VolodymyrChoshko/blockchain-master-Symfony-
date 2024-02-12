<?php
namespace BlocksEdit\Http;

/**
 * Class ContentDispositionResponse
 */
class ContentDispositionResponse extends Response
{
    /**
     * @var string
     */
    protected $localFile;

    /**
     * @var bool
     */
    protected $deleteAfter = false;

    /**
     * @var bool
     */
    protected $isData = false;

    /**
     * Constructor
     *
     * @param string   $localFile
     * @param string   $contentType
     * @param string   $fileName
     * @param int|null $statusCode
     * @param array    $headers
     * @param bool     $deleteAfter
     * @param bool     $isData
     */
    public function __construct(
        string $localFile,
        string $contentType,
        string $fileName,
        ?int $statusCode = StatusCodes::OK,
        array $headers = [],
        bool $deleteAfter = false,
        bool $isData = false
    )
    {
        $this->localFile                = $localFile;
        $this->deleteAfter              = $deleteAfter;
        $this->isData                   = $isData;

        if (!$isData) {
            clearstatcache(false, $localFile);
        }
        $length = (!$isData) ? filesize($localFile) : strlen($localFile);
        $headers['Content-Type']        = $contentType;
        $headers['Content-Length']      = $length;
        $headers['Content-Disposition'] = sprintf('attachment; filename="%s"', $fileName);

        parent::__construct('', $statusCode ?: StatusCodes::OK, $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function send()
    {
        $this->sendHeaders();
        if ($this->isData) {
            echo $this->localFile;
        } else {
            readfile($this->localFile);
            if ($this->deleteAfter) {
                unlink($this->localFile);
            }
        }

        return true;
    }
}
