<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\BillingPromo;
use Exception;

/**
 * Class BillingPromoRepository
 */
class BillingPromoRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return BillingPromo|null
     * @throws Exception
     */
    public function findByID(int $id): ?BillingPromo
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @return BillingPromo[]
     * @throws Exception
     */
    public function findAll(): array
    {
        return $this->find();
    }
}
