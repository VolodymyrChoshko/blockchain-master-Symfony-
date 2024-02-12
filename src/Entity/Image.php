<?php
namespace Entity;

use BlocksEdit\Database\Annotations as DB;
use BlocksEdit\IO\FilePathInterface;
use BlocksEdit\Util\HttpRequest;
use DateTime;
use Exception;
use RuntimeException;

/**
 * @DB\Table(
 *     "images",
 *     prefix="img_",
 *     repository="Repository\ImagesRepository",
 *     charSet="latin1"
 * )
 */
class Image implements FilePathInterface
{
    /**
     * @var int
     * @DB\Primary()
     * @DB\Column("img_id")
     */
    protected $id;

    /**
     * @var int
     * @DB\Column("img_org_id")
     */
    protected $orgId;

    /**
     * @var int
     * @DB\Column("img_ema_id")
     */
    protected $emaId;

    /**
     * @var int
     * @DB\Column("img_tmp_id")
     */
    protected $tmpId;

    /**
     * @var int
     * @DB\Column("img_ema_version")
     */
    protected $emaVersion = 0;

    /**
     * @var int
     * @DB\Column("img_tmp_version")
     */
    protected $tmpVersion = 0;

    /**
     * @var int
     * @DB\Column("img_is_temp")
     */
    protected $isTemp = 0;

    /**
     * @var int
     * @DB\Column("img_is_next")
     */
    protected $isNext = 0;

    /**
     * @var int
     * @DB\Column("img_src_id")
     */
    protected $srcId;

    /**
     * @var string
     * @DB\Column("img_filename")
     */
    protected $filename;

    /**
     * @var string
     * @DB\Column("img_src_path")
     */
    protected $srcPath;

    /**
     * @var string
     * @DB\Column("img_src_url")
     */
    protected $srcUrl;

    /**
     * @var string
     * @DB\Column("img_cdn_url")
     */
    protected $cdnUrl;

    /**
     * @var int
     * @DB\Column("img_is_pending")
     */
    protected $isPending = 0;

    /**
     * @var int
     * @DB\Column("img_is_deleted")
     */
    protected $isDeleted = 0;

    /**
     * @var int
     * @DB\Column("img_is_cdn_deleted")
     */
    protected $isCdnDeleted = 0;

    /**
     * @var int
     * @DB\Column("img_is_hosted")
     */
    protected $isHosted = 0;

    /**
     * @var DateTime
     * @DB\Column("img_date_created")
     */
    protected $dateCreated;

    /**
     * @var string
     */
    protected $tempFile = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Image
     */
    public function setId(int $id): Image
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrgId(): int
    {
        return $this->orgId;
    }

    /**
     * @param int $orgId
     *
     * @return Image
     */
    public function setOrgId(int $orgId): Image
    {
        $this->orgId = $orgId;

        return $this;
    }

    /**
     * @return int
     */
    public function getEmaId(): int
    {
        return (int)$this->emaId;
    }

    /**
     * @param int|null $emaId
     *
     * @return Image
     */
    public function setEmaId(?int $emaId): Image
    {
        $this->emaId = $emaId;

        return $this;
    }

    /**
     * @return int
     */
    public function getTmpId(): ?int
    {
        return (int)$this->tmpId;
    }

    /**
     * @param int|null $tmpId
     *
     * @return Image
     */
    public function setTmpId(?int $tmpId): Image
    {
        $this->tmpId = $tmpId;

        return $this;
    }

    /**
     * @return int
     */
    public function getEmaVersion(): int
    {
        return (int)$this->emaVersion;
    }

    /**
     * @param int $emaVersion
     *
     * @return Image
     */
    public function setEmaVersion(int $emaVersion): Image
    {
        $this->emaVersion = $emaVersion;

        return $this;
    }

    /**
     * @return int
     */
    public function getTmpVersion(): int
    {
        return (int)$this->tmpVersion;
    }

