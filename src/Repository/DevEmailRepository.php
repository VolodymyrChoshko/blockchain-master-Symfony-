<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Database\Where;
use Entity\DevEmail;
use Exception;

/**
 * Class DevEmailRepository
 */
class DevEmailRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return DevEmail|null
     * @throws Exception
     */
    public function findByID(int $id): ?DevEmail
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int|null $limit
     * @param int      $offset
     *
     * @return DevEmail[]
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
     * @return int
     * @throws Exception
     */
    public function findCount(): int
    {
        $stmt = $this->prepareAndExecute(sprintf('SELECT COUNT(*) as `c` FROM `%s`', $this->meta->getTableName()));
        $row = $this->fetch($stmt);
        if (!$row || empty($row['c'])) {
            return 0;
        }

        return (int)$row['c'];
    }

    /**
     * @param string $to
     *
     * @return int
     * @throws Exception
     */
    public function findCountByTo(string $to): int
    {
        $stmt = $this->prepareAndExecute(
            sprintf('SELECT COUNT(*) as `c` FROM `%s` WHERE `dev_to` LIKE ?', $this->meta->getTableName()),
            ['%' . $to . '%']
        );
        $row = $this->fetch($stmt);
        if (!$row || empty($row['c'])) {
            return 0;
        }

        return (int)$row['c'];
    }

    /**
     * @param string   $to
     * @param int|null $limit
     * @param int      $offset
     *
     * @return array
     * @throws Exception
     */
    public function findByTo(string $to, ?int $limit = null, int $offset = 0): array
    {
        if ($limit) {
            return $this->find([
                new Where('to', 'LIKE', '%' . $to . '%')
            ], $limit, $offset, ['id' => 'DESC']);
        }

        return $this->find([
            new Where('to', 'LIKE', '%' . $to . '%')
        ], null, null, ['id' => 'DESC']);
    }
}
