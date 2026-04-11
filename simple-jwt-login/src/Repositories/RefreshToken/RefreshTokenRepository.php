<?php

namespace SimpleJWTLogin\Repositories\RefreshToken;

class RefreshTokenRepository implements Repository
{
    const TABLE_SUFFIX = 'simple_jwt_login_refresh_tokens';

    /**
     * @var \wpdb
     */
    private $wpdb;

    /**
     * @param \wpdb $wpdb
     */
    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * @return string
     */
    private function tableName()
    {
        return $this->wpdb->prefix . self::TABLE_SUFFIX;
    }

    /**
     * @param int    $userId
     * @param string $refreshToken
     * @param int    $expiresAt
     * @return bool
     */
    public function insert($userId, $refreshToken, $expiresAt)
    {
        $result = $this->wpdb->insert(
            $this->tableName(),
            [
                'user_id'       => $userId,
                'refresh_token' => $refreshToken,
                'expires_at'    => date('Y-m-d H:i:s', $expiresAt),
            ],
            ['%d', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * @param string $refreshToken
     * @return object|null
     */
    public function getByToken($refreshToken)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tableName()} WHERE refresh_token = %s AND expires_at > NOW()",
                $refreshToken
            )
        );
    }

    /**
     * @param string $refreshToken
     * @return bool
     */
    public function deleteByToken($refreshToken)
    {
        $result = $this->wpdb->delete(
            $this->tableName(),
            ['refresh_token' => $refreshToken],
            ['%s']
        );

        return $result !== false;
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function deleteByUserId($userId)
    {
        $result = $this->wpdb->delete(
            $this->tableName(),
            ['user_id' => $userId],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * @return bool
     */
    public function cleanupExpired()
    {
        $result = $this->wpdb->query(
            "DELETE FROM {$this->tableName()} WHERE expires_at <= NOW()"
        );

        return $result !== false;
    }
}
