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
                'expires_at'    => gmdate('Y-m-d H:i:s', $expiresAt),
                // UTC from PHP's clock, the one the password change cutoff uses (CURRENT_TIMESTAMP follows the DB time zone).
                'created_at'    => gmdate('Y-m-d H:i:s'),
            ],
            ['%d', '%s', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * @param string $refreshToken
     * @return object|null
     */
    public function getByToken($refreshToken)
    {
        $sql = $this->wpdb->prepare(
            // expires_at is stored in UTC; NOW() would use the database session time zone.
            'SELECT * FROM %i WHERE refresh_token = %s AND expires_at > UTC_TIMESTAMP()',
            $this->tableName(),
            $refreshToken
        );
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        return $this->wpdb->get_row($sql);
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

        // Only one of several parallel requests can delete the row: that one owns the token.
        return $result !== false && $result > 0;
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
        $sql = $this->wpdb->prepare('DELETE FROM %i WHERE expires_at <= UTC_TIMESTAMP()', $this->tableName());
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $this->wpdb->query($sql);

        return $result !== false;
    }

    /**
     * @return bool
     */
    public function dropTable()
    {
        $escapedTable = esc_sql($this->tableName());
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $this->wpdb->query('DROP TABLE IF EXISTS `' . $escapedTable . '`');

        return $result !== false;
    }

    /**
     * @return void
     */
    public function createTable()
    {
        $charsetCollate = $this->wpdb->get_charset_collate();
        $tableName      = $this->tableName();

        $sql = "CREATE TABLE $tableName (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            refresh_token varchar(255) NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY refresh_token (refresh_token),
            KEY user_id (user_id),
            KEY expires_at (expires_at)
        ) $charsetCollate;";

        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        dbDelta($sql);
    }
}
