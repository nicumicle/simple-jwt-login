<?php

namespace SimpleJWTLogin\Modules;

use Exception;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyWpConfig;
use SimpleJWTLogin\SettingsErrors;

class SimpleJWTLoginSettings
{
    const REVOKE_TOKEN_KEY = 'simple_jwt_login_revoked_token';
    const OPTIONS_KEY = 'simple_jwt_login_settings';
    const DEFAULT_AUTH_CODE_KEY = 'AUTH_KEY';
    const DEFAULT_USER_PROFILE = 'subscriber';
    const REDIRECT_URL_PARAMETER = 'redirectUrl';

    const REDIRECT_DASHBOARD = 0;
    const REDIRECT_HOMEPAGE = 1;
    const REDIRECT_CUSTOM = 9;
    const NO_REDIRECT = 10;

    const JWT_LOGIN_BY_EMAIL = 0;
    const JWT_LOGIN_BY_WORDPRESS_USER_ID = 1;
    const JWT_LOGIN_BY_USER_LOGIN = 2;

    const SETTINGS_TYPE_INT = 0;
    const SETTINGS_TYPE_BOL = 1;
    const SETTINGS_TYPE_STRING = 2;
    const SETTINGS_TYPE_ARRAY = 3;

    const DELETE_USER_BY_EMAIL = 0;
    const DELETE_USER_BY_ID = 1;
    const DEFAULT_ROUTE_NAMESPACE = 'simple-jwt-login/v1/';

    const JWT_PAYLOAD_PARAM_IAT = 'iat';
    const JWT_PAYLOAD_PARAM_EXP = 'exp';
    const JWT_PAYLOAD_PARAM_EMAIL = 'email';
    const JWT_PAYLOAD_PARAM_ID = 'id';
    const JWT_PAYLOAD_PARAM_SITE = 'site';
    const JWT_PAYLOAD_PARAM_USERNAME = 'username';
    const DECRYPTION_SOURCE_SETTINGS = '0';
    const DECRYPTION_SOURCE_CODE = '1';

    /**
     * @var null|array
     */
    private $settings = null;

    /**
     * @var array
     */
    private $post;

    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    /**
     * @var boolean
     */
    private $needUpdateOnOptions = false;

    /**
     * SimpleJWTLoginSettings constructor.
     *
     * @param WordPressDataInterface $wordPressData
     */
    public function __construct(WordPressDataInterface $wordPressData)
    {
        $this->wordPressData = $wordPressData;
        $data = $this->wordPressData->getOptionFromDatabase(self::OPTIONS_KEY);
        $this->settings = json_decode($data, true);
        $this->needUpdateOnOptions = $data !== false;

        $this->post = [];
    }

    /**
     * @return WordPressDataInterface
     */
    public function getWordPressData()
    {
        return $this->wordPressData;
    }

    /**
     * This function makes sure that when save is pressed, all the data is saved
     *
     * @param array $post
     *
     * @return bool|void
     * @throws Exception
     */
    public function watchForUpdates($post)
    {
        if (empty($post)) {
            return false;
        }
        $this->post = $post;
        $this->initGeneralSettingsFromPost();
        $this->initLoginConfigFromPost();
        $this->initRegisterConfigFromPost();
        $this->initAuthCodesFromPost();
        $this->initDeleteUserConfigFromPost();
        $this->initHooksConfigFromPost();
        $this->initAuthenticationFromPost();
        $this->initCorsFromPost();

        $this->saveSettingsInDatabase();

        return true;
    }

