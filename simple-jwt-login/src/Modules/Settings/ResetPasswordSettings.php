<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;
use SimpleJWTLogin\ErrorCodes;

class ResetPasswordSettings extends BaseSettings implements SettingsInterface
{
    const FLOW_JUST_SAVE_IN_DB = 0;
    const FLOW_SEND_DEFAULT_WP_EMAIL = 1;
    const FLOW_SEND_CUSTOM_EMAIL = 2;
    const EMAIL_TYPE_PLAIN_TEXT = 0;
    const EMAIL_TYPE_HTML = 1;

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_reset_password',
            null,
            'allow_reset_password',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'reset_password_requires_auth_code',
            null,
            'reset_password_requires_auth_code',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_reset_password_flow',
            null,
            'jwt_reset_password_flow',
            BaseSettings::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_email_subject',
            null,
            'jwt_email_subject',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_reset_password_email_body',
            null,
            'jwt_reset_password_email_body',
            BaseSettings::SETTINGS_TYPE_STRING,
            '',
            true
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_email_type',
            null,
            'jwt_email_type',
            BaseSettings::SETTINGS_TYPE_INT
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'reset_password_jwt',
            null,
            'reset_password_jwt',
            BaseSettings::SETTINGS_TYPE_BOL
        );
    }

    /**
     * @throws Exception
     */
    public function validateSettings()
    {
        if ($this->isResetPasswordEnabled()
            && $this->getFlowType() == self::FLOW_SEND_CUSTOM_EMAIL
            && strpos($this->getResetPasswordEmailBody(), '{{CODE}}') === false
        ) {
            throw new Exception(
                __('You need to add the {{CODE}} variable in email body.', 'simple-jwt-login'),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_RESET_PASSWORD,
                    ErrorCodes::ERR_MISSING_CODE_FROM_EMAIL_BODY
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isResetPasswordEnabled()
    {
        return !empty($this->settings['allow_reset_password']);
    }

    /**
     * @return bool
     */
    public function isAuthKeyRequired()
    {
        return !empty($this->settings['reset_password_requires_auth_code']);
    }

    /**
     * @return bool
     */
    public function isJwtAllowed()
    {
        return !empty($this->settings['reset_password_jwt']);
    }

    /**
     * @return int
     */
    public function getFlowType()
    {
        return isset($this->settings['jwt_reset_password_flow'])
            ? $this->settings['jwt_reset_password_flow']
            : self::FLOW_JUST_SAVE_IN_DB;
    }

    /**
     * @return string
     */
    public function getResetPasswordEmailSubject()
    {
        return isset($this->settings['jwt_email_subject'])
            ? $this->settings['jwt_email_subject']
            : '';
    }

    /**
     * @return string
     */
    public function getResetPasswordEmailBody()
    {
        return isset($this->settings['jwt_reset_password_email_body'])
            ? base64_decode($this->settings['jwt_reset_password_email_body'])
            : '';
    }

    /**
     * @return int
     */
    public function getResetPasswordEmailType()
    {
        return isset($this->settings['jwt_email_type'])
            ? $this->settings['jwt_email_type']
            : self::EMAIL_TYPE_PLAIN_TEXT;
    }

    public function getEmailContentVariables()
    {
        return [
            '{{CODE}}' => __('Reset password code', 'simple-jwt_login'),
            '{{NAME}}' => __('User first and last name', 'simple-jwt-login'),
            '{{EMAIL}}' => __('User email', 'simple-jwt_login'),
            '{{NICKNAME}}' => __('User nickname', 'simple-jwt_login'),
            '{{FIRST_NAME}}' => __('User first name', 'simple-jwt_login'),
            '{{LAST_NAME}}' => __('User last name', 'simple-jwt_login'),
            '{{SITE}}' => __('Website URL', 'simple-jwt-login'),
            '{{IP}}' => __('Client IP address', 'simple-jwt-login')
        ];
    }
}
