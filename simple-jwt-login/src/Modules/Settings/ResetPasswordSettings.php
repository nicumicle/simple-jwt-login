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

    protected function getSectionKey()
    {
        return 'reset_password';
    }

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'enabled',
            null,
            'allow_reset_password',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'auth_code',
            null,
            'reset_password_requires_auth_code',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'flow',
            null,
            'jwt_reset_password_flow',
            BaseSettings::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'email_subject',
            null,
            'jwt_email_subject',
            BaseSettings::SETTINGS_TYPE_STRING
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'email_body',
            null,
            'jwt_reset_password_email_body',
            BaseSettings::SETTINGS_TYPE_STRING,
            '',
            true
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'email_type',
            null,
            'jwt_email_type',
            BaseSettings::SETTINGS_TYPE_INT
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'return_jwt',
            null,
            'reset_password_jwt',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'send_password_changed_email',
            null,
            'reset_password_send_changed_email',
            BaseSettings::SETTINGS_TYPE_BOL
        );
    }

    /**
     * @throws Exception
     */
    public function validateSettings()
    {
        if (!$this->isResetPasswordEnabled()) {
            return;
        }
        switch ($this->getFlowType()) {
            case self::FLOW_SEND_CUSTOM_EMAIL:
                if (strpos($this->getResetPasswordEmailBody(), '{{CODE}}') === false) {
                    throw new Exception(
                        esc_html__('You need to add the {{CODE}} variable in email body.', 'simple-jwt-login'),
                        absint($this->settingsErrors->generateCode(
                            SettingsErrors::PREFIX_RESET_PASSWORD,
                            ErrorCodes::ERR_MISSING_CODE_FROM_EMAIL_BODY
                        ))
                    );
                }
                if ($this->getResetPasswordEmailSubject() === '') {
                    throw new Exception(
                        esc_html__('The Reset Password custom email subject is empty.', 'simple-jwt-login'),
                        absint($this->settingsErrors->generateCode(
                            SettingsErrors::PREFIX_RESET_PASSWORD,
                            ErrorCodes::ERR_EMPTY_CUSTOM_EMAIL_SUBJECT
                        ))
                    );
                }
                break;
        }
    }

    /**
     * @return bool
     */
    public function isResetPasswordEnabled()
    {
        return !empty($this->settings['enabled']);
    }

    /**
     * @return bool
     */
    public function isAuthKeyRequired()
    {
        return !empty($this->settings['auth_code']);
    }

    /**
     * @return bool
     */
    public function isJwtAllowed()
    {
        return !empty($this->settings['return_jwt']);
    }

    /**
     * @return bool
     */
    public function shouldSendPasswordChangedEmail()
    {
        return !empty($this->settings['send_password_changed_email']);
    }

    /**
     * @return int
     */
    public function getFlowType()
    {
        return isset($this->settings['flow'])
            ? $this->settings['flow']
            : self::FLOW_JUST_SAVE_IN_DB;
    }

    /**
     * @return string
     */
    public function getResetPasswordEmailSubject()
    {
        return isset($this->settings['email_subject'])
            ? $this->settings['email_subject']
            : '';
    }

    /**
     * @return string
     */
    public function getResetPasswordEmailBody()
    {
        return isset($this->settings['email_body'])
            ? base64_decode($this->settings['email_body'])
            : '';
    }

    /**
     * @return int
     */
    public function getResetPasswordEmailType()
    {
        return isset($this->settings['email_type'])
            ? $this->settings['email_type']
            : self::EMAIL_TYPE_PLAIN_TEXT;
    }

    public function getEmailContentVariables()
    {
        return [
            '{{CODE}}' => __('Reset password code', 'simple-jwt-login'),
            '{{NAME}}' => __('User first and last name', 'simple-jwt-login'),
            '{{USERNAME}}' => __('User name', 'simple-jwt-login'),
            '{{EMAIL}}' => __('User email', 'simple-jwt-login'),
            '{{NICKNAME}}' => __('User nickname', 'simple-jwt-login'),
            '{{FIRST_NAME}}' => __('User first name', 'simple-jwt-login'),
            '{{LAST_NAME}}' => __('User last name', 'simple-jwt-login'),
            '{{SITE}}' => __('Website URL', 'simple-jwt-login'),
            '{{IP}}' => __('Client IP address', 'simple-jwt-login')
        ];
    }
}
