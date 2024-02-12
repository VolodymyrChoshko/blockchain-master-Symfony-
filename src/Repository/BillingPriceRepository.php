<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\BillingPrice;
use Exception;

/**
 * Class BillingPriceRepository
 */
class BillingPriceRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return BillingPrice|null
     * @throws Exception
     */
    public function findByID(int $id): ?BillingPrice
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param string $target
     *
     * @return BillingPrice|null
     * @throws Exception
     */
    public function findByTarget(string $target): ?BillingPrice
    {
        return $this->findOne([
            'target' => $target
        ]);
    }

    /**
     * @return BillingPrice[]
     * @throws Exception
     */
    public function findAll(): array
    {
        return $this->find();
    }

    /**
     * @param string $target
     *
     * @return int
     * @throws Exception
     */
    public function getAmountCents(string $target): int
    {
        $billingPrice = $this->findByTarget($target);
        if (!$billingPrice) {
            return -1;
        }

        return $billingPrice->getAmountCents();
    }
}
