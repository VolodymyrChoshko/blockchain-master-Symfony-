<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\InvoiceItem;
use Exception;

/**
 * Class InvoiceItemRepository
 */
class InvoiceItemRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return InvoiceItem|null
     * @throws Exception
     */
    public function findByID(int $id): ?InvoiceItem
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $invoiceId
     *
     * @return InvoiceItem[]
     * @throws Exception
     */
    public function findByInvoice(int $invoiceId): array
    {
        return $this->find([
            'invoiceId' => $invoiceId
        ]);
    }
}
