<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyWpConfig;

class GeneralSettings extends BaseSettings implements SettingsInterface
{
    const DECRYPTION_SOURCE_SETTINGS = '0';
    const DECRYPTION_SOURCE_CODE = '1';

    const DEFAULT_ROUTE_NAMESPACE = 'simple-jwt-login/v1/';

    protected function getSectionKey()
    {
        return 'general';
    }

    protected function getFieldDefinitions()
    {
        return [
            [null, 'route_namespace',        null, 'route_namespace',        self::SETTINGS_TYPE_STRING],
            [null, 'jwt_algorithm',          null, 'jwt_algorithm',          self::SETTINGS_TYPE_STRING],
            [null, 'decryption_source',      null, 'decryption_source',      self::SETTINGS_TYPE_STRING],
            [null, 'decryption_key',         null, 'decryption_key',         self::SETTINGS_TYPE_STRING],
            [null, 'decryption_key_base64',  null, 'decryption_key_base64',  self::SETTINGS_TYPE_BOL, false],
            [null, 'decryption_key_public',  null, 'decryption_key_public',  self::SETTINGS_TYPE_STRING, null, true],
            [null, 'decryption_key_private', null, 'decryption_key_private', self::SETTINGS_TYPE_STRING, null, true],
            [null, 'request_jwt_url',        null, 'request_jwt_url',        self::SETTINGS_TYPE_INT],
            [null, 'request_jwt_cookie',     null, 'request_jwt_cookie',     self::SETTINGS_TYPE_INT],
            [null, 'request_jwt_header',     null, 'request_jwt_header',     self::SETTINGS_TYPE_INT],
            [null, 'request_jwt_session',    null, 'request_jwt_session',    self::SETTINGS_TYPE_INT],
            ['api_middleware', 'enabled', 'api_middleware', 'enabled',       self::SETTINGS_TYPE_BOL, false],
            ['request_keys', 'url',     'request_keys', 'url',               self::SETTINGS_TYPE_STRING],
            ['request_keys', 'session', 'request_keys', 'session',           self::SETTINGS_TYPE_STRING],
            ['request_keys', 'cookie',  'request_keys', 'cookie',            self::SETTINGS_TYPE_STRING],
            ['request_keys', 'header',  'request_keys', 'header',            self::SETTINGS_TYPE_STRING],
            [null, 'request_jwt_header_require_bearer', null, 'request_jwt_header_require_bearer', self::SETTINGS_TYPE_BOL, false],
            ['security', 'safe_redirect',    'security', 'safe_redirect',    self::SETTINGS_TYPE_BOL],
            ['security', 'trust_ip_headers', 'security', 'trust_ip_headers', self::SETTINGS_TYPE_BOL],
        ];
    }

