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

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_login_by',
            null,
            'jwt_login_by',
            BaseSettings::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_login_by_parameter',
            null,
            'jwt_login_by_parameter',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_autologin',
            null,
            'allow_autologin',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        if (isset($this->post['redirect'])) {
            $this->settings['redirect'] = (int)$this->post['redirect'];
            $this->settings['redirect_url'] = (int)$this->post['redirect'] === LoginSettings::REDIRECT_CUSTOM
            && isset($this->post['redirect_url'])
                ? (string)$this->post['redirect_url']
                : '';
        }

        $this->assignSettingsPropertyFromPost(
            null,
            'require_login_auth',
            null,
            'require_login_auth',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'include_login_request_parameters',
            null,
            'include_login_request_parameters',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_usage_redirect_parameter',
            null,
            'allow_usage_redirect_parameter',
            BaseSettings::SETTINGS_TYPE_BOL,
            false
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'login_ip',
            null,
            'login_ip',
            BaseSettings::SETTINGS_TYPE_STRING
        );
    }

    public function validateSettings()
    {
        if (isset($this->post['allow_autologin']) && (int)$this->post['allow_autologin'] === 1
            && (!isset($this->post['jwt_login_by_parameter']) || empty(trim($this->post['jwt_login_by_parameter'])))
        ) {
            throw  new Exception(
                __('JWT Parameter key from LoginSettings Config is missing.', 'simple-jwt-login'),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_LOGIN,
                    SettingsErrors::ERR_LOGIN_MISSING_JWT_PARAMETER_KEY
                )
            );
        }

        if (isset($this->post['redirect']) &&
            (int)$this->post['redirect'] === LoginSettings::REDIRECT_CUSTOM &&
            (
                empty($this->post['redirect_url']) ||
                !filter_var($this->post['redirect_url'], FILTER_VALIDATE_URL)
            )
        ) {
            throw new Exception(
                __('Invalid custom URL provided.', 'simple-jwt-login'),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_LOGIN,
                    SettingsErrors::ERR_LOGIN_INVALID_CUSTOM_URL
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isAutologinEnabled()
    {
        return !empty($this->settings['allow_autologin']);
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
     * @return bool
     */
    public function isAuthKeyRequiredOnLogin()
    {
        return isset($this->settings['require_login_auth'])
            ? (bool)$this->settings['require_login_auth']
            : false;
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
}
