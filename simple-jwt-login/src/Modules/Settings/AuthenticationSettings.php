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
    const JWT_PAYLOAD_PARAM_ISS = 'iss';

    /**
     * @var string[]
     */
    public static $protectedPayloadKeys = array('iat', 'exp', 'email', 'id', 'site', 'username', 'iss');

    /**
     * @var string[]
     */
    public static $protectedHeaderKeys  = array('typ', 'alg', 'kid');

    protected function getSectionKey()
    {
        return 'authorization';
    }

    protected function getFieldDefinitions()
    {
        return [
            [null, 'enabled',                null, 'allow_authentication',        self::SETTINGS_TYPE_STRING],
            [null, 'jwt_payload',            null, 'jwt_payload',                 self::SETTINGS_TYPE_ARRAY],
            [null, 'ttl',                    null, 'jwt_auth_ttl',                self::SETTINGS_TYPE_STRING],
            [null, 'refresh_ttl',            null, 'jwt_auth_refresh_ttl',        self::SETTINGS_TYPE_STRING],
            [null, 'ip_whitelist',           null, 'auth_ip',                     self::SETTINGS_TYPE_STRING],
            [null, 'auth_code',              null, 'auth_requires_auth_code',     self::SETTINGS_TYPE_BOL],
            [null, 'password_base64',        null, 'auth_password_base64',        self::SETTINGS_TYPE_BOL, false],
            [null, 'password_hash_enabled',  null, 'auth_password_hash_enabled',  self::SETTINGS_TYPE_BOL, false],
            [null, 'iss',                    null, 'jwt_auth_iss',                self::SETTINGS_TYPE_STRING],
            [null, 'refresh_token_enabled',  null, 'allow_refresh_token',         self::SETTINGS_TYPE_STRING],
            [null, 'refresh_token_key',      null, 'refresh_token_key',           self::SETTINGS_TYPE_STRING],
            [null, 'validate_token_enabled', null, 'allow_validate_token',        self::SETTINGS_TYPE_STRING],
            [null, 'revoke_token_enabled',   null, 'allow_revoke_token',          self::SETTINGS_TYPE_STRING],
            [null, 'refresh_auth_code',      null, 'refresh_requires_auth_code',  self::SETTINGS_TYPE_BOL],
            [null, 'validate_auth_code',     null, 'validate_requires_auth_code', self::SETTINGS_TYPE_BOL],
            [null, 'revoke_auth_code',       null, 'revoke_requires_auth_code',   self::SETTINGS_TYPE_BOL],
            ['custom_claims', 'payload', null, 'custom_claims_payload',           self::SETTINGS_TYPE_ARRAY],
            ['custom_claims', 'header',  null, 'custom_claims_header',            self::SETTINGS_TYPE_ARRAY],
        ];
    }

    /**
     * @throws Exception
     */
    public function validateSettings()
    {
        $this->validateCustomClaimKeys(
            'custom_claims_payload',
            self::$protectedPayloadKeys,
            SettingsErrors::ERR_AUTHENTICATION_CUSTOM_CLAIM_PROTECTED_PAYLOAD
        );
        $this->validateCustomClaimKeys(
            'custom_claims_header',
            self::$protectedHeaderKeys,
            SettingsErrors::ERR_AUTHENTICATION_CUSTOM_CLAIM_PROTECTED_HEADER
        );

        if (!isset($this->post['allow_authentication'])) {
            return;
        }
        if ((int)$this->post['allow_authentication'] === 1
            && empty($this->post['jwt_payload'])
        ) {
            throw new Exception(
                esc_html__(
                    'Authentication payload data can not be empty. Please choose the ones you want to be added in the JWT.',
                    'simple-jwt-login'
                ),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_EMPTY_PAYLOAD
                ))
            );
        }

        if (!isset($this->post['jwt_auth_ttl'])
            || empty((int)$this->post['jwt_auth_ttl'])
            || (int)$this->post['jwt_auth_ttl'] < 0
        ) {
            throw new Exception(
                esc_html__(
                    'Authentication JWT time to live should be greater than zero.',
                    'simple-jwt-login'
                ),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_TTL
                ))
            );
        }

        if (isset($this->post['allow_refresh_token'])
            && (int)$this->post['allow_refresh_token'] === 1
        ) {
            if (!isset($this->post['jwt_auth_refresh_ttl'])
                || empty((int)$this->post['jwt_auth_refresh_ttl'])
                || (int)$this->post['jwt_auth_refresh_ttl'] < 0
            ) {
                throw new Exception(
                    esc_html__(
                        'Authentication JWT Refresh time to live should be greater than zero.',
                        'simple-jwt-login'
                    ),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_REFRESH_TOKEN,
                        SettingsErrors::ERR_AUTHENTICATION_REFRESH_TTL_ZERO
                    ))
                );
            }

            if (!isset($this->post['refresh_token_key'])
                || empty(trim($this->post['refresh_token_key']))
            ) {
                throw new Exception(
                    esc_html__('Refresh Token Secret Key is required.', 'simple-jwt-login'),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_REFRESH_TOKEN,
                        SettingsErrors::ERR_AUTHENTICATION_REFRESH_TOKEN_KEY_REQUIRED
                    ))
                );
            }
        }
    }

    /**
     * @param string $postKey
     * @param array  $protectedKeys
     * @param int    $errorCode
     * @throws Exception
     */
    private function validateCustomClaimKeys($postKey, $protectedKeys, $errorCode)
    {
        if (!isset($this->post[$postKey]['key'])) {
            return;
        }
        foreach ($this->post[$postKey]['key'] as $claimKey) {
            if (empty(trim($claimKey))) {
                throw new Exception(
                    esc_html__('Custom claim key cannot be empty.', 'simple-jwt-login'),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTHENTICATION,
                        SettingsErrors::ERR_AUTHENTICATION_CUSTOM_CLAIM_EMPTY_KEY
                    ))
                );
            }
            if (in_array($claimKey, $protectedKeys, true)) {
                throw new Exception(
                    esc_html(
                        sprintf(
                            /* translators: %s: JWT claim key name */
                            __(
                                'Custom claim key "%s" is a reserved JWT claim and cannot be overwritten.',
                                'simple-jwt-login'
                            ),
                            $claimKey
                        )
                    ),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTHENTICATION,
                        $errorCode
                    ))
                );
            }
        }
    }

    /**
     * @return bool
     */
    public function isAuthenticationEnabled()
    {
        return !empty($this->settings['enabled']);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isPayloadDataEnabled($name)
    {
        return !empty($this->settings['jwt_payload'])
            && in_array($name, $this->settings['jwt_payload'], true);
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
            self::JWT_PAYLOAD_PARAM_USERNAME,
            self::JWT_PAYLOAD_PARAM_ISS,
        ];
    }

    /**
     * @return int
     */
    public function getAuthJwtTtl()
    {
        return isset($this->settings['ttl'])
            ? (int)$this->settings['ttl']
            : 60;
    }

    /**
     * @return int
     */
    public function getAuthJwtRefreshTtl()
    {
        return isset($this->settings['refresh_ttl'])
            ? (int)$this->settings['refresh_ttl']
            : 20160;
    }

    /**
     * @return string
     */
    public function getAuthIss()
    {
        return isset($this->settings['iss'])
            ? (string)$this->settings['iss']
            : $this->wordPressData->getSiteUrl();
    }

    /**
     * @return string
     */
    public function getAllowedIps()
    {
        return isset($this->settings['ip_whitelist'])
            ? (string) $this->settings['ip_whitelist']
            : '';
    }

    /**
     * @return bool
     */
    public function isAuthKeyRequired()
    {
        return isset($this->settings['auth_code'])
            ? (bool) $this->settings['auth_code']
            : false;
    }

    /**
     * @return bool
     */
    public function isAuthPasswordBase64Encoded()
    {
        return isset($this->settings['password_base64'])
            ? (bool) $this->settings['password_base64']
            : false;
    }

    /**
     * @return bool
     */
    public function isAuthPasswordHashAllowed()
    {
        return isset($this->settings['password_hash_enabled'])
            ? (bool) $this->settings['password_hash_enabled']
            : false;
    }

    /**
     * @return bool
     */
    public function isRefreshAuthKeyRequired()
    {
        return isset($this->settings['refresh_auth_code'])
            ? (bool) $this->settings['refresh_auth_code']
            : false;
    }

    /**
     * @return bool
     */
    public function isValidateAuthKeyRequired()
    {
        return isset($this->settings['validate_auth_code'])
            ? (bool) $this->settings['validate_auth_code']
            : false;
    }

    /**
     * @return bool
     */
    public function isRevokeAuthKeyRequired()
    {
        return isset($this->settings['revoke_auth_code'])
            ? (bool) $this->settings['revoke_auth_code']
            : false;
    }

    /**
     * @return bool
     */
    public function isRefreshTokenEnabled()
    {
        return !empty($this->settings['refresh_token_enabled']);
    }

    /**
     * @return string
     */
    public function getRefreshTokenKey()
    {
        return isset($this->settings['refresh_token_key'])
            ? $this->settings['refresh_token_key']
            : '';
    }

    /**
     * @return bool
     */
    public function isValidateTokenEnabled()
    {
        if (!isset($this->settings['validate_token_enabled'])) {
            return true;
        }
        return !empty($this->settings['validate_token_enabled']);
    }

    /**
     * @return bool
     */
    public function isRevokeTokenEnabled()
    {
        if (!isset($this->settings['revoke_token_enabled'])) {
            return true;
        }
        return !empty($this->settings['revoke_token_enabled']);
    }

    /**
     * @return array
     */
    public function getCustomPayloadClaims()
    {
        return $this->extractCustomClaims('payload');
    }

    /**
     * @return array
     */
    public function getCustomHeaderClaims()
    {
        return $this->extractCustomClaims('header');
    }

    /**
     * @param string $type
     * @return array
     */
    private function extractCustomClaims($type)
    {
        $keys   = isset($this->settings['custom_claims'][$type]['key'])
            ? $this->settings['custom_claims'][$type]['key']
            : array();
        $values = isset($this->settings['custom_claims'][$type]['value'])
            ? $this->settings['custom_claims'][$type]['value']
            : array();

        $result = array();
        foreach ($keys as $i => $claimKey) {
            if (!empty(trim($claimKey))) {
                $result[$claimKey] = isset($values[$i]) ? $values[$i] : '';
            }
        }
        return $result;
    }
}
