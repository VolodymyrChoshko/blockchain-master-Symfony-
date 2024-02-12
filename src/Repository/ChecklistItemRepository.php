<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use Entity\ChecklistItem;
use Entity\Email;
use Entity\Template;
use Exception;

/**
 * Class ChecklistItemRepository
 */
class ChecklistItemRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return ChecklistItem|null
     * @throws Exception
     */
    public function findByID(int $id): ?ChecklistItem
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param Template $template
     *
     * @return ChecklistItem[]
     * @throws Exception
     */
    public function findByTemplate(Template $template): array
    {
        return $this->find([
            'template' => $template
        ]);
    }

    /**
     * @param Email $email
     *
     * @return ChecklistItem[]
     * @throws Exception
     */
    public function findByEmail(Email $email): array
    {
        return $this->find([
            'email' => $email
        ]);
    }

    /**
     * @param Template $template
     *
     * @return ChecklistItem[]
     * @throws Exception
     */
    public function findTemplates(Template $template): array
    {
        return $this->find([
            'template' => $template
        ]);
    }

    /**
     * @param Email  $email
     * @param string $key
     *
     * @return ChecklistItem|null
     * @throws Exception
     */
    public function findByKey(Email $email, string $key): ?ChecklistItem
    {
        return $this->findOne([
            'email' => $email,
            'key' => $key
        ]);
    }
}
