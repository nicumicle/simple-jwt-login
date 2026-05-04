<?php

namespace SimpleJWTLogin\Modules\Settings;

class ApiKeysSettings extends BaseSettings implements SettingsInterface
{
    const SETTINGS_GROUP       = 'api_keys';
    const SETTING_ENABLED      = 'enabled';
    const SETTING_HEADER_NAME  = 'header_name';
    const DEFAULT_HEADER_NAME  = 'X-API-Key';

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            self::SETTINGS_GROUP,
            self::SETTING_ENABLED,
            self::SETTINGS_GROUP,
            self::SETTING_ENABLED,
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            self::SETTINGS_GROUP,
            self::SETTING_HEADER_NAME,
            self::SETTINGS_GROUP,
            self::SETTING_HEADER_NAME,
            BaseSettings::SETTINGS_TYPE_STRING
        );
    }

    public function validateSettings()
    {
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return isset($this->settings[self::SETTINGS_GROUP][self::SETTING_ENABLED])
            && (bool) $this->settings[self::SETTINGS_GROUP][self::SETTING_ENABLED] === true;
    }

    /**
     * @return string
     */
    public function getHeaderName()
    {
        $name = isset($this->settings[self::SETTINGS_GROUP][self::SETTING_HEADER_NAME])
            ? trim((string) $this->settings[self::SETTINGS_GROUP][self::SETTING_HEADER_NAME])
            : '';

        return $name !== '' ? $name : self::DEFAULT_HEADER_NAME;
    }
}
