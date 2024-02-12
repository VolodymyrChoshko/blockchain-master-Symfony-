<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\NoticeSeen;
use Exception;

/**
 * Class NoticeSeenRepository
 */
class NoticeSeenRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return NoticeSeen|null
     * @throws Exception
     */
    public function findByID(int $id): ?NoticeSeen
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $uid
     *
     * @return array|NoticeSeen[]
     * @throws Exception
     */
    public function findUnclosedByUser(int $uid): array
    {
        return $this->find([
            'usr_id' => $uid,
            'is_closed' => '0'
        ]);
    }

    /**
     * @param int $nid
     * @param int $uid
     *
     * @return NoticeSeen|null
     * @throws Exception
     */
    public function findByNoticeAndUser(int $nid, int $uid): ?NoticeSeen
    {
        return $this->findOne([
            'ntc_id' => $nid,
            'usr_id' => $uid
        ]);
    }
}
