<?php

namespace SimpleJWTLogin\Repositories\WebhookLog;

use SimpleJWTLogin\Repositories\DateFilterTrait;

class WebhookLogRepository implements Repository
{
    use DateFilterTrait;

    const TABLE_SUFFIX = 'simple_jwt_login_webhook_logs';

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
     * @param string      $webhookUrl
     * @param string      $event
     * @param string      $method
     * @param int|null    $statusCode
     * @param string|null $responseBody
     * @return bool
     */
    public function insert($webhookUrl, $event, $method, $statusCode, $responseBody)
    {
        $result = $this->wpdb->insert(
            $this->tableName(),
            [
                'webhook_url'   => $webhookUrl,
                'event'         => $event,
                'method'        => $method,
                'status_code'   => $statusCode,
                'response_body' => $responseBody,
            ],
            ['%s', '%s', '%s', '%d', '%s']
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

        if (!empty($filters['event'])) {
            $where[]  = 'event = %s';
            $params[] = $filters['event'];
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'success') {
                $where[] = 'status_code >= 200 AND status_code < 300';
            }
            if ($filters['status'] === 'failure') {
                $where[] = '(status_code IS NULL OR status_code < 200 OR status_code >= 300)';
            }
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
            webhook_url varchar(500) NOT NULL,
            event varchar(100) NOT NULL,
            method varchar(10) NOT NULL DEFAULT 'POST',
            status_code int(5) DEFAULT NULL,
            response_body text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event (event),
            KEY status_code (status_code),
            KEY created_at (created_at)
        ) $charsetCollate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
