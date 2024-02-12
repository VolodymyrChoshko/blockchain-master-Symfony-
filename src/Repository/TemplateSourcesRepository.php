<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\TemplateSource;
use Exception;

/**
 * Class TemplateSourcesRepository
 */
class TemplateSourcesRepository extends Repository
{
    /**
     * @param int $tid
     * @param int $iid
     *
     * @return bool
     * @throws Exception
     */
    public function isEnabled(int $tid, int $iid): bool
    {
        $entity = $this->findOne([
            'tmpId' => $tid,
            'srcId' => $iid
        ]);

        return !empty($entity) && $entity->isEnabled();
    }

    /**
     * @param int $id
     *
     * @return TemplateSource|null
     * @throws Exception
     */
    public function findByID(int $id): ?TemplateSource
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $tid
     * @param int $iid
     *
     * @return TemplateSource|null
     * @throws Exception
     */
    public function findByTemplateAndSource(int $tid, int $iid): ?TemplateSource
    {
        return $this->findOne([
            'tmpId' => $tid,
            'srcId' => $iid
        ]);
    }
}
