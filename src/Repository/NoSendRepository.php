<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\NoSend;
use Exception;

/**
 * Class NoSendRepository
 */
class NoSendRepository extends Repository
{
    /**
     * @param string $email
     *
     * @return null|NoSend
     * @throws Exception
     */
    public function findByEmail(string $email): ?NoSend
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * @param string $email
     *
     * @return bool
     * @throws Exception
     */
    public function isSendable(string $email): bool
    {
        return !$this->findByEmail($email);
    }
}
