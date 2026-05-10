<?php

namespace SimpleJWTLogin\Modules\Settings;

class DeleteUserSettings extends BaseSettings implements SettingsInterface
{
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
    }

    public function validateSettings()
    {
    }

    /**
     * @return bool
     */
    public function isDeleteAllowed()
    {
        return !empty($this->settings['allow_delete']);
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
}
