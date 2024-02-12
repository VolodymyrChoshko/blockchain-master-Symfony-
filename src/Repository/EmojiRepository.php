<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\Emoji;
use Exception;

/**
 * Class EmojiRepository
 */
class EmojiRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return Emoji|null
     * @throws Exception
     */
    public function findByID(int $id): ?Emoji
    {
        return $this->findOne([
            'id' => $id
        ]);
    }
}
