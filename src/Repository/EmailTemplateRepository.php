<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\EmailTemplate;
use Exception;

/**
 * Class EmailTemplateRepository
 */
class EmailTemplateRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return EmailTemplate|null
     * @throws Exception
     */
    public function findByID(int $id): ?EmailTemplate
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param string $name
     *
     * @return EmailTemplate|null
     * @throws Exception
     */
    public function findByName(string $name): ?EmailTemplate
    {
        return $this->findOne([
            'name' => $name
        ]);
    }

    /**
     * @param int $emaId
     *
     * @return EmailTemplate|null
     * @throws Exception
     */
    public function findByEmaId(int $emaId): ?EmailTemplate
    {
        return $this->findOne([
            'emaId' => $emaId
        ]);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function findAll(): array
    {
        return $this->find();
    }
}
