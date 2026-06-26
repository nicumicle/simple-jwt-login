<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class RegisterSettings extends BaseSettings implements SettingsInterface
{
    const DEFAULT_USER_PROFILE = 'subscriber';

    protected function getSectionKey()
    {
        return 'register';
    }

    protected function getFieldDefinitions()
    {
        return [
            [null, 'enabled',                null, 'allow_register',              self::SETTINGS_TYPE_BOL],
            [null, 'new_user_profile',       null, 'new_user_profile',            self::SETTINGS_TYPE_STRING],
            [null, 'ip_whitelist',           null, 'register_ip',                 self::SETTINGS_TYPE_STRING],
            [null, 'domain_whitelist',       null, 'register_domain',             self::SETTINGS_TYPE_STRING],
            [null, 'auth_code',              null, 'require_register_auth',       self::SETTINGS_TYPE_BOL],
            [null, 'random_password',        null, 'random_password',             self::SETTINGS_TYPE_BOL, false],
            [null, 'random_password_length', null, 'random_password_length',      self::SETTINGS_TYPE_INT, 10],
            [null, 'force_login',            null, 'register_force_login',        self::SETTINGS_TYPE_BOL, false],
            [null, 'return_jwt',             null, 'register_jwt',                self::SETTINGS_TYPE_BOL, false],
            [null, 'allowed_user_meta',      null, 'allowed_user_meta',           self::SETTINGS_TYPE_STRING],
            [null, 'send_welcome_email',     null, 'register_send_welcome_email', self::SETTINGS_TYPE_BOL, false],
        ];
    }

    public function validateSettings()
    {
        if (empty($this->post['new_user_profile'])) {
            throw new Exception(
                esc_html__('New User profile slug can not be empty.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_REGISTER,
                    SettingsErrors::ERR_REGISTER_MISSING_NEW_USER_PROFILE
                ))
            );
        }

        $roles = array_filter(array_map('trim', explode(',', $this->post['new_user_profile'])));
        if (empty($roles)) {
            throw new Exception(
                esc_html__('New User profile slug can not be empty.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_REGISTER,
                    SettingsErrors::ERR_REGISTER_MISSING_NEW_USER_PROFILE
                ))
            );
        }

        foreach ($roles as $role) {
            if (!$this->wordPressData->roleExists($role)) {
                throw new Exception(
                    esc_html__('Invalid user role provided.', 'simple-jwt-login'),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_REGISTER,
                        SettingsErrors::ERR_REGISTER_INVALID_ROLE
                    ))
                );
            }
        }

        if (isset($this->post['random_password_length']) && !is_numeric($this->post['random_password_length'])) {
            throw new Exception(
                esc_html__('Random password length should be an integer.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_REGISTER,
                    SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_NUMERIC
                ))
            );
        }

        if (isset($this->post['random_password_length']) && ((int)$this->post['random_password_length'] < 6)) {
            throw new Exception(
                esc_html__('Random password length should be at least 6 characters.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_REGISTER,
                    SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_MIN_LENGTH
                ))
            );
        }
        if (isset($this->post['random_password_length']) && ((int)$this->post['random_password_length'] > 255)) {
            throw new Exception(
                esc_html__('Random password length can be max 255.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_REGISTER,
                    SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_MAX_LENGTH
                ))
            );
        }
    }

    /**
     * @return bool
     */
    public function isRegisterAllowed()
    {
        return !empty($this->settings['enabled']);
    }

    /**
     * @return array
     */
    public function getNewUserRoles()
    {
        $raw = isset($this->settings['new_user_profile'])
            ? $this->settings['new_user_profile']
            : self::DEFAULT_USER_PROFILE;
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * @return string
     */
    public function getAllowedRegisterIps()
    {
        return isset($this->settings['ip_whitelist'])
            ? $this->settings['ip_whitelist']
            : '';
    }

    /**
     * @return string
     */
    public function getAllowedRegisterDomain()
    {
        return isset($this->settings['domain_whitelist'])
            ? $this->settings['domain_whitelist']
            : '';
    }

    /**
     * @return bool
     */
    public function isAuthKeyRequiredOnRegister()
    {
        return isset($this->settings['auth_code'])
            ? (bool)$this->settings['auth_code']
            : true;
    }

    /**
     * @return bool
     */
    public function isRandomPasswordForCreateUserEnabled()
    {
        return !empty($this->settings['random_password']);
    }

    /**
     * @return int
     */
    public function getRandomPasswordLength()
    {
        return isset($this->settings['random_password_length'])
            ? (int)$this->settings['random_password_length']
            : 10;
    }

    /**
     * @return bool
     */
    public function isForceLoginAfterCreateUserEnabled()
    {
        return !empty($this->settings['force_login']);
    }

    /**
     * @return string
     */
    public function getAllowedUserMeta()
    {
        return isset($this->settings['allowed_user_meta'])
            ? $this->settings['allowed_user_meta']
            : '';
    }

    /**
     * @return bool
     */
    public function isJwtEnabled()
    {
        return !empty($this->settings['return_jwt']);
    }

    /**
     * @return bool
     */
    public function isSendWelcomeEmailEnabled()
    {
        return !empty($this->settings['send_welcome_email']);
    }
}
