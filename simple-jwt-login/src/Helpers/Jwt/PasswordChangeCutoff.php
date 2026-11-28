<?php

namespace SimpleJWTLogin\Helpers\Jwt;

use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository;

/**
 * A password change invalidates every JWT issued before it: the time of the
 * change is stored on the user and any JWT whose "iat" is older is rejected.
 * Only when "Revoke sessions on password change" is on.
 *
 * `wp jwt revoke-all` stores a second cutoff that always applies.
 */
class PasswordChangeCutoff
{
    const META_KEY = 'simple_jwt_login_tokens_valid_after';
    const REVOKE_ALL_META_KEY = 'simple_jwt_login_tokens_revoked_before';

    /**
     * Revoke every JWT and refresh token issued until now, including this second.
     *
     * @param Repository $wordPressData
     * @param int $userId
     * @return void
     */
    public static function markAllRevoked(Repository $wordPressData, $userId)
    {
        $wordPressData->updateUserMeta($userId, self::REVOKE_ALL_META_KEY, (string) time());
    }

    /**
     * @param Repository $wordPressData
     * @param int $userId
     * @return void
     */
    public static function markPasswordChanged(Repository $wordPressData, $userId)
    {
        $wordPressData->updateUserMeta($userId, self::META_KEY, (string) time());
    }

    /**
     * A JWT without "iat" cannot prove it is newer than the change, so it is rejected too.
     *
     * @param Repository $wordPressData
     * @param int $userId
     * @param array $payload
     * @return bool
     */
    public static function isIssuedBeforePasswordChange(Repository $wordPressData, $userId, array $payload)
    {
        $validAfter = self::readCutoff($wordPressData, $userId, self::META_KEY);

        return $validAfter > 0 && self::issuedAt($payload) < $validAfter;
    }

    /**
     * @param Repository $wordPressData
     * @param int $userId
     * @param array $payload
     * @return bool
     */
    public static function isIssuedBeforeRevokeAll(Repository $wordPressData, $userId, array $payload)
    {
        $revokedBefore = self::readCutoff($wordPressData, $userId, self::REVOKE_ALL_META_KEY);

        // A token issued in the same second as the revocation could be one of the revoked ones.
        return $revokedBefore > 0 && self::issuedAt($payload) <= $revokedBefore;
    }

    /**
     * @param Repository $wordPressData
     * @param int $userId
     * @param string $metaKey
     * @return int
     */
    private static function readCutoff(Repository $wordPressData, $userId, $metaKey)
    {
        $stored = $wordPressData->getUserMeta($userId, $metaKey);

        return is_array($stored) && !empty($stored) ? (int) reset($stored) : 0;
    }

    /**
     * @param array $payload
     * @return int
     */
    private static function issuedAt(array $payload)
    {
        return isset($payload['iat']) ? (int) $payload['iat'] : 0;
    }

    /**
     * The check the JWT endpoints run: the password change cutoff (only when "Revoke sessions on
     * password change" is enabled) and the `wp jwt revoke-all` cutoff (always).
     *
     * @param AuthenticationSettings $authSettings
     * @param Repository $wordPressData
     * @param int $userId
     * @param array $payload
     * @return bool
     */
    public static function isRevokedByPasswordChange(
        AuthenticationSettings $authSettings,
        Repository $wordPressData,
        $userId,
        array $payload
    ) {
        if (self::isIssuedBeforeRevokeAll($wordPressData, $userId, $payload)) {
            return true;
        }

        return $authSettings->isRevokeOnPasswordChangeEnabled()
            && self::isIssuedBeforePasswordChange($wordPressData, $userId, $payload);
    }
}
