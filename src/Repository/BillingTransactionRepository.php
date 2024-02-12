<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\BillingTransaction;
use Exception;
use DateTime;

/**
 * Class BillingTransactionRepository
 */
class BillingTransactionRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return BillingTransaction|null
     * @throws Exception
     */
    public function findByID(int $id): ?BillingTransaction
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param DateTime|null $since
     *
     * @return int
     * @throws Exception
     */
    public function findTotalAmountCents(?DateTime $since = null): int
    {
        if ($since) {
            $stmt = $this->prepareAndExecute(
                sprintf(
                    'SELECT SUM(%s) FROM `%s` WHERE DATE(`%s`) >= ?',
                    $this->entityAccessor->prefixColumnName('amountCents'),
                    $this->meta->getTableName(),
                    $this->entityAccessor->prefixColumnName('dateCreated')
                ),
                [$since->format('Y-m-d')]
            );
        } else {
            $stmt = $this->prepareAndExecute(
                sprintf(
                    'SELECT SUM(%s) FROM `%s`',
                    $this->entityAccessor->prefixColumnName('amountCents'),
                    $this->meta->getTableName()
                )
            );
        }

        return (int)$stmt->fetchColumn();
    }
}
