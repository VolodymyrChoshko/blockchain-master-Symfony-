<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\BillingLog;
use Entity\BillingPlan;
use Exception;

/**
 * Class BillingLogRepository
 */
class BillingLogRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return BillingLog|null
     * @throws Exception
     */
    public function findByID(int $id): ?BillingLog
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int|null $limit
     * @param int      $offset
     *
     * @return BillingLog[]
     * @throws Exception
     */
    public function findAll(?int $limit = null, int $offset = 0): array
    {
        if ($limit) {
            return $this->find([], $limit, $offset, ['id' => 'DESC']);
        }

        return $this->find([], null, null, ['id' => 'DESC']);
    }

    /**
     * @param int $oid
     *
     * @return BillingLog[]
     * @throws Exception
     */
    public function findByOrg(int $oid): array
    {
        return $this->find([
            'orgId' => $oid
        ], null, null, ['id' => 'DESC']);
    }

    /**
     * @param BillingPlan $billingPlan
     * @param string      $message
     *
     * @return BillingLog
     * @throws Exception
     */
    public function createLog(BillingPlan $billingPlan, string $message): BillingLog
    {
        $billingLog = (new BillingLog())
            ->setOrgId($billingPlan->getOrgId())
            ->setMessage($message);
        $this->insert($billingLog);

        return $billingLog;
    }
}