    /**
     * @throws Exception
     */
    public function validateSettings()
    {
        if (!isset($this->post['route_namespace'])
            || empty(trim(
                $this->post['route_namespace'],
                ' /'
            ))) {
            throw new Exception(
                esc_html__('Route namespace could not be empty.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_GENERAL,
                    SettingsErrors::ERR_GENERAL_EMPTY_NAMESPACE
                ))
            );
        }
        if (isset($this->post['request_keys'])) {
            if (empty($this->post['request_keys']['url'])
                || empty($this->post['request_keys']['session'])
                || empty($this->post['request_keys']['cookie'])
                || empty($this->post['request_keys']['header'])
            ) {
                throw new Exception(
                    esc_html__('Request Keys are required.', 'simple-jwt-login'),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_GENERAL,
                        SettingsErrors::ERR_GENERAL_REQUEST_KEYS
                    ))
                );
            }
        }
        if (!empty($this->post['jwt_algorithm'])) {
            if (isset($this->post['decryption_source'])
                && $this->post['decryption_source'] === self::DECRYPTION_SOURCE_CODE
            ) {
                if (strpos($this->post['jwt_algorithm'], 'RS') !== false
                    && (!defined(JwtKeyWpConfig::SIMPLE_JWT_PUBLIC_KEY)
                        || !defined(JwtKeyWpConfig::SIMPLE_JWT_PRIVATE_KEY))
                ) {
                    throw new Exception(
                        esc_html__('Public or private key is not defined in code.', 'simple-jwt-login'),
                        absint($this->settingsErrors->generateCode(
                            SettingsErrors::PREFIX_GENERAL,
                            SettingsErrors::ERR_GENERAL_PRIVATE_KEY_MISSING_FROM_CODE_RS
                        ))
                    );
                }
                if (!defined(JwtKeyWpConfig::SIMPLE_JWT_PRIVATE_KEY)) {
                    throw new Exception(
                        esc_html__('Private key is not defined in code.', 'simple-jwt-login'),
                        absint($this->settingsErrors->generateCode(
                            SettingsErrors::PREFIX_GENERAL,
                            SettingsErrors::ERR_GENERAL_PRIVATE_KEY_NOT_PRESENT_IN_CODE_HS
                        ))
                    );
                }
            }
            if (isset($this->post['decryption_source']) && $this->post['decryption_source'] === self::DECRYPTION_SOURCE_SETTINGS) {
                if (strpos($this->post['jwt_algorithm'], 'RS') !== false
                    && (!isset($this->post['decryption_key_public'])
                        || empty(trim($this->post['decryption_key_public']))
                        || !isset($this->post['decryption_key_private'])
                        || empty(trim($this->post['decryption_key_private'])))
                ) {
                    throw new Exception(
                        esc_html__('Public Key and Private Key are required.', 'simple-jwt-login'),
                        absint($this->settingsErrors->generateCode(
                            SettingsErrors::PREFIX_GENERAL,
                            SettingsErrors::ERR_GENERAL_MISSING_PRIVATE_AND_PUBLIC_KEY
                        ))
                    );
                }
                if (strpos($this->post['jwt_algorithm'], 'RS') === false
                    && (!isset($this->post['decryption_key'])
                        || empty(trim($this->post['decryption_key'])))
                ) {
                    throw new Exception(
                        esc_html__('JWT Verification Key is required.', 'simple-jwt-login'),
                        absint($this->settingsErrors->generateCode(
                            SettingsErrors::PREFIX_GENERAL,
                            SettingsErrors::ERR_GENERAL_DECRYPTION_KEY_REQUIRED
                        ))
                    );
                }
            }
        }

        if (empty($this->post['request_jwt_url'])
            && empty($this->post['request_jwt_cookie'])
            && empty($this->post['request_jwt_header'])
            && empty($this->post['request_jwt_session'])
        ) {
            throw new Exception(
                esc_html__('You have to have at least one option enabled in \'JWT Input Sources\'', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_GENERAL,
                    SettingsErrors::ERR_GENERAL_GET_JWT_FROM
                ))
            );
        }
    }

    /**
     * @return string
     */
    public function getDecryptionSource()
    {
        return isset($this->settings['decryption_source'])
            ? (string)$this->settings['decryption_source']
            : self::DECRYPTION_SOURCE_SETTINGS;
    }

    /**
     * @return string
     */
    public function getDecryptionKey()
    {
        return isset($this->settings['decryption_key'])
            ? $this->settings['decryption_key']
            : '';
    }

    /**
     * @return bool
     */
    public function isDecryptionKeyBase64Encoded()
    {
        return isset($this->settings['decryption_key_base64'])
            ? (bool)$this->settings['decryption_key_base64']
            : false;
    }

    /**
     * @return string
     */
    public function getDecryptionKeyPublic()
    {
        return isset($this->settings['decryption_key_public'])
            ? base64_decode($this->settings['decryption_key_public'])
            : '';
    }

    /**
     * @return string
     */
    public function getDecryptionKeyPrivate()
    {
        return isset($this->settings['decryption_key_private'])
            ? base64_decode($this->settings['decryption_key_private'])
            : '';
    }

    /**
     * @return string
     */
    public function getJWTDecryptAlgorithm()
    {
        return isset($this->settings['jwt_algorithm'])
            ? $this->settings['jwt_algorithm']
            : 'HS256';
    }

    /**
     * @return string
     */
    public function getRouteNamespace()
    {
        $return = isset($this->settings['route_namespace'])
            ? $this->settings['route_namespace']
            : self::DEFAULT_ROUTE_NAMESPACE;

        return rtrim(ltrim($return, '/'), '/') . '/';
    }

    /**
     * @return bool
     */
    public function isJwtFromURLEnabled()
    {
        return isset($this->settings['request_jwt_url'])
            ? (bool)$this->settings['request_jwt_url']
            : true;
    }

    /**
     * @return bool
     */
    public function isJwtFromCookieEnabled()
    {
        return isset($this->settings['request_jwt_cookie'])
            ? (bool)$this->settings['request_jwt_cookie']
            : false;
    }

    /**
     * @return bool
     */
    public function isJwtFromHeaderEnabled()
    {
        return isset($this->settings['request_jwt_header'])
            ? (bool)$this->settings['request_jwt_header']
            : true;
    }

    /**
     * @return bool
     */
    public function isJwtFromSessionEnabled()
    {
        return isset($this->settings['request_jwt_session'])
            ? (bool)$this->settings['request_jwt_session']
            : false;
    }

    /**
     * @return string
     */
    public function getRequestKeyUrl()
    {
        return isset($this->settings['request_keys']['url'])
            ? esc_html($this->settings['request_keys']['url'])
            : 'JWT';
    }

    /**
     * @return string
     */
    public function getRequestKeySession()
    {
        return isset($this->settings['request_keys']['session'])
            ? esc_html($this->settings['request_keys']['session'])
            : 'simple-jwt-login-token';
    }

    /**
     * @return string
     */
    public function getRequestKeyCookie()
    {
        return isset($this->settings['request_keys']['cookie'])
            ? esc_html($this->settings['request_keys']['cookie'])
            : 'simple-jwt-login-token';
    }

    /**
     * @return string
     */
    public function getRequestKeyHeader()
    {
        return isset($this->settings['request_keys']['header'])
            ? esc_html($this->settings['request_keys']['header'])
            : 'Authorization';
    }

    /**
     * @return bool
     */
    public function isJwtFromHeaderBearerRequired()
    {
        return isset($this->settings['request_jwt_header_require_bearer'])
            ? (bool)$this->settings['request_jwt_header_require_bearer']
            : false;
    }

    /**
     * @return bool
     */
    public function isMiddlewareEnabled()
    {
        return !empty($this->settings['api_middleware']['enabled']);
    }

    public function isSafeRedirectEnabled()
    {
        return !empty($this->settings['security']['safe_redirect']);
    }

    /**
     * @return bool
     */
    public function isTrustIpHeadersEnabled()
    {
        return !empty($this->settings['security']['trust_ip_headers']);
    }
}
