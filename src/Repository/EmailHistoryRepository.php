<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Tag\EmailHistoryTag;
use Tag\EmailTag;
use BlocksEdit\Html\Imagify;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\System\Required;
use Entity\EmailHistory;
use Exception;
use Repository\Exception\CreateTemplateException;
use RuntimeException;

/**
 * Class EmailHistoryRepository
 */
class EmailHistoryRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return EmailHistory|null
     * @throws Exception
     */
    public function findByID(int $id): ?EmailHistory
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $eid
     *
     * @return EmailHistory[]|array
     * @throws Exception
     */
    public function findByEmail(int $eid): array
    {
        return $this->find([
            'emaId' => $eid,
        ], null, null, [
            'id' => 'desc',
        ]);
    }

    /**
     * @param int $eid
     * @param int $version
     *
     * @return EmailHistory|null
     * @throws Exception
     */
    public function findByEmailVersion(int $eid, int $version): ?EmailHistory
    {
        return $this->findOne([
            'emaId'   => $eid,
            'version' => $version,
        ]);
    }

    /**
     * @param int $id
     *
     * @return int
     * @throws Exception
     */
    public function findLatestVersion(int $id): int
    {
        $stmt = $this->prepareAndExecute(
            'SELECT MAX(emh_version) as max_version FROM `emh_email_history` WHERE `emh_ema_id` = ? AND emh_parent_id IS NULL LIMIT 1',
            [$id]
        );
        $row = $this->fetch($stmt);
        if (!$row) {
            return 0;
        }

        return (int)$row['max_version'];
    }

    /**
     * @param int $id
     *
     * @return EmailHistory|null
     * @throws Exception
     */
    public function findLatest(int $id): ?EmailHistory
    {
        $stmt = $this->prepareAndExecute(
            'SELECT MAX(emh_version) as max_version FROM `emh_email_history` WHERE `emh_ema_id` = ? LIMIT 1',
            [$id]
        );
        $row = $this->fetch($stmt);
        if (!$row || empty($row['max_version'])) {
            return null;
        }

        return $this->findByEmailVersion($id, $row['max_version']);
    }

    /**
     * @param int $eid
     *
     * @return int
     * @throws Exception
     */
    public function findNextVersion(int $eid): int
    {
        $stmt = $this->prepareAndExecute(
            'SELECT MAX(emh_version) as max_version FROM `emh_email_history` WHERE `emh_ema_id` = ? LIMIT 1',
            [$eid]
        );
        $row = $this->fetch($stmt);
        if (!$row) {
            return 1;
        }

        return ((int)$row['max_version']) + 1;
    }

    /**
     * @param int    $uid
     * @param int    $eid
     * @param string $html
     * @param int    $version
     *
     * @return EmailHistory
     * @throws Exception
     */
    public function save(int $uid, int $eid, string $html, int $version): ?EmailHistory
    {
        $email = $this->emailRepository->findByID($eid);
        if (!$email) {
            throw new CreateTemplateException('Email not found.');
        }

        $latestVersion = $this->findLatestVersion($eid);

        // $parentId = null;
        if ($version !== 0 && $version !== $latestVersion) {
            $parent = $this->findByEmailVersion($eid, $version);
            if (!$parent) {
                throw new RuntimeException('Parent email not found.');
            }
            // $parentId = $parent->getId();
        }

        $nextVersion = $this->findNextVersion($eid);
        $entity = (new EmailHistory())
            ->setEmaId($eid)
            ->setUsrId($uid)
            ->setHtml($html)
            ->setMessage('')
            ->setVersion($nextVersion)
            ->setParentId(null);
        $this->insert($entity);

        // $source = $this->paths->dirEmail($eid, $version);
        $dest   = $this->paths->dirEmail($eid, $nextVersion);
        $this->paths->make($dest);

        return $entity;
    }

    /**
     * @param object|EmailHistory $entity
     *
     * @return void
     * @throws Exception
     */
    public function insert(object $entity)
    {
        parent::insert($entity);
        $this->cache->deleteByTags([
            new EmailTag($entity->getEmaId())
        ]);
    }

    /**
     * @param int $eid
     *
     * @return int
     * @throws Exception
     */
    public function deleteByEmail(int $eid): int
    {
        $stmt = $this->prepareAndExecute('DELETE FROM emh_email_history WHERE emh_ema_id = ?', [$eid]);
        $this->cache->deleteByTags([
            new EmailTag($eid),
            new EmailHistoryTag($eid)
        ]);

        return $stmt->rowCount();
    }

    /**
     * @var EmailRepository
     */
    protected $emailRepository;

    /**
     * @var ImagesRepository
     */
    protected $imagesRepository;

    /**
     * @var TemplatesRepository
     */
    protected $templatesRepository;

    /**
     * @var Imagify
     */
    protected $imagify;

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
     * @param ImagesRepository $imagesRepository
     */
    public function setImagesRepository(ImagesRepository $imagesRepository)
    {
        $this->imagesRepository = $imagesRepository;
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
