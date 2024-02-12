<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\EmailLinkParam;
use Exception;

/**
 * Class EmailLinkParamRepository
 */
class EmailLinkParamRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return EmailLinkParam|null
     * @throws Exception
     */
    public function findByID(int $id): ?EmailLinkParam
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $eid
     *
     * @return EmailLinkParam[]
     * @throws Exception
     */
    public function findByEmail(int $eid): array
    {
        return $this->find([
            'emaId' => $eid
        ]);
    }
}
