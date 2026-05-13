<?php

namespace SimpleJWTLogin\Modules\Settings;

class ApiKeysSettings extends BaseSettings implements SettingsInterface
{
    const SETTING_ENABLED      = 'enabled';
    const SETTING_HEADER_NAME  = 'header_name';
    const DEFAULT_HEADER_NAME  = 'X-API-Key';

    protected function getSectionKey()
    {
        return 'api_keys';
    }

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            null,
            self::SETTING_ENABLED,
            'api_keys',
            self::SETTING_ENABLED,
            BaseSettings::SETTINGS_TYPE_BOL
        );
        $this->assignSettingsPropertyFromPost(
            null,
            self::SETTING_HEADER_NAME,
            'api_keys',
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
        return !empty($this->settings[self::SETTING_ENABLED]);
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
