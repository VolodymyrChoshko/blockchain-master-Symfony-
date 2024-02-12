<?php
namespace BlocksEdit\Http;

use PDO;
use PDOStatement;
use SessionHandlerInterface;

/**
 * Class SessionHandler
 */
class SessionHandler implements SessionHandlerInterface
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var PDOStatement|null
     */
    protected $stmtRead;

    /**
     * @var PDOStatement|null
     */
    protected $stmtWrite;

    /**
     * @var PDOStatement|null
     */
    protected $stmtDestroy;

    /**
     * @var PDOStatement|null
     */
    protected $stmtGc;

    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritDoc}
     */
    public function open($path, $name): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy($id): bool
    {
        if (!$this->stmtDestroy) {
            $this->stmtDestroy = $this->pdo->prepare(
                'DELETE FROM `sessions` WHERE `id` = ?'
            );
        }

        $this->stmtDestroy->execute([$id]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function gc($max_lifetime)
    {
        if (!$this->stmtGc) {
            $this->stmtGc = $this->pdo->prepare(
                'DELETE FROM `sessions` WHERE `access` < ?'
            );
        }

        $this->stmtGc->execute([time() - $max_lifetime]);

        return $this->stmtGc->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function read($id)
    {
        if (!$this->stmtRead) {
            $this->stmtRead = $this->pdo->prepare(
                'SELECT `data` FROM `sessions` WHERE id = ?'
            );
        }

        $this->stmtRead->execute([$id]);
        $row = $this->stmtRead->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row['data'];
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function write($id, $data): bool
    {
        if (!$this->stmtWrite) {
            $this->stmtWrite = $this->pdo->prepare(
                'REPLACE INTO `sessions` VALUES (?, ?, ?)'
            );
        }

        $this->stmtWrite->execute([$id, time(), $data]);

        return true;
    }
}
