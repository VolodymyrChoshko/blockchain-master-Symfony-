<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\Comment;
use Entity\Email;
use Exception;

/**
 * Class CommentRepository
 */
class CommentRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return Comment|null
     * @throws Exception
     */
    public function findByID(int $id): ?Comment
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param Email $email
     *
     * @return Comment[]
     * @throws Exception
     */
    public function findByEmail(Email $email): array
    {
        return $this->find([
            'email' => $email,
        ]);
    }
}
