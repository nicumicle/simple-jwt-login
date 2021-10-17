<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class AuthenticationSettings extends BaseSettings implements SettingsInterface
{
    const JWT_PAYLOAD_PARAM_IAT = 'iat';
    const JWT_PAYLOAD_PARAM_EXP = 'exp';
    const JWT_PAYLOAD_PARAM_EMAIL = 'email';
    const JWT_PAYLOAD_PARAM_ID = 'id';
    const JWT_PAYLOAD_PARAM_SITE = 'site';
    const JWT_PAYLOAD_PARAM_USERNAME = 'username';

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_authentication',
            null,
            'allow_authentication',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_payload',
            null,
            'jwt_payload',
            BaseSettings::SETTINGS_TYPE_ARRAY
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_auth_ttl',
            null,
            'jwt_auth_ttl',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_auth_refresh_ttl',
            null,
            'jwt_auth_refresh_ttl',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'auth_ip',
            null,
            'auth_ip',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'auth_requires_auth_code',
            null,
            'auth_requires_auth_code',
            BaseSettings::SETTINGS_TYPE_BOL
        );
    }

    /**
     * @throws Exception
     */
    public function validateSettings()
    {
        if (!isset($this->post['allow_authentication'])) {
            return;
        }
        if ((int)$this->post['allow_authentication'] === 1
            && empty($this->post['jwt_payload'])
        ) {
            throw new Exception(
                __(
                    'Authentication payload data can not be empty.'
                    . ' Please choose the ones you want to be added in the JWT.',
                    'simple-jwt-login'
                ),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_EMPTY_PAYLOAD
                )
            );
        }

        if (!isset($this->post['jwt_auth_ttl'])
            || empty((int)$this->post['jwt_auth_ttl'])
            || (int)$this->post['jwt_auth_ttl'] < 0
        ) {
            throw new Exception(
                __(
                    'Authentication JWT time to live should be greater than zero.',
                    'simple-jwt-login'
                ),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_TTL
                )
            );
        }

        if (!isset($this->post['jwt_auth_refresh_ttl'])
            || empty((int)$this->post['jwt_auth_refresh_ttl'])
            || (int)$this->post['jwt_auth_refresh_ttl'] < 0) {
            throw new Exception(
                __(
                    'Authentication JWT Refresh time to live should be greater than zero.',
                    'simple-jwt-login'
                ),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_REFRESH_TTL_ZERO
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isAuthenticationEnabled()
    {
        return isset($this->settings['allow_authentication'])
         && !empty($this->settings['allow_authentication']);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isPayloadDataEnabled($name)
    {
        return !empty($this->settings['jwt_payload'])
            && in_array($name, $this->settings['jwt_payload']);
    }

    /**
     * @return string[]
     */
    public function getJwtPayloadParameters()
    {
        return [
            self::JWT_PAYLOAD_PARAM_IAT,
            self::JWT_PAYLOAD_PARAM_EXP,
            self::JWT_PAYLOAD_PARAM_EMAIL,
            self::JWT_PAYLOAD_PARAM_ID,
            self::JWT_PAYLOAD_PARAM_SITE,
            self::JWT_PAYLOAD_PARAM_USERNAME
        ];
    }

    /**
     * @return int
     */
    public function getAuthJwtTtl()
    {
        return isset($this->settings['jwt_auth_ttl'])
            ? (int)$this->settings['jwt_auth_ttl']
            : 60;
    }

    /**
     * @return int
     */
    public function getAuthJwtRefreshTtl()
    {
        return isset($this->settings['jwt_auth_refresh_ttl'])
            ? (int)$this->settings['jwt_auth_refresh_ttl']
            : 20160;
    }

    /**
     * @return string
     */
    public function getAllowedIps()
    {
        return isset($this->settings['auth_ip'])
            ? (string) $this->settings['auth_ip']
            : '';
    }

    /**
     * @return bool
     */
    public function isAuthKeyRequired()
    {
        return isset($this->settings['auth_requires_auth_code'])
            ? (bool) $this->settings['auth_requires_auth_code']
            : false;
    }
}
