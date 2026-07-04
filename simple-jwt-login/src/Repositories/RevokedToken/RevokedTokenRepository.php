<?php

namespace SimpleJWTLogin\Repositories\RevokedToken;

class RevokedTokenRepository implements Repository
{
    const TABLE_SUFFIX = 'simple_jwt_login_revoked_tokens';

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
     * @param int         $userId
     * @param string      $tokenHash
     * @param string|null $expiresAt
     * @return bool
     */
    public function insert($userId, $tokenHash, $expiresAt)
    {
        $result = $this->wpdb->insert(
            $this->tableName(),
            [
                'user_id'    => $userId,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
            ],
            ['%d', '%s', '%s']
        );

        return $result !== false;
    }

    /**
     * @param int    $userId
     * @param string $tokenHash
     * @return bool
     */
    public function existsForUser($userId, $tokenHash)
    {
        $sql = $this->wpdb->prepare(
            'SELECT 1 FROM %i WHERE user_id = %d AND token_hash = %s LIMIT 1',
            $this->tableName(),
            $userId,
            $tokenHash
        );
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        return $this->wpdb->get_var($sql) !== null;
    }

    /**
     * @param int $revokedTokenId
     * @return bool
     */
    public function existsById($revokedTokenId)
    {
        $sql = $this->wpdb->prepare(
            'SELECT 1 FROM %i WHERE id = %d LIMIT 1',
            $this->tableName(),
            $revokedTokenId
        );
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        return $this->wpdb->get_var($sql) !== null;
    }

    /**
     * @param int $revokedTokenId
     * @return bool
     */
    public function deleteById($revokedTokenId)
    {
        $result = $this->wpdb->delete(
            $this->tableName(),
            ['id' => $revokedTokenId],
            ['%d']
        );

        return $result > 0;
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
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function findAll($page, $perPage)
    {
        $offset = ($page - 1) * $perPage;

        //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
        $total = (int) $this->wpdb->get_var($this->wpdb->prepare('SELECT COUNT(*) FROM %i', $this->tableName()));

        $sql = $this->wpdb->prepare(
            'SELECT id, user_id, token_hash, expires_at, revoked_at
             FROM %i
             ORDER BY revoked_at DESC
             LIMIT %d OFFSET %d',
            $this->tableName(),
            $perPage,
            $offset
        );
        //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
        $items = $this->wpdb->get_results($sql);

        return [
            'items' => $items !== null ? $items : [],
            'total' => $total,
        ];
    }

    /**
     * @return bool
     */
    public function deleteExpired()
    {
        $sql = $this->wpdb->prepare(
            'DELETE FROM %i WHERE expires_at IS NOT NULL AND expires_at <= NOW()',
            $this->tableName()
        );
        //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
        $result = $this->wpdb->query($sql);

        return $result !== false;
    }

    /**
     * @return bool
     */
    public function dropTable()
    {
        $sql = $this->wpdb->prepare('DROP TABLE IF EXISTS %i', $this->tableName());
        //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.NotPrepared
        $result = $this->wpdb->query($sql);

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
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            token_hash varchar(64) NOT NULL,
            expires_at datetime DEFAULT NULL,
            revoked_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_token (user_id, token_hash),
            KEY expires_at (expires_at)
        ) $charsetCollate;";

        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        dbDelta($sql);
    }
}
