<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Database\Where;
use Entity\LogRecord;
use Exception;

/**
 * Class LogRecordRepository
 */
class LogRecordRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return LogRecord|null
     * @throws Exception
     */
    public function findByID(int $id): ?LogRecord
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @param int $level
     * @param int $limit
     * @param int $offset
     *
     * @return LogRecord[]
     * @throws Exception
     */
    public function findByLevelOrGreater(int $level, int $limit = 100, int $offset = 0): array
    {
        return $this->find(
            [
                new Where('level', '>=', $level)
            ],
            $limit,
            $offset,
            ['id' => 'DESC'],
            'SQL_CALC_FOUND_ROWS *'
        );
    }

    /**
     * @param int $level
     * @param int $limit
     * @param int $offset
     *
     * @return LogRecord[]
     * @throws Exception
     */
    public function findByLevel(int $level, int $limit = 100, int $offset = 0): array
    {
        return $this->find(
            [
                'level' => $level
            ],
            $limit,
            $offset,
            ['id' => 'DESC'],
            'SQL_CALC_FOUND_ROWS *'
        );
    }

    /**
     * @param int|null $limit
     * @param int      $offset
     *
     * @return LogRecord[]
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
     * @param string $message
     * @param int    $limit
     * @param int    $offset
     *
     * @return LogRecord[]
     * @throws Exception
     */
    public function findByMessage(string $message, int $limit = 50, int $offset = 0): array
    {
        return $this->find(
            [
                new Where('message', 'LIKE', '%' . $message . '%')
            ],
            $limit,
            $offset,
            ['id' => 'DESC'],
            'SQL_CALC_FOUND_ROWS *'
        );
    }

    /**
     * @param string $message
     * @param int    $level
     * @param int    $limit
     * @param int    $offset
     *
     * @return LogRecord[]
     * @throws Exception
     */
    public function findByMessageAndLevel(string $message, int $level, int $limit = 50, int $offset = 0): array
    {
        return $this->find(
            [
                new Where('message', 'LIKE', '%' . $message . '%'),
                new Where('level', '=', $level)
            ],
            $limit,
            $offset,
            ['id' => 'DESC'],
            'SQL_CALC_FOUND_ROWS *'
        );
    }

    /**
     * @return int
     * @throws Exception
     */
    public function findFoundRows(): int
    {
        $stmt = $this->prepareAndExecute('SELECT FOUND_ROWS()');

        return (int)$stmt->fetchColumn();
    }
}
