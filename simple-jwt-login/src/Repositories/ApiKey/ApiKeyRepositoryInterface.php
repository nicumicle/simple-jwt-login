<?php

namespace SimpleJWTLogin\Repositories\ApiKey;

interface ApiKeyRepositoryInterface
{
    /**
     * @param int         $userId
     * @param string      $name
     * @param string      $keyHash    SHA-256 hex digest of the raw key
     * @param string      $keyPrefix  First 8 chars of the raw key (display only)
     * @param string      $permissions JSON-encoded array of permission tokens
     * @param string|null $expiresAt  Datetime string or null
     * @param string      $createdAt  Datetime string
     * @return int|false  Inserted row ID on success, false on failure
     */
    public function insert($userId, $name, $keyHash, $keyPrefix, $permissions, $expiresAt, $createdAt);

    /**
     * Find an active (non-revoked, non-expired) key by its SHA-256 hash.
     *
     * @param string $keyHash
     * @return object|null
     */
    public function getByKeyHash($keyHash);

    /**
     * Return a paginated list of all keys (including revoked) — never includes key_hash.
     *
     * @param int $page    1-based page number
     * @param int $perPage Rows per page
     * @return array{items: object[], total: int}
     */
    public function findAll($page, $perPage);

    /**
     * Return a paginated list of keys belonging to a specific user — never includes key_hash.
     *
     * @param int $userId
     * @param int $page    1-based page number
     * @param int $perPage Rows per page
     * @return array{items: object[], total: int}
     */
    public function findByUserId($userId, $page, $perPage);

    /**
     * Fetch a single key row by ID (without key_hash). Returns null if not found.
     *
     * @param int $keyId
     * @return object|null
     */
    public function findById($keyId);

    /**
     * Update mutable fields of a key by its ID.
     *
     * @param int         $keyId
     * @param string      $name
     * @param string      $permissions JSON-encoded array of permission tokens
     * @param string|null $expiresAt   Datetime string or null
     * @return bool
     */
    public function updateById($keyId, $name, $permissions, $expiresAt);

    /**
     * Soft-delete a key by setting revoked_at to the current UTC time.
     *
     * @param int    $keyId
     * @param string $revokedAt Datetime string
     * @return bool
     */
    public function revokeById($keyId, $revokedAt);

    /**
     * Permanently remove a key row from the database.
     *
     * @param int $keyId
     * @return bool
     */
    public function deleteById($keyId);

    /**
     * Stamp last_used_at on a key to track usage.
     *
     * @param int    $keyId
     * @param string $lastUsedAt Datetime string
     * @return bool
     */
    public function touchLastUsed($keyId, $lastUsedAt);

    /**
     * Create (or upgrade) the API keys table via dbDelta.
     *
     * @return void
     */
    public function createTable();

    /**
     * Drop the API keys table entirely (used on plugin uninstall).
     *
     * @return bool
     */
    public function dropTable();
}
