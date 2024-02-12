<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\CreditCard;
use Exception;

/**
 * Class CreditCardRepository
 */
class CreditCardRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return CreditCard|null
     * @throws Exception
     */
    public function findByID(int $id): ?CreditCard
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $oid
     *
     * @return CreditCard[]
     * @throws Exception
     */
    public function findByOrg(int $oid): array
    {
        return $this->find([
            'orgId' => $oid
        ]);
    }

    /**
     * @param int $oid
     *
     * @return CreditCard|null
     * @throws Exception
     */
    public function findActiveCard(int $oid): ?CreditCard
    {
        return $this->findOne([
            'orgId'    => $oid,
            'isActive' => 1
        ]);
    }
}
