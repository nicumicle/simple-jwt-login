<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;
use SimpleJWTLogin\Services\BaseService;

class LoginSettings extends BaseSettings implements SettingsInterface
{
    const JWT_LOGIN_BY_EMAIL = 0;
    const JWT_LOGIN_BY_WORDPRESS_USER_ID = 1;
    const JWT_LOGIN_BY_USER_LOGIN = 2;

    const REDIRECT_DASHBOARD = 0;
    const REDIRECT_HOMEPAGE = 1;
    const REDIRECT_CUSTOM = 9;
    const NO_REDIRECT = 10;

    protected function getSectionKey()
    {
        return 'login';
    }

    protected function getFieldDefinitions()
    {
        return [
            [null, 'login_by',                   null, 'jwt_login_by',                     self::SETTINGS_TYPE_INT],
            [null, 'login_by_parameter',         null, 'jwt_login_by_parameter',           self::SETTINGS_TYPE_STRING],
            [null, 'enabled',                    null, 'allow_autologin',                  self::SETTINGS_TYPE_BOL],
            [null, 'fail_redirect',              null, 'login_fail_redirect',              self::SETTINGS_TYPE_STRING],
            [null, 'auth_code',                  null, 'require_login_auth',               self::SETTINGS_TYPE_BOL],
            [null, 'include_request_parameters', null, 'include_login_request_parameters', self::SETTINGS_TYPE_BOL, false],
            [null, 'allow_redirect_parameter',   null, 'allow_usage_redirect_parameter',   self::SETTINGS_TYPE_BOL, false],
            [null, 'remove_request_parameters',  null, 'login_remove_request_parameters',  self::SETTINGS_TYPE_STRING, null],
            [null, 'ip_whitelist',               null, 'login_ip',                         self::SETTINGS_TYPE_STRING],
            [null, 'iss_whitelist',              null, 'login_iss',                        self::SETTINGS_TYPE_STRING],
        ];
    }

    public function initSettingsFromPost()
    {
        parent::initSettingsFromPost();

        if (isset($this->post['redirect'])) {
            $this->settings['redirect'] = (int)$this->post['redirect'];
            $this->settings['redirect_url'] = (int)$this->post['redirect'] === self::REDIRECT_CUSTOM
            && isset($this->post['redirect_url'])
                ? (string)$this->post['redirect_url']
                : '';
        }
    }

    public function validateSettings()
    {
        if (isset($this->post['allow_autologin']) && (int)$this->post['allow_autologin'] === 1
            && (!isset($this->post['jwt_login_by_parameter']) || empty(trim($this->post['jwt_login_by_parameter'])))
        ) {
            throw new Exception(
                esc_html__('JWT Parameter key from LoginSettings Config is missing.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_GENERAL,
                    SettingsErrors::ERR_LOGIN_MISSING_JWT_PARAMETER_KEY
                ))
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
                esc_html__('Invalid custom URL provided.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_LOGIN,
                    SettingsErrors::ERR_LOGIN_INVALID_CUSTOM_URL
                ))
            );
        }
    }

    /**
     * @return bool
     */
    public function isAutologinEnabled()
    {
        return !empty($this->settings['enabled']);
    }

    /**
     * @return int
     */
    public function getJWTLoginBy()
    {
        return isset($this->settings['login_by'])
            ? (int)$this->settings['login_by']
            : self::JWT_LOGIN_BY_EMAIL;
    }

    /**
     * @return string
     * @since v.1.2.0
     */
    public function getJwtLoginByParameter()
    {
        return isset($this->settings['login_by_parameter'])
            ? $this->settings['login_by_parameter']
            : '';
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

    /**
     * @return bool
     */
    public function isRequestParametersIncluded()
    {
        return isset($this->settings['include_request_parameters'])
            ? (bool)$this->settings['include_request_parameters']
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
        return isset($this->settings['ip_whitelist'])
            ? $this->settings['ip_whitelist']
            : '';
    }

    /**
     * @return string
     */
    public function getAllowedLoginIss()
    {
        return isset($this->settings['iss_whitelist'])
            ? $this->settings['iss_whitelist']
            : '';
    }

    /**
     * @return bool
     */
    public function isAuthKeyRequiredOnLogin()
    {
        return !empty($this->settings['auth_code']);
    }

    /**
     * @return boolean
     */
    public function isRedirectParameterAllowed()
    {
        return !empty($this->settings['allow_redirect_parameter']);
    }

    /**
     * @return string
     */
    public function getAutologinRedirectOnFail()
    {
        return isset($this->settings['fail_redirect'])
            ? (string) $this->settings['fail_redirect']
            : '';
    }

    /**
     * @return string
     */
    public function getDangerousQueryParameters()
    {
        $default = [
            'rest_route',
            'jwt',
            'JWT',
            'email',
            'password',
            BaseService::REDIRECT_URL_PARAMETER,
        ];

        if (isset($this->fullSettings['auth_codes']['key'])) {
            $default[] = $this->fullSettings['auth_codes']['key'];
        }

        return isset($this->settings['remove_request_parameters'])
            ? (string) $this->settings['remove_request_parameters']
            : implode(', ', $default);
    }
}
