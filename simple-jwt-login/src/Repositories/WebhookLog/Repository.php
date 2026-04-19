<?php

namespace SimpleJWTLogin\Repositories\WebhookLog;

interface Repository
{
    /**
     * @param string   $webhookUrl
     * @param string   $event
     * @param string   $method
     * @param int|null $statusCode
     * @param string|null $responseBody  Only stored on error (non-2xx or wp_error)
     * @return bool
     */
    public function insert($webhookUrl, $event, $method, $statusCode, $responseBody);

    /**
     * @param array $filters  Supported keys: event, status (success|failure), date_from (Y-m-d), date_to (Y-m-d)
     * @param int   $page
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
     * @return bool
     */
    public function deleteAll();

    /**
     * @return bool
     */
    public function dropTable();

    /**
     * @return void
     */
    public function createTable();
}