    private function initAuthenticationFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_authentication',
            null,
            'allow_authentication',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(null, 'jwt_payload', null, 'jwt_payload', self::SETTINGS_TYPE_ARRAY);
        $this->assignSettingsPropertyFromPost(null, 'jwt_auth_ttl', null, 'jwt_auth_ttl', self::SETTINGS_TYPE_STRING);
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_auth_refresh_ttl',
            null,
            'jwt_auth_refresh_ttl',
            self::SETTINGS_TYPE_STRING
        );
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws Exception
     */
    private function validateAuthenticationConfigFromPost()
    {
        if ((int)$this->post['allow_authentication'] === 1 && (empty($this->post['jwt_payload']))) {
            throw new Exception(
                __(
                    'Authentication payload data can not be empty.'
                    . ' Please choose the ones you want to be added in the JWT.',
                    'simple-jwt-login'
                ),
                SettingsErrors::generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_EMPTY_PAYLOAD
                )
            );
        }

        if (empty((int)$this->post['jwt_auth_ttl']) || (int)$this->post['jwt_auth_ttl'] < 0) {
            throw new Exception(
                __(
                    'Authentication JWT time to live should be greater than zero.',
                    'simple-jwt-login'
                ),
                SettingsErrors::generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_TTL
                )
            );
        }

        if (empty((int)$this->post['jwt_auth_refresh_ttl']) || (int)$this->post['jwt_auth_refresh_ttl'] < 0) {
            throw new Exception(
                __(
                    'Authentication JWT Refresh time to live should be greater than zero.',
                    'simple-jwt-login'
                ),
                SettingsErrors::generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_REFRESH_TTL_ZERO
                )
            );
        }
    }


    private function initHooksConfigFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'enabled_hooks',
            null,
            'enabled_hooks',
            self::SETTINGS_TYPE_ARRAY
        );
    }

    private function initGeneralSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'route_namespace',
            null,
            'route_namespace',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_algorithm',
            null,
            'jwt_algorithm',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'decryption_source',
            null,
            'decryption_source',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'decryption_key',
            null,
            'decryption_key',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'decryption_key_base64',
            null,
            'decryption_key_base64',
            self::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'decryption_key_public',
            null,
            'decryption_key_public',
            self::SETTINGS_TYPE_STRING,
            null,
            true
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'decryption_key_private',
            null,
            'decryption_key_private',
            self::SETTINGS_TYPE_STRING,
            null,
            true
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'request_jwt_url',
            null,
            'request_jwt_url',
            self::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'request_jwt_cookie',
            null,
            'request_jwt_cookie',
            self::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'request_jwt_header',
            null,
            'request_jwt_header',
            self::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'request_jwt_session',
            null,
            'request_jwt_session',
            self::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            'api_middleware',
            'enabled',
            'api_middleware',
            'enabled',
            self::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            'request_keys',
            'url',
            'request_keys',
            'url',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            'request_keys',
            'session',
            'request_keys',
            'session',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            'request_keys',
            'cookie',
            'request_keys',
            'cookie',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            'request_keys',
            'header',
            'request_keys',
            'header',
            self::SETTINGS_TYPE_STRING
        );
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws Exception
     */
    private function validateGeneralSettingsFromPost()
    {
        if (!isset($this->post['route_namespace'])
            || isset($this->post['route_namespace'])
            && empty(trim(
                $this->post['route_namespace'],
                ' /'
            ))) {
            throw new Exception(
                __('Route namespace could not be empty.', 'simple-jwt-login'),
                SettingsErrors::generateCode(
                    SettingsErrors::PREFIX_GENERAL,
                    SettingsErrors::ERR_GENERAL_EMPTY_NAMESPACE
                )
            );
        }
        if (isset($this->post['request_keys'])) {
            if (empty($this->post['request_keys']['url'])
                || empty($this->post['request_keys']['session'])
                || empty($this->post['request_keys']['cookie'])
                || empty($this->post['request_keys']['header'])
            ) {
                throw new Exception(
                    __('Request Keys are required', 'simple-jwt-login'),
                    SettingsErrors::generateCode(
                        SettingsErrors::PREFIX_GENERAL,
                        SettingsErrors::ERR_GENERAL_REQUEST_KEYS
                    )
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
                        __('Public or private key is not defined in code.', 'simple-jwt-login'),
                        SettingsErrors::generateCode(
                            SettingsErrors::PREFIX_GENERAL,
                            SettingsErrors::ERR_GENERAL_PRIVATE_KEY_MISSING_FROM_CODE_RS
                        )
                    );
                } elseif (!defined(JwtKeyWpConfig::SIMPLE_JWT_PRIVATE_KEY)) {
                    throw new Exception(
                        __('Private key is not defined in code.', 'simple-jwt-login'),
                        SettingsErrors::generateCode(
                            SettingsErrors::PREFIX_GENERAL,
                            SettingsErrors::ERR_GENERAL_PRIVATE_KEY_NOT_PRESENT_IN_CODE_HS
                        )
                    );
                }
            }
            if (strpos($this->post['jwt_algorithm'], 'RS') !== false) {
                if (!isset($this->post['decryption_key_public'])
                    || empty(trim($this->post['decryption_key_public']))
                    || !isset($this->post['decryption_key_private'])
                    || empty(trim($this->post['decryption_key_private']))
                ) {
                    throw  new Exception(
                        __('JWT Decryption public and private key are required.', 'simple-jwt-login'),
                        SettingsErrors::generateCode(
                            SettingsErrors::PREFIX_GENERAL,
                            SettingsErrors::ERR_GENERAL_MISSING_PRIVATE_AND_PUBLIC_KEY
                        )
                    );
                }
            } elseif (!isset($this->post['decryption_key']) || empty(trim($this->post['decryption_key']))) {
                throw  new Exception(
                    __('JWT Decryption key is required.', 'simple-jwt-login'),
                    SettingsErrors::generateCode(
                        SettingsErrors::PREFIX_GENERAL,
                        SettingsErrors::ERR_GENERAL_DECRYPTION_KEY_REQUIRED
                    )
                );
            }
        }

        if (empty($this->post['request_jwt_url'])
            && empty($this->post['request_jwt_cookie'])
            && empty($this->post['request_jwt_header'])
            && empty($this->post['request_jwt_session'])
        ) {
            throw new Exception(
                __('You have to have at least on option enabled in \'Get JWT token From\'', 'simple-jwt-login'),
                SettingsErrors::generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_GET_JWT_FROM)
            );
        }
    }

    private function initAuthCodesFromPost()
    {
        $authCodes = [];
        if (isset($this->post['auth_codes']) && isset($this->post['auth_codes']['code'])) {
            $codes = $this->post['auth_codes']['code'];
            foreach ($codes as $key => $code) {
                if (trim($code) === ''
                    || !isset($this->post['auth_codes']['role'][$key])
                    || !isset($this->post['auth_codes']['expiration_date'][$key])
                ) {
                    continue;
                }
                $authCodes[] = [
                    'code' => $this->wordPressData->sanitize_text_field($code),
                    'role' => $this->wordPressData->sanitize_text_field($this->post['auth_codes']['role'][$key]),
                    'expiration_date' => $this->wordPressData->sanitize_text_field(
                        $this->post['auth_codes']['expiration_date'][$key]
                    )
                ];
            }
        }
        $this->settings['auth_codes'] = $authCodes;

        $this->assignSettingsPropertyFromPost(null, 'auth_code_key', null, 'auth_code_key', self::SETTINGS_TYPE_STRING);
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws Exception
     */
    private function validateAuthCodesFromPost()
    {
        $x = 1;
        if (!empty($this->settings['require_login_auth']) && !empty($this->settings['allow_autologin'])
            || !empty($this->settings['require_register_auth']) && !empty($this->settings['allow_register'])
            || !empty($this->settings['require_delete_auth']) && !empty($this->settings['allow_delete'])
        ) {
            if (empty($this->settings['auth_codes'])) {
                throw new Exception(
                    __('Missing Auth Codes. Please add at least one Auth Code.', 'simple-jwt-login'),
                    SettingsErrors::generateCode(
                        SettingsErrors::PREFIX_AUTH_CODES,
                        SettingsErrors::ERR_EMPTY_AUTH_CODES
                    )
                );
            }
        }
    }

    private function initLoginConfigFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_login_by',
            null,
            'jwt_login_by',
            self::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_login_by_parameter',
            null,
            'jwt_login_by_parameter',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_autologin',
            null,
            'allow_autologin',
            self::SETTINGS_TYPE_BOL
        );
        if (isset($this->post['redirect'])) {
            $this->settings['redirect'] = (int)$this->post['redirect'];
            $this->settings['redirect_url'] = (int)$this->post['redirect'] === self::REDIRECT_CUSTOM
            && isset($this->post['redirect_url'])
                ? (string)$this->post['redirect_url']
                : '';
        }

        $this->assignSettingsPropertyFromPost(
            null,
            'require_login_auth',
            null,
            'require_login_auth',
            self::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'include_login_request_parameters',
            null,
            'include_login_request_parameters',
            self::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_usage_redirect_parameter',
            null,
            'allow_usage_redirect_parameter',
            self::SETTINGS_TYPE_BOL,
            false
        );
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws Exception
     */
    private function validateLoginConfigFromPost()
    {
        if (isset($this->post['allow_autologin']) && (int)$this->post['allow_autologin'] === 1
            && (!isset($this->post['jwt_login_by_parameter']) || empty(trim($this->post['jwt_login_by_parameter'])))
        ) {
            throw  new Exception(
                __('JWT Parameter key from Login Config is missing.', 'simple-jwt-login'),
                SettingsErrors::generateCode(
                    SettingsErrors::PREFIX_LOGIN,
                    SettingsErrors::ERR_LOGIN_MISSING_JWT_PARAMETER_KEY
                )
            );
        }

        if (isset($this->post['redirect']) &&
            (int)$this->post['redirect'] === self::REDIRECT_CUSTOM &&
            (
                empty($this->post['redirect_url']) ||
                !filter_var($this->post['redirect_url'], FILTER_VALIDATE_URL)
            )
        ) {
            throw new Exception(
                __('Invalid custom URL provided', 'simple-jwt-login'),
                SettingsErrors::generateCode(SettingsErrors::PREFIX_LOGIN, SettingsErrors::ERR_LOGIN_INVALID_CUSTOM_URL)
            );
        }
    }

    private function initRegisterConfigFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_register',
            null,
            'allow_register',
            self::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'new_user_profile',
            null,
            'new_user_profile',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'login_ip',
            null,
            'login_ip',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'register_ip',
            null,
            'register_ip',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'register_domain',
            null,
            'register_domain',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'require_register_auth',
            null,
            'require_register_auth',
            self::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'allowed',
            null,
            'require_register_auth',
            self::SETTINGS_TYPE_BOL
        );
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws Exception
     */
    private function validateRegisterUser()
    {
        if (empty($this->post['new_user_profile'])) {
            throw new Exception(
                __('New User profile slug can not be empty.', 'simple-jwt-login'),
                SettingsErrors::generateCode(
                    SettingsErrors::PREFIX_REGISTER,
                    SettingsErrors::ERR__REGISTER_MISSING_NEW_USER_PROFILE
                )
            );
        }
    }

    /**
     * @param null|string $settingsPropertyGroup
     * @param string $settingsProperty
     * @param null|string $postKeyGroup
     * @param string $postKey
     * @param string $type
     * @param null|mixed $defaultValue
     * @param null|bool $base64Encode
     */
    private function assignSettingsPropertyFromPost(
        $settingsPropertyGroup,
        $settingsProperty,
        $postKeyGroup,
        $postKey,
        $type = null,
        $defaultValue = null,
        $base64Encode = null
    ) {
        $posTKeyExists = $postKeyGroup !== null
            ? isset($this->post[$postKeyGroup]) && isset($this->post[$postKeyGroup][$postKey])
            : isset($this->post[$postKey]);


        if ($type === self::SETTINGS_TYPE_ARRAY && !$posTKeyExists && $defaultValue === null) {
            $defaultValue = [];
        }

        if ($posTKeyExists) {
            $postValue = $postKeyGroup !== null
                ? $this->post[$postKeyGroup][$postKey]
                : $this->post[$postKey];
            switch ($type) {
                case self::SETTINGS_TYPE_INT:
                    $value = intval($postValue);
                    break;
                case self::SETTINGS_TYPE_BOL:
                    $value = (bool)$postValue;
                    break;
                case self::SETTINGS_TYPE_STRING:
                    $value = $base64Encode
                        ? base64_encode($postValue)
                        : $this->wordPressData->sanitize_text_field($postValue);
                    break;
                case self::SETTINGS_TYPE_ARRAY:
                    $value = (array)$postValue;
                    break;
                default:
                    $value = $postValue;
                    break;
            }
            if ($settingsPropertyGroup !== null) {
                $this->settings[$settingsPropertyGroup][$settingsProperty] = $value;
            } elseif ($settingsPropertyGroup === null) {
                $this->settings[$settingsProperty] = $value;
            }
        } elseif ($defaultValue !== null) {
            $defaultValue = $base64Encode
                ? base64_encode($defaultValue)
                : $defaultValue;
            if ($settingsPropertyGroup !== null) {
                $this->settings[$settingsPropertyGroup][$settingsProperty] = $defaultValue;
            } elseif ($settingsPropertyGroup === null) {
                $this->settings[$settingsProperty] = $defaultValue;
            }
        }
    }

    /**
     * Save Data
     * @throws Exception
     */
    private function saveSettingsInDatabase()
    {
        $this->validateGeneralSettingsFromPost();
        $this->validateAuthCodesFromPost();
        $this->validateLoginConfigFromPost();
        $this->validateDeleteUserConfigFromPost();
        $this->validateRegisterUser();
        $this->validateAuthenticationConfigFromPost();
        $this->validateCorsFromPost();

        if ($this->needUpdateOnOptions) {
            return $this->wordPressData->update_option(self::OPTIONS_KEY, json_encode($this->settings));
        }

        return $this->wordPressData->add_option(self::OPTIONS_KEY, json_encode($this->settings));
    }

    /**
     * @return bool
     */
    public function getAllowAutologin()
    {
        return !empty($this->settings['allow_autologin']);
    }

    public function getAllowAuthentication()
    {
        return !empty($this->settings['allow_authentication']);
    }

    /**
     * @return bool
     */
    public function getAllowRegister()
    {
        return !empty($this->settings['allow_register']);
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
    public function getDecryptionKeyIsBase64Encoded()
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
     * @return int
     */
    public function getJWTLoginBy()
    {
        return isset($this->settings['jwt_login_by'])
            ? (int)$this->settings['jwt_login_by']
            : self::JWT_LOGIN_BY_EMAIL;
    }

    /**
     * @return string
     * @since v.1.2.0
     */
    public function getJwtLoginByParameter()
    {
        return isset($this->settings['jwt_login_by_parameter'])
            ? $this->settings['jwt_login_by_parameter']
            : $this->getOldVersionJWTEmailParameter();
    }

    /**
     * Return JWT parameter for old version plugins
     * @return string
     * @deprecated  since v 1.2.0
     */
    private function getOldVersionJWTEmailParameter()
    {
        return isset($this->settings['jwt_email_parameter'])
            ? $this->settings['jwt_email_parameter']
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
     * @return array
     */
    public function getAuthCodes()
    {
        return isset($this->settings['auth_codes'])
            ? $this->settings['auth_codes']
            : [];
    }

    /**
     * @return string
     */
    public function getAuthCodeKey()
    {
        return !empty($this->settings['auth_code_key'])
            ? $this->settings['auth_code_key']
            : self::DEFAULT_AUTH_CODE_KEY;
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
     * @return int
     */
    public function getRedirect()
    {
        return isset($this->settings['redirect'])
            ? (int)$this->settings['redirect']
            : self::REDIRECT_DASHBOARD;
    }

    public function getShouldIncludeRequestParameters()
    {
        return isset($this->settings['include_login_request_parameters'])
            ? (bool)$this->settings['include_login_request_parameters']
            : false;
    }

    /**
     * @return string
     */
    public function getCustomRedirectURL()
    {
        return isset($this->settings['redirect_url'])
            ? $this->settings['redirect_url']
            : '';
    }

    /**
     * @return string
     */
    public function getAllowedLoginIps()
    {
        return isset($this->settings['login_ip'])
            ? $this->settings['login_ip']
            : '';
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
    public function getRequireLoginAuthKey()
    {
        return isset($this->settings['require_login_auth'])
            ? (bool)$this->settings['require_login_auth']
            : false;
    }

    /**
     * @return bool
     */
    public function getRequireRegisterAuthKey()
    {
        return isset($this->settings['require_register_auth'])
            ? (bool)$this->settings['require_register_auth']
            : true;
    }

    /**
     * @param string $route
     * @param array $params
     *
     * @return string
     */
    public function generateExampleLink($route, $params)
    {
        $url = $this->wordPressData->getSiteUrl() . '/?rest_route=/' . $this->getRouteNamespace() . $route;
        foreach ($params as $key => $value) {
            $url .= sprintf('&amp;%s=<b>%s</b>', $key, $value);
        }

        return $url;
    }

    /**
     * @return bool
     */
    public function getAllowDelete()
    {
        return isset($this->settings['allow_delete'])
            ? (bool)$this->settings['allow_delete']
            : false;
    }

    /**
     * @return bool
     */
    public function getRequireDeleteAuthKey()
    {
        return isset($this->settings['require_delete_auth'])
            ? (bool)$this->settings['require_delete_auth']
            : true;
    }

    /**
     * @return string
     */
    public function getAllowedDeleteIps()
    {
        return isset($this->settings['delete_ip'])
            ? $this->settings['delete_ip']
            : '';
    }

    /**
     * @return int
     */
    public function getDeleteUserBy()
    {
        return isset($this->settings['delete_user_by'])
            ? (int)$this->settings['delete_user_by']
            : self::DELETE_USER_BY_EMAIL;
    }

    /**
     * @return mixed|string
     */
    public function getJwtDeleteByParameter()
    {
        return isset($this->settings['jwt_delete_by_parameter'])
            ? $this->settings['jwt_delete_by_parameter']
            : '';
    }

    /**
     * @return boolean
     */
    public function isRedirectParameterAllowed()
    {
        return isset($this->settings['allow_usage_redirect_parameter'])
            ? (bool)$this->settings['allow_usage_redirect_parameter']
            : false;
    }

    private function initDeleteUserConfigFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_delete',
            null,
            'allow_delete',
            self::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'require_delete_auth',
            null,
            'require_delete_auth',
            self::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'delete_ip',
            null,
            'delete_ip',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'allowed_user_meta',
            null,
            'allowed_user_meta',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'delete_user_by',
            null,
            'delete_user_by',
            self::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_delete_by_parameter',
            null,
            'jwt_delete_by_parameter',
            self::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'random_password',
            null,
            'random_password',
            self::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'register_force_login',
            null,
            'register_force_login',
            self::SETTINGS_TYPE_BOL,
            false
        );
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws Exception
     */
    private function validateDeleteUserConfigFromPost()
    {
        if (!empty($this->post['allow_delete'])
            && (
                !isset($this->post['jwt_delete_by_parameter'])
                || empty(trim($this->post['jwt_delete_by_parameter']))
            )
        ) {
            throw new Exception(
                __('Missing JWT parameter for Delete User.', 'simple-jwt-login'),
                SettingsErrors::generateCode(
                    SettingsErrors::PREFIX_DELETE,
                    SettingsErrors::ERR_DELETE_MISSING_JWT_PARAM
                )
            );
        }
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
    public function getJwtFromURLEnabled()
    {
        return isset($this->settings['request_jwt_url'])
            ? (bool)$this->settings['request_jwt_url']
            : true;
    }

    /**
     * @return bool
     */
    public function getJwtFromCookieEnabled()
    {
        return isset($this->settings['request_jwt_cookie'])
            ? (bool)$this->settings['request_jwt_cookie']
            : false;
    }

    /**
     * @return bool
     */
    public function getJwtFromHeaderEnabled()
    {
        return isset($this->settings['request_jwt_header'])
            ? (bool)$this->settings['request_jwt_header']
            : true;
    }

    /**
     * @return bool
     */
    public function getJwtFromSessionEnabled()
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
        return isset($this->settings['request_keys']) && isset($this->settings['request_keys']['url'])
            ? esc_html($this->settings['request_keys']['url'])
            : 'JWT';
    }

    /**
     * @return string
     */
    public function getRequestKeySession()
    {
        return isset($this->settings['request_keys']) && isset($this->settings['request_keys']['session'])
            ? esc_html($this->settings['request_keys']['session'])
            : 'simple-jwt-login-token';
    }

    /**
     * @return string
     */
    public function getRequestKeyCookie()
    {
        return isset($this->settings['request_keys']) && isset($this->settings['request_keys']['cookie'])
            ? esc_html($this->settings['request_keys']['cookie'])
            : 'simple-jwt-login-token';
    }

    /**
     * @return string
     */
    public function getRequestKeyHeader()
    {
        return isset($this->settings['request_keys']) && isset($this->settings['request_keys']['header'])
            ? esc_html($this->settings['request_keys']['header'])
            : 'Authorization';
    }

    /**
     * @return bool
     */
    public function getRandomPasswordForCreateUser()
    {
        return isset($this->settings['random_password'])
            ? (bool)$this->settings['random_password']
            : false;
    }

    /**
     * @return bool
     */
    public function getForceLoginAfterCreateUser()
    {
        return isset($this->settings['register_force_login'])
            ? (bool)$this->settings['register_force_login']
            : false;
    }

    /**
     * @return array
     */
    public function getEnabledHooks()
    {
        return isset($this->settings['enabled_hooks'])
            ? (array)$this->settings['enabled_hooks']
            : [];
    }

    /**
     * @param string $hookName
     *
     * @return bool
     */
    public function isHookEnable($hookName)
    {
        return !empty($this->settings['enabled_hooks'])
            && in_array($hookName, $this->settings['enabled_hooks']);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isPayloadDataEnabled($name)
    {
        return !empty($this->settings['jwt_payload'])
            && in_array($name, $this->settings['jwt_payload']);
    }

    /**
     * @return bool
     */
    public function isAuthenticationEnabled()
    {
        return !empty($this->settings['allow_authentication']);
    }

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
     * @return array
     */
    public function getSettingsAsArray()
    {
        return $this->settings;
    }

    /**
     * @return CorsService
     */
    public function getCors()
    {
        return new CorsService($this);
    }

    private function initCorsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            'cors',
            'enabled',
            'cors',
            'enabled',
            self::SETTINGS_TYPE_INT
        );

        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_origin_enabled',
            'cors',
            'allow_origin_enabled',
            self::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_origin',
            'cors',
            'allow_origin',
            self::SETTINGS_TYPE_STRING
        );

        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_methods_enabled',
            'cors',
            'allow_methods_enabled',
            self::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_methods',
            'cors',
            'allow_methods',
            self::SETTINGS_TYPE_STRING
        );

        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_headers_enabled',
            'cors',
            'allow_headers_enabled',
            self::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            'cors',
            'allow_headers',
            'cors',
            'allow_headers',
            self::SETTINGS_TYPE_STRING
        );
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws Exception
     */
    private function validateCorsFromPost()
    {
        if (!empty($this->post['cors']['enabled'])
            && (
                empty($this->settings['cors']['allow_origin_enabled'])
                && empty($this->settings['cors']['allow_methods_enabled'])
                && empty($this->settings['cors']['allow_headers_enabled'])
            )
        ) {
            throw  new Exception(
                __(
                    'Cors is enabled but no option is checked. Please check at least one option.',
                    'simple-jwt-login'
                ),
                SettingsErrors::generateCode(SettingsErrors::PREFIX_CORS, SettingsErrors::ERR_CORS_NO_OPTION)
            );
        }
    }

    /**
     * @return bool
     */
    public function isMiddlewareEnabled()
    {
        return isset($this->settings['api_middleware'])
            && isset($this->settings['api_middleware']['enabled'])
            && !empty($this->settings['api_middleware']['enabled']);
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
}
