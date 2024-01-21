<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class ApplicationsSettings extends BaseSettings implements SettingsInterface
{
    const GOOGLE_GROUP = 'google';

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            self::GOOGLE_GROUP,
            'enabled',
            self::GOOGLE_GROUP,
            'enabled',
            BaseSettings::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            self::GOOGLE_GROUP,
            'client_id',
            self::GOOGLE_GROUP,
            'client_id',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            self::GOOGLE_GROUP,
            'client_secret',
            self::GOOGLE_GROUP,
            'client_secret',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            self::GOOGLE_GROUP,
            'allow_on_all_endpoints',
            self::GOOGLE_GROUP,
            'allow_on_all_endpoints',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            self::GOOGLE_GROUP,
            'create_user_if_not_exists',
            self::GOOGLE_GROUP,
            'create_user_if_not_exists',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            self::GOOGLE_GROUP,
            'enable_oauth',
            self::GOOGLE_GROUP,
            'enable_oauth',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            self::GOOGLE_GROUP,
            'enable_exchange_code',
            self::GOOGLE_GROUP,
            'enable_exchange_code',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            self::GOOGLE_GROUP,
            'redirect_uri_exchange_code',
            self::GOOGLE_GROUP,
            'redirect_uri_exchange_code',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            self::GOOGLE_GROUP,
            'enable_exchange_id_token',
            self::GOOGLE_GROUP,
            'enable_exchange_id_token',
            BaseSettings::SETTINGS_TYPE_BOL
        );
    }

    public function validateSettings()
    {
        if (empty($this->post[self::GOOGLE_GROUP]['enabled'])) {
            return;
        }

        if (!empty($this->post[self::GOOGLE_GROUP]['enabled'])
            && empty($this->post[self::GOOGLE_GROUP]['enable_exchange_code'])
            && empty($this->post[self::GOOGLE_GROUP]['enable_exchange_id_token'])
            && empty($this->post[self::GOOGLE_GROUP]['enable_oauth'])
        ) {
            throw new Exception(
                __(
                    'You need to enable at least one Google Oauth option in order to enable the Google App.',
                    'simple-jwt-login'
                ),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_APPLICATIONS,
                    SettingsErrors::ERR_GOOGLE_CLIENT_ID_REQUIRED
                )
            );
        }
        if (empty($this->post[self::GOOGLE_GROUP]['client_id'])) {
            throw new Exception(
                __(
                    'Google Client ID is required.',
                    'simple-jwt-login'
                ),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_APPLICATIONS,
                    SettingsErrors::ERR_GOOGLE_CLIENT_ID_REQUIRED
                )
            );
        }

        if (empty($this->post[self::GOOGLE_GROUP]['client_secret'])) {
            throw new Exception(
                __(
                    'Google Client Secret is required.',
                    'simple-jwt-login'
                ),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_APPLICATIONS,
                    SettingsErrors::ERR_GOOGLE_CLIENT_SECRET_REQUIRED
                )
            );
        }

        if (!empty($this->post[self::GOOGLE_GROUP]['enable_exchange_code'])
            && empty($this->post[self::GOOGLE_GROUP]['redirect_uri_exchange_code'])
        ) {
            throw new Exception(
                __(
                    'Google Redirect URI is required when exchange code is enabled.',
                    'simple-jwt-login'
                ),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_APPLICATIONS,
                    SettingsErrors::ERR_GOOGLE_REDIRECT_URI_REQUIRED_FOR_EXCHANGE_CODE
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isGoogleEnabled()
    {
        return isset($this->settings[self::GOOGLE_GROUP]['enabled'])
            && !empty($this->settings[self::GOOGLE_GROUP]['enabled']);
    }

    /**
     * @return string
     */
    public function getGoogleClientID()
    {
        if (isset($this->settings[self::GOOGLE_GROUP]['client_id'])) {
            return $this->settings[self::GOOGLE_GROUP]['client_id'];
        }

        return  "";
    }

    /**
     * @return string
     */
    public function getGoogleClientSecret()
    {
        if (isset($this->settings[self::GOOGLE_GROUP]['client_secret'])) {
            return $this->settings[self::GOOGLE_GROUP]['client_secret'];
        }

        return  "";
    }

    /**
     * @return bool
     */
    public function isGoogleJwtAllowedOnAllEndpoints()
    {
        return isset($this->settings[self::GOOGLE_GROUP]['allow_on_all_endpoints'])
            && !empty($this->settings[self::GOOGLE_GROUP]['allow_on_all_endpoints']);
    }

    /**
     * @return bool
     */
    public function isGoogleCreateUserIfNotExistsEnabled()
    {
        return isset($this->settings[self::GOOGLE_GROUP]['create_user_if_not_exists'])
            && !empty($this->settings[self::GOOGLE_GROUP]['create_user_if_not_exists']);
    }

    public function isOauthEnabled()
    {
        return isset($this->settings[self::GOOGLE_GROUP]['enable_oauth'])
            && !empty($this->settings[self::GOOGLE_GROUP]['enable_oauth']);
    }

    public function isGoogleExchangeCodeEnabled()
    {
        return isset($this->settings[self::GOOGLE_GROUP]['enable_exchange_code'])
            && !empty($this->settings[self::GOOGLE_GROUP]['enable_exchange_code']);
    }

    public function getGoogleExchangeCodeRedirectUri()
    {
        if (isset($this->settings[self::GOOGLE_GROUP]['redirect_uri_exchange_code'])) {
            return $this->settings[self::GOOGLE_GROUP]['redirect_uri_exchange_code'];
        }

        return  "";
    }

    public function isGoogleExchangeIdTokenEnabled()
    {
        return isset($this->settings[self::GOOGLE_GROUP]['enable_exchange_id_token'])
            && !empty($this->settings[self::GOOGLE_GROUP]['enable_exchange_id_token']);
    }
}
