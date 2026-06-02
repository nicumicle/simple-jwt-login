<?php

namespace SimpleJWTLogin\Repositories\AuditLog;

use SimpleJWTLogin\Repositories\DateFilterTrait;

class AuditLogRepository implements Repository
{
    use DateFilterTrait;

    const TABLE_SUFFIX = 'simple_jwt_login_audit_logs';

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
     * @param string      $eventType
     * @param int|null    $userId
     * @param string|null $userEmail
     * @param string|null $ipAddress
     * @param string      $status
     * @param string|null $message
     * @param int|null    $apiKeyId
     * @return bool
     */
    public function insert($eventType, $userId, $userEmail, $ipAddress, $status, $message, $apiKeyId = null)
    {
        $result = $this->wpdb->insert(
            $this->tableName(),
            [
                'event_type'  => $eventType,
                'user_id'     => $userId,
                'user_email'  => $userEmail,
                'ip_address'  => $ipAddress,
                'status'      => $status,
                'message'     => $message,
                'api_key_id'  => $apiKeyId,
            ],
            ['%s', '%d', '%s', '%s', '%s', '%s', '%d']
        );

        return $result !== false;
    }

    /**
     * @param array $filters
     * @param int   $page
     * @param int   $perPage
     * @return array
     */
    public function findPaginated($filters, $page, $perPage)
    {
        $where  = [];
        $params = [];

        if (!empty($filters['event_type'])) {
            $where[]  = 'event_type = %s';
            $params[] = $filters['event_type'];
        }

        if (!empty($filters['status'])) {
            $where[]  = 'status = %s';
            $params[] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $where[]  = 'user_id = %d';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['user_email'])) {
            $where[]  = 'user_email LIKE %s';
            $params[] = '%' . $this->wpdb->esc_like($filters['user_email']) . '%';
        }

        if (!empty($filters['date_from']) && $this->isValidDate($filters['date_from'])) {
            $where[]  = 'created_at >= %s';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to']) && $this->isValidDate($filters['date_to'])) {
            $where[]  = 'created_at <= %s';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $offset = ($page - 1) * $perPage;

        $countSql    = 'SELECT COUNT(*) FROM %i ' . $whereClause;
        $dataSql     = 'SELECT * FROM %i ' . $whereClause . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
        $countParams = array_merge([$this->tableName()], $params);
        $dataParams  = array_merge([$this->tableName()], $params, [$perPage, $offset]);

        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $preparedCountSql = $this->wpdb->prepare($countSql, $countParams);
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $total = (int) $this->wpdb->get_var($preparedCountSql);
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $items = $this->wpdb->get_results($this->wpdb->prepare($dataSql, $dataParams));

        return [
            'items' => $items !== null ? $items : [],
            'total' => $total,
        ];
    }

    /**
     * @param string $beforeDatetime
     * @return bool
     */
    public function deleteOlderThan($beforeDatetime)
    {
        $sql = $this->wpdb->prepare(
            'DELETE FROM %i WHERE created_at < %s',
            $this->tableName(),
            $beforeDatetime
        );
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $this->wpdb->query($sql);

        return $result !== false;
    }

    /**
     * @return bool
     */
    public function deleteAll()
    {
        $escapedTable = esc_sql($this->tableName());
        //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $this->wpdb->query('DELETE FROM `' . $escapedTable . '`');

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
            event_type varchar(100) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            user_email varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'success',
            message text DEFAULT NULL,
            api_key_id bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event_created (event_type, created_at),
            KEY idx_user_created (user_id, created_at),
            KEY idx_status_created (status, created_at),
            KEY idx_apikey_created (api_key_id, created_at),
            KEY created_at (created_at)
        ) $charsetCollate;";

        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }
        dbDelta($sql);
    }
}
