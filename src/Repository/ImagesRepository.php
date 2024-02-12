<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Database\Where;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\System\Required;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\Each;
use Entity\Image;
use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class ImagesRepository
 */
class ImagesRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return Image|null
     * @throws Exception
     */
    public function findByID(int $id): ?Image
    {
        return $this->findOne(
            [
                'id' => $id
            ]
        );
    }

    /**
     * @param int $eid
     * @param int $version
     *
     * @return Image[]|object[]
     * @throws Exception
     */
    public function findByEmailAndVersion(int $eid, int $version): array
    {
        return $this->find(
            [
                'emaId'      => $eid,
                'emaVersion' => $version,
                'isDeleted'  => 0
            ]
        );
    }

    /**
     * @param int    $eid
     * @param int    $version
     * @param string $filename
     *
     * @return Image|null
     * @throws Exception
     */
    public function findByFilename(int $eid, int $version, string $filename): ?Image
    {
        return $this->findOne(
            [
                'emaId'      => $eid,
                'emaVersion' => $version,
                'filename'   => $filename,
                'isDeleted'  => 0
            ]
        );
    }

    /**
     * @param int $tid
     * @param int $version
     *
     * @return Image[]|object[]
     * @throws Exception
     */
    public function findByTemplateAndVersion(int $tid, int $version): array
    {
        return $this->find(
            [
                'tmpId'      => $tid,
                'tmpVersion' => $version,
                'isDeleted'  => 0
            ]
        );
    }

    /**
     * @param int $eid
     *
     * @return array|Image[]
     * @throws Exception
     */
    public function findNext(int $eid): array
    {
        return $this->find([
            'emaId'  => $eid,
            'isNext' => true
        ]);
    }

    /**
     * @return Image[]
     * @throws Exception
     */
    public function findByTempUndeleted(): array
    {
        return $this->find([
            'isTemp'    => true,
            'isDeleted' => false,
            new Where('dateCreated', '<=', 'DATE_SUB(NOW(), INTERVAL 24 HOUR)', true)
        ]);
    }

    /**
     * @return array|Image[]
     * @throws Exception
     */
    public function findByNextUnused(): array
    {
        return $this->find([
            'isNext'    => true,
            'isDeleted' => false,
            new Where('dateCreated', '<=', 'DATE_SUB(NOW(), INTERVAL 24 HOUR)', true)
        ]);
    }

    /**
     * @return Image[]
     * @throws Exception
     */
    public function findByCdnUndeleted(): array
    {
        return $this->find([
            'isHosted'     => true,
            'isDeleted'    => true,
            'isCdnDeleted' => false
        ]);
    }

    /**
     * @param int $oid
     *
     * @return int
     * @throws Exception
     */
    public function deleteByOrg(int $oid): int
    {
        $stmt = $this->prepareAndExecute('UPDATE img_images SET img_is_deleted = 1 WHERE img_org_id = ?', [$oid]);

        return $stmt->rowCount();
    }

    /**
     * @param Image[] $images
     * @param int     $version
     * @param int     $throttle
     * @param bool    $skipInsert
     *
     * @return Image[]
     * @throws Exception
     */
    public function batchCopy(array $images, int $version = 0, int $throttle = 5, bool $skipInsert = false): array
    {
        if (empty($images)) {
            return [];
        }

        $system = '';
        $sourceUrls = [];
        $targetFilenames = [];

        foreach($images as $image) {
            if (!($image instanceof Image)) {
                throw new InvalidArgumentException('Images passed to ImagesRepository::batchCopy() must be instances of Image.');
            }
            if (!$image->isDownloadable()) {
                throw new RuntimeException('Image cannot be downloaded because it is not hosted.');
            }
            if (!$system) {
                list($system) = $this->cdn->getSystemAndPathFromURL($image->getCdnUrl());
            }
            $sourceUrls[]      = $image->getCdnUrl();
            $targetFilenames[] = $image->getFilename();
        }

        $created = [];
        $urls = $this->cdn->prefixed($images[0]->getOrgId())
            ->batchCopyByURL($sourceUrls, $system, $targetFilenames, $throttle);
        foreach($urls as $i => $url) {
            $image = $images[$i];
            $emailVersion = $image->getEmaVersion();
            if ($image->getEmaId() && $version) {
                $emailVersion = $version;
            }
            $templateVersion = $image->getTmpVersion();
            if ($image->getTmpId()) {
                $templateVersion = $version;
            }
            $newImage = (new Image())
                ->setOrgId($image->getOrgId())
                ->setTmpId($image->getTmpId())
                ->setTmpVersion($templateVersion)
                ->setSrcPath($image->getSrcPath())
                ->setSrcId($image->getSrcId())
                ->setFilename($image->getFilename())
                ->setEmaId($image->getEmaId())
                ->setEmaVersion($emailVersion)
                ->setIsHosted($image->isHosted())
                ->setIsTemp($image->isTemp())
                ->setIsCdnDeleted(false)
                ->setIsDeleted(false)
                ->setIsPending(false)
                ->setCdnUrl($url);
            $created[] = $newImage;
            if (!$skipInsert) {
                $this->insert($newImage);
            }
        }

        return $created;
    }

    /**
     * @param Image[] $images
     * @param int     $throttle
     *
     * @return array
     */
    public static function batchDownload(array $images, int $throttle = 5): array
    {
        $guzzle = new Client([
            'verify'  => false,
            'timeout' => 5
        ]);

        $tempFiles = [];
        $upload    = function($images) use($guzzle, &$tempFiles) {
            foreach($images as $image) {
                if (!($image instanceof Image)) {
                    throw new InvalidArgumentException('Images passed to ImagesRepository::batchDownload() must be instances of Image.');
                }
                if (!$image->isDownloadable()) {
                    throw new RuntimeException('Image cannot be downloaded because it is not hosted.');
                }
                $tempFiles[$image->getId()] = tempnam(sys_get_temp_dir(), 'image');

                try {
                    $resp = $guzzle->getAsync($image->getCdnUrl(), ['sink' => $tempFiles[$image->getId()]]);
                    yield $resp;

                    $image->setTempFile($tempFiles[$image->getId()]);
                } catch (Exception $e) {}
            }
        };

        Each::ofLimit($upload($images), $throttle, null, function(ClientException $e, int $index) use(&$images, &$tempFiles) {
            $images[$index]->setTempFile('');
            unset($tempFiles[$index]);
        })
            ->wait();

        return array_values($tempFiles);
    }

    /**
     * @var EmailRepository
     */
    protected $emailRepository;

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @var CDNInterface
     */
    protected $cdn;

    /**
     * @Required()
     * @param CDNInterface $cdn
     */
    public function setCDN(CDNInterface $cdn)
    {
        $this->cdn = $cdn;
    }

    /**
     * @Required()
     * @param EmailRepository $emailRepository
     */
    public function setEmailRepository(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    /**
     * @Required()
     * @param TemplatesRepository $templatesRepository
     */
    public function setTemplatesRepository(TemplatesRepository $templatesRepository)
    {
        $this->templatesRepository = $templatesRepository;
    }
}
