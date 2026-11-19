<?php

namespace SimpleJWTLogin\Helpers\Jwt;

use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository;

/**
 * A password change invalidates every JWT issued before it: the time of the
 * change is stored on the user and any JWT whose "iat" is older is rejected.
 * Only when "Revoke sessions on password change" is on.
 */
class PasswordChangeCutoff
{
    const META_KEY = 'simple_jwt_login_tokens_valid_after';

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
        $stored     = $wordPressData->getUserMeta($userId, self::META_KEY);
        $validAfter = is_array($stored) && !empty($stored) ? (int) reset($stored) : 0;
        if ($validAfter <= 0) {
            return false;
        }
        $issuedAt = isset($payload['iat']) ? (int) $payload['iat'] : 0;

        return $issuedAt < $validAfter;
    }

    /**
     * The check the JWT endpoints run: off unless "Revoke sessions on password change" is enabled.
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
        return $authSettings->isRevokeOnPasswordChangeEnabled()
            && self::isIssuedBeforePasswordChange($wordPressData, $userId, $payload);
    }
}
