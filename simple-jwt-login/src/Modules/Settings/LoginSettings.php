<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class LoginSettings extends BaseSettings implements SettingsInterface
{
    const REDIRECT_URL_PARAMETER = 'redirectUrl';
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

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'login_by',
            null,
            'jwt_login_by',
            BaseSettings::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'login_by_parameter',
            null,
            'jwt_login_by_parameter',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'enabled',
            null,
            'allow_autologin',
            BaseSettings::SETTINGS_TYPE_BOL
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
            'fail_redirect',
            null,
            'login_fail_redirect',
            BaseSettings::SETTINGS_TYPE_STRING
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'auth_code',
            null,
            'require_login_auth',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'include_request_parameters',
            null,
            'include_login_request_parameters',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_redirect_parameter',
            null,
            'allow_usage_redirect_parameter',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'remove_request_parameters',
            null,
            'login_remove_request_parameters',
            BaseSettings::SETTINGS_TYPE_STRING,
            null
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'ip_whitelist',
            null,
            'login_ip',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'iss_whitelist',
            null,
            'login_iss',
            BaseSettings::SETTINGS_TYPE_STRING
        );
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
            'redirectUrl',
        ];

        if (isset($this->fullSettings['auth_codes']['key'])) {
            $default[] = $this->fullSettings['auth_codes']['key'];
        }

        return isset($this->settings['remove_request_parameters'])
            ? (string) $this->settings['remove_request_parameters']
            : implode(', ', $default);
    }
}
