<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\Comment;
use Entity\Mention;
use Exception;

/**
 * Class MentionRepository
 */
class MentionRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return Mention|null
     * @throws Exception
     */
    public function findByID(int $id): ?Mention
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param string $uuid
     *
     * @return Mention|null
     * @throws Exception
     */
    public function findByUUID(string $uuid): ?Mention
    {
        return $this->findOne([
            'uuid' => $uuid
        ]);
    }

    /**
     * @param Comment $comment
     *
     * @return Mention[]
     * @throws Exception
     */
    public function findByComment(Comment $comment): array
    {
        return $this->find([
            'comment' => $comment,
        ]);
    }
}
