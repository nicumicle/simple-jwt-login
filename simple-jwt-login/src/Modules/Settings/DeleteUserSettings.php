<?php

namespace SimpleJWTLogin\Modules\Settings;

class DeleteUserSettings extends BaseSettings implements SettingsInterface
{
    protected function getSectionKey()
    {
        return 'delete_user';
    }

    protected function getFieldDefinitions()
    {
        return [
            [null, 'enabled',      null, 'allow_delete',        self::SETTINGS_TYPE_BOL],
            [null, 'auth_code',    null, 'require_delete_auth', self::SETTINGS_TYPE_BOL],
            [null, 'ip_whitelist', null, 'delete_ip',          self::SETTINGS_TYPE_STRING],
        ];
    }

    public function validateSettings()
    {
    }

    /**
     * @return bool
     */
    public function isDeleteAllowed()
    {
        return !empty($this->settings['enabled']);
    }

    /**
     * @return bool
     */
    public function isAuthKeyRequiredOnDelete()
    {
        return isset($this->settings['auth_code'])
            ? (bool)$this->settings['auth_code']
            : true;
    }

    /**
     * @return string
     */
    public function getAllowedDeleteIps()
    {
        return isset($this->settings['ip_whitelist'])
            ? $this->settings['ip_whitelist']
            : '';
    }
}
