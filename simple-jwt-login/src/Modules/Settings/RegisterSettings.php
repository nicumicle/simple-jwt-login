<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class RegisterSettings extends BaseSettings implements SettingsInterface
{
    const DEFAULT_USER_PROFILE = 'subscriber';

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_register',
            null,
            'allow_register',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'new_user_profile',
            null,
            'new_user_profile',
            BaseSettings::SETTINGS_TYPE_STRING
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'register_ip',
            null,
            'register_ip',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'register_domain',
            null,
            'register_domain',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'require_register_auth',
            null,
            'require_register_auth',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'random_password',
            null,
            'random_password',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'register_force_login',
            null,
            'register_force_login',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'register_jwt',
            null,
            'register_jwt',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'allowed_user_meta',
            null,
            'allowed_user_meta',
            BaseSettings::SETTINGS_TYPE_STRING
        );
    }

    public function validateSettings()
    {
        if (empty($this->post['new_user_profile'])) {
            throw new Exception(
                __('New User profile slug can not be empty.', 'simple-jwt-login'),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_REGISTER,
                    SettingsErrors::ERR_REGISTER_MISSING_NEW_USER_PROFILE
                )
            );
        }

        if ($this->wordPressData->roleExists($this->post['new_user_profile']) === false) {
            throw new Exception(
                __('Invalid user role provided.', 'simple-jwt-login'),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_REGISTER,
                    SettingsErrors::ERR_REGISTER_INVALID_ROLE
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isRegisterAllowed()
    {
        return !empty($this->settings['allow_register']);
    }

    /**
     * @return string
     */
    public function getNewUSerProfile()
    {
        return isset($this->settings['new_user_profile'])
            ? $this->settings['new_user_profile']
            : self::DEFAULT_USER_PROFILE;
    }

    /**
     * @return string
     */
    public function getAllowedRegisterIps()
    {
        return isset($this->settings['register_ip'])
            ? $this->settings['register_ip']
            : '';
    }

    /**
     * @return string
     */
    public function getAllowedRegisterDomain()
    {
        return isset($this->settings['register_domain'])
            ? $this->settings['register_domain']
            : '';
    }

    /**
     * @return bool
     */
    public function isAuthKeyRequiredOnRegister()
    {
        return isset($this->settings['require_register_auth'])
            ? (bool)$this->settings['require_register_auth']
            : true;
    }

    /**
     * @return bool
     */
    public function isRandomPasswordForCreateUserEnabled()
    {
        return isset($this->settings['random_password'])
            ? (bool)$this->settings['random_password']
            : false;
    }

    /**
     * @return bool
     */
    public function isForceLoginAfterCreateUserEnabled()
    {
        return isset($this->settings['register_force_login'])
            ? (bool)$this->settings['register_force_login']
            : false;
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
        return isset($this->settings['register_jwt'])
            ? (bool) $this->settings['register_jwt']
            : false;
    }
}
