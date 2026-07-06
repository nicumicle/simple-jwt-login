<?php

namespace SimpleJWTLogin\Repositories\RevokedToken;

interface Repository
{
    /**
     * @param int         $userId
     * @param string      $tokenHash
     * @param string|null $expiresAt Y-m-d H:i:s, or null when the JWT has no `exp` claim
     * @return bool
     */
    public function insert($userId, $tokenHash, $expiresAt);

    /**
     * @param int    $userId
     * @param string $tokenHash
     * @return bool
     */
    public function existsForUser($userId, $tokenHash);

    /**
     * @param int $revokedTokenId
     * @return bool
     */
    public function existsById($revokedTokenId);

    /**
     * @param int $revokedTokenId
     * @return bool
     */
    public function deleteById($revokedTokenId);

    /**
     * Delete every revoked-token row belonging to the given user.
     *
     * @param int $userId
     * @return bool
     */
    public function deleteByUserId($userId);

    /**
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function findAll($page, $perPage);

    /**
     * Remove all rows whose expiry date is in the past.
     *
     * @return bool
     */
    public function deleteExpired();

    /**
     * Drop the revoked tokens table entirely (used on plugin uninstall).
     *
     * @return bool
     */
    public function dropTable();

    /**
     * Create (or upgrade) the revoked tokens table via dbDelta.
     *
     * @return void
     */
    public function createTable();
}
