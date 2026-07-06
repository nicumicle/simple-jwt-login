<?php

namespace SimpleJWTLogin\Modules\Settings;

class ApiKeysSettings extends BaseSettings implements SettingsInterface
{
    const SETTING_ENABLED            = 'enabled';
    const SETTING_HEADER_NAME        = 'header_name';
    const SETTING_ALLOW_USER_API_KEYS = 'allow_user_api_keys';
    const DEFAULT_HEADER_NAME        = 'X-API-Key';

    protected function getSectionKey()
    {
        return 'api_keys';
    }

    protected function getFieldDefinitions()
    {
        return [
            [null, self::SETTING_ENABLED,             'api_keys', self::SETTING_ENABLED,             self::SETTINGS_TYPE_BOL],
            [null, self::SETTING_HEADER_NAME,         'api_keys', self::SETTING_HEADER_NAME,         self::SETTINGS_TYPE_STRING],
            [null, self::SETTING_ALLOW_USER_API_KEYS, 'api_keys', self::SETTING_ALLOW_USER_API_KEYS, self::SETTINGS_TYPE_BOL],
        ];
    }

    public function validateSettings()
    {
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return !empty($this->settings[self::SETTING_ENABLED]);
    }

    /**
     * @return bool
     */
    public function isUserApiKeysEnabled()
    {
        return !empty($this->settings[self::SETTING_ALLOW_USER_API_KEYS]);
    }

    /**
     * @return string
     */
    public function getHeaderName()
    {
        $name = isset($this->settings[self::SETTING_HEADER_NAME])
            ? trim((string) $this->settings[self::SETTING_HEADER_NAME])
            : '';

        return $name !== '' ? $name : self::DEFAULT_HEADER_NAME;
    }
}
