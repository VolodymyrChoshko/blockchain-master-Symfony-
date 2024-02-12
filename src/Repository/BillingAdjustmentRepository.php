<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Database\Where;
use BlocksEdit\Database\WhereOr;
use Entity\BillingAdjustment;
use Exception;

/**
 * Class BillingAdjustmentRepository
 */
class BillingAdjustmentRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return BillingAdjustment|null
     * @throws Exception
     */
    public function findByID(int $id): ?BillingAdjustment
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $oid
     *
     * @return array
     * @throws Exception
     */
    public function findByOrg(int $oid): array
    {
        return $this->find([
            'orgId' => $oid
        ], null, null, ['id' => 'DESC']);
    }

    /**
     * @param int $oid
     *
     * @return array
     * @throws Exception
     */
    public function findUnAppliedByOrg(int $oid): array
    {
        $whereOr = new WhereOr([
            BillingAdjustment::STATUS_PENDING,
            BillingAdjustment::STATUS_APPLIED_PARTIALLY
        ]);
        return $this->find([
            'orgId'  => $oid,
            new Where('status', '=', $whereOr)
        ], null, null, ['id' => 'DESC']);
    }
}
