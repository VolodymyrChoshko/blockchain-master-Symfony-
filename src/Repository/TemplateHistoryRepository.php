<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Tag\TemplateHistoryTag;
use Tag\TemplateTag;
use BlocksEdit\Media\CDNInterface;
use BlocksEdit\System\Required;
use Entity\TemplateHistory;
use Exception;

/**
 * Class TemplateHistoryRepository
 */
class TemplateHistoryRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return TemplateHistory
     * @throws Exception
     */
    public function findByID(int $id): ?TemplateHistory
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $tid
     *
     * @return TemplateHistory[]|array
     * @throws Exception
     */
    public function findByTemplate(int $tid): array
    {
        return $this->find([
            'tmpId' => $tid,
        ], null, null, [
            'id' => 'desc',
        ]);
    }

    /**
     * @param int $tid
     * @param int $version
     *
     * @return TemplateHistory
     * @throws Exception
     */
    public function findByTemplateVersion(int $tid, int $version): ?TemplateHistory
    {
        return $this->findOne([
            'tmpId'   => $tid,
            'version' => $version,
        ]);
    }

    /**
     * @param int $id
     *
     * @return TemplateHistory|null
     * @throws Exception
     */
    public function findLatest(int $id): ?TemplateHistory
    {
        $stmt = $this->prepareAndExecute(
            'SELECT MAX(tmh_version) as max_version FROM `tmh_template_history` WHERE `tmh_tmp_id` = ? LIMIT 1',
            [$id]
        );
        $row = $this->fetch($stmt);
        if (!$row) {
            return null;
        }

        return $this->findByTemplateVersion($id, $row['max_version']);
    }

    /**
     * @param object|TemplateHistory $entity
     *
     * @return void
     * @throws Exception
     */
    public function insert(object $entity)
    {
        parent::insert($entity);
        $this->cache->deleteByTags([
            new TemplateTag($entity->getTmpId())
        ]);
    }

    /**
     * @param int $tid
     *
     * @return int
     * @throws Exception
     */
    public function deleteByTemplate(int $tid): int
    {
        $toRemove = [];
        foreach($this->findByTemplate($tid) as $templateHistory) {
            if ($templateHistory->getThumbNormal()) {
                $toRemove[] = $templateHistory->getThumbNormal();
            }
            if ($templateHistory->getThumb200()) {
                $toRemove[] = $templateHistory->getThumb200();
            }
            if ($templateHistory->getThumb360()) {
                $toRemove[] = $templateHistory->getThumb360();
            }
            if ($templateHistory->getThumbMobile()) {
                $toRemove[] = $templateHistory->getThumbMobile();
            }

            $this->cache->deleteByTags([
                new TemplateTag($templateHistory->getTmpId()),
                new TemplateHistoryTag($templateHistory->getId())
            ]);
        }
        if (!empty($toRemove)) {
            $this->cdn->batchRemoveByURL($toRemove);
        }

        $stmt = $this->prepareAndExecute('DELETE FROM tmh_template_history WHERE tmh_tmp_id = ?', [$tid]);

        return $stmt->rowCount();
    }

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
}
