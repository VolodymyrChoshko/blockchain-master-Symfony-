<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Database\Where;
use Entity\Notice;
use Exception;

/**
 * Class NoticeRepository
 */
class NoticeRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return Notice|null
     * @throws Exception
     */
    public function findByID(int $id): ?Notice
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param string $date
     *
     * @return array|Notice[]
     * @throws Exception
     */
    public function findAfterDate(string $date): array
    {
        return $this->find([
            new Where('date_created', '>=', $date)
        ]);
    }
}
