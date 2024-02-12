<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\TemplateLinkParam;
use Exception;

/**
 * Class TemplateLinkParamRepository
 */
class TemplateLinkParamRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return TemplateLinkParam
     * @throws Exception
     */
    public function findByID(int $id): ?TemplateLinkParam
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $tid
     *
     * @return TemplateLinkParam[]
     * @throws Exception
     */
    public function findByTemplate(int $tid): array
    {
        return $this->find([
            'tmpId' => $tid
        ]);
    }
}
