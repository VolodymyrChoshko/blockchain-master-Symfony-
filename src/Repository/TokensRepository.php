<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Util\TokensTrait;
use Exception;
use RuntimeException;

/**
 * Class TokensRepository
 */
class TokensRepository extends Repository
{
    use TokensTrait;

    /**
     * @param int    $uid
     * @param string $scope
     *
     * @return string
     * @throws Exception
     */
    public function addForUser(int $uid, string $scope): string
    {
        $token = $this->tokens->generateToken($uid, $scope);
        $this->prepareAndExecute(
            "DELETE FROM utk_tokens WHERE utk_usr_id = ? AND utk_scope = ? LIMIT 1",
            [$uid, $scope]
        );
        $this->prepareAndExecute(
            "DELETE FROM utk_tokens WHERE utk_token = ? AND utk_scope = ? LIMIT 1",
            [$token, $scope]
        );

        $stmt  = $this->prepareAndExecute('INSERT INTO utk_tokens (utk_usr_id, utk_token, utk_created_at, utk_scope) VALUES(?, ?, ?, ?)', [
            $uid,
            $token,
            time(),
            $scope
        ]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Failed to create user token.');
        }

        return $token;
    }

    /**
     * @param string $token
     * @param string $scope
     *
     * @return int
     * @throws Exception
     */
    public function findUserIDFromToken(string $token, string $scope): int
    {
        $stmt = $this->prepareAndExecute('SELECT utk_usr_id FROM utk_tokens WHERE utk_token = ? AND utk_scope = ?', [
            $token,
            $scope
        ]);
        if (0 == $stmt->rowCount()) {
            return 0;
        }

        return (int)$this->fetch($stmt)['utk_usr_id'];
    }

    /**
     * @param string $token
     * @param string $scope
     *
     * @return array
     * @throws Exception
     */
    public function findByTokenAndScope(string $token, string $scope): array
    {
        $stmt = $this->prepareAndExecute('SELECT * FROM utk_tokens WHERE utk_token = ? AND utk_scope = ? LIMIT 1', [
            $token,
            $scope
        ]);

        return $this->fetch($stmt);
    }

    /**
     * @param int    $uid
     * @param string $token
     *
     * @return int
     * @throws Exception
     */
    public function deleteByUser(int $uid, string $token): int
    {
        $stmt = $this->prepareAndExecute('DELETE FROM utk_tokens WHERE utk_usr_id = ? AND utk_token = ?', [
            $uid,
            $token
        ]);

        return $stmt->rowCount();
    }
}
