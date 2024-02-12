<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\PinGroup;
use Entity\Template;
use Exception;

/**
 * Class PinGroupRepository
 */
class PinGroupRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return PinGroup|null
     * @throws Exception
     */
    public function findByID(int $id): ?PinGroup
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param Template $template
     *
     * @return array
     * @throws Exception
     */
    public function findByTemplate(Template $template): array
    {
        return $this->find([
            'template' => $template,
        ]);
    }
}
