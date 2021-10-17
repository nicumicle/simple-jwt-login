<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class DeleteUserSettings extends BaseSettings implements SettingsInterface
{
    const DELETE_USER_BY_EMAIL = 0;
    const DELETE_USER_BY_ID = 1;
    const DELETE_USER_BY_USER_LOGIN = 2;

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            'allow_delete',
            null,
            'allow_delete',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'require_delete_auth',
            null,
            'require_delete_auth',
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'delete_ip',
            null,
            'delete_ip',
            BaseSettings::SETTINGS_TYPE_STRING
        );

        $this->assignSettingsPropertyFromPost(
            null,
            'delete_user_by',
            null,
            'delete_user_by',
            BaseSettings::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            null,
            'jwt_delete_by_parameter',
            null,
            'jwt_delete_by_parameter',
            BaseSettings::SETTINGS_TYPE_STRING
        );
    }

    public function validateSettings()
    {
        if (!empty($this->settings['allow_delete'])
            && (
                !isset($this->settings['jwt_delete_by_parameter'])
                || empty(trim($this->settings['jwt_delete_by_parameter']))
            )
        ) {
            throw new Exception(
                __('Missing JWT parameter for Delete User.', 'simple-jwt-login'),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_DELETE,
                    SettingsErrors::ERR_DELETE_MISSING_JWT_PARAM
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isDeleteAllowed()
    {
        return isset($this->settings['allow_delete'])
            ? (bool)$this->settings['allow_delete']
            : false;
    }

    /**
     * @return bool
     */
    public function isAuthKeyRequiredOnDelete()
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
}
