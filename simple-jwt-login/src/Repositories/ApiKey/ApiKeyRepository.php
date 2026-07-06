<?php

namespace SimpleJWTLogin\Repositories\ApiKey;

class ApiKeyRepository implements ApiKeyRepositoryInterface
{
    const TABLE_SUFFIX = 'simple_jwt_login_api_keys';

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
     * @param string      $name
     * @param string      $keyHash
     * @param string      $keyPrefix
     * @param string      $permissions
     * @param string|null $expiresAt
     * @param string      $createdAt
     * @return int|false
     */
    public function insert($userId, $name, $keyHash, $keyPrefix, $permissions, $expiresAt, $createdAt)
    {
        $result = $this->wpdb->insert(
            $this->tableName(),
            [
                'user_id'     => $userId,
                'name'        => $name,
                'key_hash'    => $keyHash,
                'key_prefix'  => $keyPrefix,
                'permissions' => $permissions,
                'expires_at'  => $expiresAt,
                'created_at'  => $createdAt,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return false;
        }

        return (int) $this->wpdb->insert_id;
    }

    /**
     * @param string $keyHash
     * @return object|null
     */
    public function getByKeyHash($keyHash)
    {
        $sql = $this->wpdb->prepare(
            'SELECT * FROM %i
             WHERE key_hash = %s
               AND revoked_at IS NULL
               AND (expires_at IS NULL OR expires_at > NOW())',
            $this->tableName(),
            $keyHash
        );
        //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
        return $this->wpdb->get_row($sql);
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
            'SELECT id, user_id, name, key_prefix, permissions, expires_at, last_used_at, created_at, revoked_at
             FROM %i
             ORDER BY created_at DESC
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
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function findByUserId($userId, $page, $perPage)
    {
        $offset = ($page - 1) * $perPage;

        $countSql = $this->wpdb->prepare('SELECT COUNT(*) FROM %i WHERE user_id = %d', $this->tableName(), $userId);
        //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
        $total = (int) $this->wpdb->get_var($countSql);

        $sql = $this->wpdb->prepare(
            'SELECT id, user_id, name, key_prefix, permissions, expires_at, last_used_at, created_at, revoked_at
             FROM %i
             WHERE user_id = %d
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d',
            $this->tableName(),
            $userId,
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
     * @param int $keyId
     * @return object|null
     */
    public function findById($keyId)
    {
        $sql = $this->wpdb->prepare(
            'SELECT id, user_id, name, key_prefix, permissions, expires_at, last_used_at, created_at, revoked_at
             FROM %i
             WHERE id = %d',
            $this->tableName(),
            $keyId
        );
        //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
        return $this->wpdb->get_row($sql);
    }

    /**
     * @param int         $keyId
     * @param string      $name
     * @param string      $permissions
     * @param string|null $expiresAt
     * @return bool
     */
    public function updateById($keyId, $name, $permissions, $expiresAt)
    {
        $result = $this->wpdb->update(
            $this->tableName(),
            [
                'name'        => $name,
                'permissions' => $permissions,
                'expires_at'  => $expiresAt,
            ],
            ['id' => $keyId],
            ['%s', '%s', '%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * @param int    $keyId
     * @param string $revokedAt
     * @return bool
     */
    public function revokeById($keyId, $revokedAt)
    {
        $result = $this->wpdb->update(
            $this->tableName(),
            ['revoked_at' => $revokedAt],
            ['id' => $keyId],
            ['%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * @param int $keyId
     * @return bool
     */
    public function deleteById($keyId)
    {
        //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
        $result = $this->wpdb->delete(
            $this->tableName(),
            ['id' => $keyId],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * @param int    $keyId
     * @param string $lastUsedAt
     * @return bool
     */
    public function touchLastUsed($keyId, $lastUsedAt)
    {
        $result = $this->wpdb->update(
            $this->tableName(),
            ['last_used_at' => $lastUsedAt],
            ['id' => $keyId],
            ['%s'],
            ['%d']
        );

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
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(255) NOT NULL DEFAULT '',
            key_hash varchar(64) NOT NULL,
            key_prefix varchar(12) NOT NULL,
            permissions longtext NOT NULL,
            expires_at datetime DEFAULT NULL,
            last_used_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            revoked_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY key_hash (key_hash),
            KEY idx_user_created (user_id, created_at),
            KEY created_at (created_at)
        ) $charsetCollate;";

        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        dbDelta($sql);
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
}
