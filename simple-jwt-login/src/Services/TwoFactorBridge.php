<?php

namespace SimpleJWTLogin\Services;

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
     * @return string|false
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
}
