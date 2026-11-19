<?php

namespace SimpleJWTLogin\Services\Integrations\TwoFactor;

use SimpleJWTLogin\Repositories\Wordpress\Repository;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

class TwoFactorBridge
{
    /**
     * @var Repository|null
     */
    private $wordPressData;

    /**
     * @param Repository|null $wordPressData
     */
    public function __construct($wordPressData = null)
    {
        $this->wordPressData = $wordPressData;
    }

    /**
     * @return Repository
     */
    protected function getWordPressData()
    {
        if ($this->wordPressData === null) {
            $this->wordPressData = WordPressRepository::getInstance();
        }

        return $this->wordPressData;
    }

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
     * Record a failed 2FA code like Two_Factor_Core::process_provider() does:
     * is_user_rate_limited() then applies its backoff, wp_login_failed lets brute-force
     * plugins block the client, and after too many failures (30 by default, filter
     * two_factor_failed_attempt_limit) the password is reset and the user is emailed.
     *
     * @param mixed $user
     * @param int $userId
     * @return bool True when the password was reset.
     */
    public function recordFailedAttempt($user, $userId)
    {
        $wordPressData = $this->getWordPressData();
        $attemptsKey   = \Two_Factor_Core::USER_FAILED_LOGIN_ATTEMPTS_KEY;
        $attempts      = $this->toInt($wordPressData->getUserMeta($userId, $attemptsKey));

        $wordPressData->updateUserMeta($userId, \Two_Factor_Core::USER_RATE_LIMIT_KEY, (string) time());
        $wordPressData->updateUserMeta($userId, $attemptsKey, (string) ($attempts + 1));
        $wordPressData->loginFailed((string) $wordPressData->getUserProperty($user, 'user_login'));

        if (!\Two_Factor_Core::should_reset_password($userId)) {
            return false;
        }

        // Also deletes the login nonce, so the interim JWT stops working.
        \Two_Factor_Core::reset_compromised_password($user);
        \Two_Factor_Core::send_password_reset_emails($user);

        return true;
    }

    /**
     * Clear the failed 2FA attempts after a successful verification.
     *
     * @param int $userId
     * @return void
     */
    public function resetFailedAttempts($userId)
    {
        $wordPressData = $this->getWordPressData();
        $wordPressData->deleteUserMeta($userId, \Two_Factor_Core::USER_RATE_LIMIT_KEY, '');
        $wordPressData->deleteUserMeta($userId, \Two_Factor_Core::USER_FAILED_LOGIN_ATTEMPTS_KEY, '');
    }

    /**
     * get_user_meta() returns a list of values when $single is false.
     *
     * @param mixed $metaValue
     * @return int
     */
    protected function toInt($metaValue)
    {
        if (is_array($metaValue)) {
            return isset($metaValue[0]) ? (int) $metaValue[0] : 0;
        }

        return (int) $metaValue;
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