    /**
     * @param int $tmpVersion
     *
     * @return Image
     */
    public function setTmpVersion(int $tmpVersion): Image
    {
        $this->tmpVersion = $tmpVersion;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTemp(): bool
    {
        return (bool)$this->isTemp;
    }

    /**
     * @param bool $isTemp
     *
     * @return Image
     */
    public function setIsTemp(bool $isTemp): Image
    {
        $this->isTemp = (int)$isTemp;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNext(): bool
    {
        return (bool)$this->isNext;
    }

    /**
     * @param bool $isNext
     *
     * @return $this
     */
    public function setIsNext(bool $isNext): Image
    {
        $this->isNext = (int)$isNext;

        return $this;
    }

    /**
     * @return int
     */
    public function getSrcId(): int
    {
        return (int)$this->srcId;
    }

    /**
     * @param int $srcId
     *
     * @return Image
     */
    public function setSrcId(int $srcId): Image
    {
        $this->srcId = $srcId;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     *
     * @return Image
     */
    public function setFilename(string $filename): Image
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return string
     */
    public function getSrcPath(): string
    {
        return (string)$this->srcPath;
    }

    /**
     * @param string $srcPath
     *
     * @return Image
     */
    public function setSrcPath(string $srcPath): Image
    {
        $this->srcPath = $srcPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getSrcUrl(): string
    {
        return (string)$this->srcUrl;
    }

    /**
     * @param string $srcUrl
     *
     * @return Image
     */
    public function setSrcUrl(string $srcUrl): Image
    {
        $this->srcUrl = $srcUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getCdnUrl(): string
    {
        return (string)$this->cdnUrl;
    }

    /**
     * @param string $cdnUrl
     *
     * @return Image
     */
    public function setCdnUrl(string $cdnUrl): Image
    {
        $this->cdnUrl = $cdnUrl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return (bool)$this->isPending;
    }

    /**
     * @param bool $isPending
     *
     * @return Image
     */
    public function setIsPending(bool $isPending): Image
    {
        $this->isPending = (int)$isPending;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return (bool)$this->isDeleted;
    }

    /**
     * @param bool $isDeleted
     *
     * @return Image
     */
    public function setIsDeleted(bool $isDeleted): Image
    {
        $this->isDeleted = (int)$isDeleted;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCdnDeleted(): bool
    {
        return (bool)$this->isCdnDeleted;
    }

    /**
     * @param bool $isCdnDeleted
     *
     * @return Image
     */
    public function setIsCdnDeleted(bool $isCdnDeleted): Image
    {
        $this->isCdnDeleted = (int)$isCdnDeleted;

        return $this;
    }

    /**
     * @param bool $isHosted
     *
     * @return $this
     */
    public function setIsHosted(bool $isHosted): Image
    {
        $this->isHosted = (int)$isHosted;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHosted(): bool
    {
        return (bool)$this->isHosted;
    }

    /**
     * @return DateTime
     */
    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param DateTime $dateCreated
     *
     * @return Image
     * @throws Exception
     */
    public function setDateCreated(DateTime $dateCreated): Image
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return string
     */
    public function getTempFile(): string
    {
        return $this->tempFile;
    }

    /**
     * @param string $tempFile
     *
     * @return Image
     */
    public function setTempFile(string $tempFile): Image
    {
        $this->tempFile = $tempFile;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDownloadable(): bool
    {
        return ($this->isHosted && substr($this->getCdnUrl(), 0, 4) === 'http');
    }

    /**
     * @param bool $toTempFile
     *
     * @return string
     */
    public function download(bool $toTempFile = false): string
    {
        if (!$this->isDownloadable()) {
            throw new RuntimeException('Image cannot be downloaded because it is not hosted.');
        }
        if ($toTempFile) {
            $this->setTempFile(tempnam(sys_get_temp_dir(), 'image'));
            (new HttpRequest())->get($this->getCdnUrl(), $this->getTempFile());

            return $this->getTempFile();
        }

        return (new HttpRequest())->get($this->getCdnUrl());
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->tempFile;
    }
}
