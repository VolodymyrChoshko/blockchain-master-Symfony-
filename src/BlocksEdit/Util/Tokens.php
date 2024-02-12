<?php
namespace BlocksEdit\Util;

/**
 * Class Tokens
 */
class Tokens
{
    const TOKEN_EXTEND_TRIAL = 'extend_trial';
    const TOKEN_PREVIEW = 'preview_token';
    const TOKEN_PUBLIC = 'original_public_url';
    const TOKEN_INVITE = 'tid_invite';
    const TOKEN_RESET_PASSWORD = 'resetpassword';

    /**
     * @param int    $id
     * @param string $scope
     *
     * @return string
     */
    public function generateToken(int $id, string $scope): string
    {
        if ($scope === self::TOKEN_PREVIEW) {
            $rid = $id . '_' . time() . '_' . rand(1, 1000);
        } else {
            $rid = $id;
        }

        return sha1(md5($rid . '_wpdb_salt)' . $scope));
    }

    /**
     * @param int    $id
     * @param string $scope
     * @param string $token
     *
     * @return bool
     */
    public function verifyToken(int $id, string $scope, string $token): bool
    {
        $verifyToken = self::generateToken($id, $scope);

        return $token === $verifyToken;
    }
}
