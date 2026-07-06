<?php

/**
 * Test doubles mirroring the public static API of the "Two Factor" WordPress
 * plugin (\Two_Factor_Core and provider classes). These intentionally use the
 * plugin's snake_case method names and live together in one file, mirroring the
 * MysqliWpdb test-double approach; this file is excluded from PHP_CodeSniffer.
 *
 * Each class returns a value driven by a public static property so individual
 * tests can configure the delegated result.
 */

if (!class_exists('Two_Factor_Core')) {
    class Two_Factor_Core
    {
        public static $usingTwoFactor = true;
        public static $primaryProvider = 'Two_Factor_Totp';
        public static $nonce = array('key' => 'abc');
        public static $verifyNonceResult = true;
        public static $rateLimited = false;
        public static $timeDelay = 5;
        public static $deletedNonceFor = null;

        public static function is_user_using_two_factor($user)
        {
            return self::$usingTwoFactor;
        }

        public static function get_primary_provider_for_user($user)
        {
            return self::$primaryProvider;
        }

        public static function create_login_nonce($userId)
        {
            return self::$nonce;
        }

        public static function verify_login_nonce($userId, $nonce)
        {
            return self::$verifyNonceResult;
        }

        public static function is_user_rate_limited($user)
        {
            return self::$rateLimited;
        }

        public static function get_user_time_delay($user)
        {
            return self::$timeDelay;
        }

        public static function delete_login_nonce($userId)
        {
            self::$deletedNonceFor = $userId;
        }
    }

    class Two_Factor_Totp
    {
        public static $result = true;

        public static function get_instance()
        {
            return new self();
        }

        public function validate_code_for_user($user, $code)
        {
            return self::$result;
        }
    }

    class Two_Factor_Email
    {
        public static $result = true;

        public static function get_instance()
        {
            return new self();
        }

        public function validate_token($userId, $code)
        {
            return self::$result;
        }
    }

    class Two_Factor_Backup_Codes
    {
        public static $result = true;

        public static function get_instance()
        {
            return new self();
        }

        public function validate_code($user, $code)
        {
            return self::$result;
        }
    }
}
