<?php

namespace SimpleJWTLogin\Repositories\AuditLog;

interface Repository
{
    /**
     * @param string      $eventType
     * @param int|null    $userId
     * @param string|null $userEmail
     * @param string|null $ipAddress
     * @param string      $status
     * @param string|null $message
     * @return bool
     */
    public function insert($eventType, $userId, $userEmail, $ipAddress, $status, $message);

    /**
     * Returns paginated log entries matching the given filters.
     *
     * Supported filter keys: event_type, status, user_id, user_email, date_from (Y-m-d), date_to (Y-m-d).
     *
     * @param array $filters
     * @param int   $page    1-based page number
     * @param int   $perPage
     * @return array{items: object[], total: int}
     */
    public function findPaginated($filters, $page, $perPage);

    /**
     * Delete all entries older than the given datetime string (Y-m-d H:i:s).
     *
     * @param string $beforeDatetime
     * @return bool
     */
    public function deleteOlderThan($beforeDatetime);

    /**
     * Delete all audit log entries.
     *
     * @return bool
     */
    public function deleteAll();

    /**
     * Drop the audit logs table entirely (used on plugin uninstall).
     *
     * @return bool
     */
    public function dropTable();

    /**
     * Create (or upgrade) the audit logs table via dbDelta.
     *
     * @return void
     */
    public function createTable();
}
