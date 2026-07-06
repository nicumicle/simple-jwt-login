<?php

namespace SimpleJWTLogin\Repositories\RefreshToken;

interface Repository
{
    /**
     * @param int    $userId
     * @param string $refreshToken
     * @param int    $expiresAt    Unix timestamp
     * @return bool
     */
    public function insert($userId, $refreshToken, $expiresAt);

    /**
     * Returns the token row or null when not found / expired.
     *
     * @param string $refreshToken
     * @return object|null
     */
    public function getByToken($refreshToken);

    /**
     * @param string $refreshToken
     * @return bool
     */
    public function deleteByToken($refreshToken);

    /**
     * Delete every refresh token belonging to the given user.
     *
     * @param int $userId
     * @return bool
     */
    public function deleteByUserId($userId);

    /**
     * Remove all tokens whose expiry date is in the past.
     *
     * @return bool
     */
    public function cleanupExpired();

    /**
     * Drop the refresh tokens table entirely (used on plugin uninstall).
     *
     * @return bool
     */
    public function dropTable();

    /**
     * Create (or upgrade) the refresh tokens table via dbDelta.
     *
     * @return void
     */
    public function createTable();
}
