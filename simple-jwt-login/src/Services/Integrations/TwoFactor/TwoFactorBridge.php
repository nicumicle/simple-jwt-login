<?php

namespace SimpleJWTLogin\Services\Integrations\TwoFactor;

class TwoFactorBridge
{
    /**
     * @return bool
     */
    public function isAvailable()
    {
        return class_exists('\Two_Factor_Core');
    }

    /**
     * @param mixed $user
     * @return bool
     */
    public function isUserUsing2FA($user)
    {
        return \Two_Factor_Core::is_user_using_two_factor($user);
    }

    /**
     * @param mixed $user
     * @return mixed
     */
    public function getPrimaryProvider($user)
    {
        return \Two_Factor_Core::get_primary_provider_for_user($user);
    }

    /**
     * @param int $userId
     * @return array|false
     */
    public function createNonce($userId)
    {
        return \Two_Factor_Core::create_login_nonce($userId);
    }

    /**
     * @param int $userId
     * @param string $nonce
     * @return bool
     */
    public function verifyNonce($userId, $nonce)
    {
        return \Two_Factor_Core::verify_login_nonce($userId, $nonce);
    }

    /**
     * @param mixed $user
     * @return bool
     */
    public function isRateLimited($user)
    {
        return \Two_Factor_Core::is_user_rate_limited($user);
    }

    /**
     * @param mixed $user
     * @return int
     */
    public function getTimeDelay($user)
    {
        return \Two_Factor_Core::get_user_time_delay($user);
    }

    /**
     * Set the interim 'two_factor' auth cookie so wp-login.php?action=two-factor
     * accepts the session. Mirrors what Two_Factor_Core::prepare_login() does.
     *
     * @param int $userId
     * @return void
     */
    public function setInterimCookie($userId)
    {
        wp_set_auth_cookie($userId, false, is_ssl(), 'two_factor');
    }

    /**
     * Verify a submitted 2FA code against the given provider class.
     *
     * @param string $providerClass
     * @param mixed  $user
     * @param string $code
     * @param int    $userId
     * @return bool
     */
    public function verifyCode($providerClass, $user, $code, $userId)
    {
        if (strpos($providerClass, 'Two_Factor_Totp') !== false
            && class_exists('\Two_Factor_Totp')
        ) {
            $instance = \Two_Factor_Totp::get_instance();
            if (method_exists($instance, 'validate_code_for_user')) {
                return (bool) $instance->validate_code_for_user($user, $code);
            }
        }

        if (strpos($providerClass, 'Two_Factor_Email') !== false
            && class_exists('\Two_Factor_Email')
        ) {
            $instance = \Two_Factor_Email::get_instance();
            if (method_exists($instance, 'validate_token')) {
                return (bool) $instance->validate_token($userId, $code);
            }
        }

        if (strpos($providerClass, 'Two_Factor_Backup_Codes') !== false
            && class_exists('\Two_Factor_Backup_Codes')
        ) {
            $instance = \Two_Factor_Backup_Codes::get_instance();
            if (method_exists($instance, 'validate_code')) {
                return (bool) $instance->validate_code($user, $code);
            }
        }

        return false;
    }

    /**
     * Delete the login nonce for a user after successful or failed verification.
     *
     * @param int $userId
     * @return void
     */
    public function deleteNonce($userId)
    {
        \Two_Factor_Core::delete_login_nonce($userId);
    }
}
