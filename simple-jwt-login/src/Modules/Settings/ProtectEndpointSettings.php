<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class ProtectEndpointSettings extends BaseSettings implements SettingsInterface
{
    const PROPERTY_GROUP = 'protect_endpoints';
    const ALL_ENDPOINTS = 1;
    const SPECIFIC_ENDPOINTS = 2;

    public function initSettingsFromPost()
    {
        $this->assignSettingsPropertyFromPost(
            self::PROPERTY_GROUP,
            'enabled',
            self::PROPERTY_GROUP,
            'enabled',
            BaseSettings::SETTINGS_TYPE_INT
        );

        $this->assignSettingsPropertyFromPost(
            self::PROPERTY_GROUP,
            'action',
            self::PROPERTY_GROUP,
            'action',
            BaseSettings::SETTINGS_TYPE_INT
        );
        $this->assignSettingsPropertyFromPost(
            self::PROPERTY_GROUP,
            'protect',
            self::PROPERTY_GROUP,
            'protect',
            BaseSettings::SETTINGS_TYPE_ARRAY
        );
        $this->assignSettingsPropertyFromPost(
            self::PROPERTY_GROUP,
            'whitelist',
            self::PROPERTY_GROUP,
            'whitelist',
            BaseSettings::SETTINGS_TYPE_ARRAY
        );
    }

    public function validateSettings()
    {
        if ($this->isEnabled() === false) {
            return true;
        }

        $filteredEndpoints = array_filter($this->getProtectedEndpoints(), function ($value) {
            return !empty(trim($value));
        });

        if ($this->getAction() === ProtectEndpointSettings::SPECIFIC_ENDPOINTS && empty($filteredEndpoints)) {
            throw new Exception(
                __('You need to add at least one endpoint.', 'simple-jwt-login'),
                $this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_PROTECT_ENDPOINTS,
                    SettingsErrors::ERR_EMPTY_SPECIFIC_ENDPOINT
                )
            );
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return !empty($this->settings[ProtectEndpointSettings::PROPERTY_GROUP]['enabled']);
    }

    /**
     * @return int
     */
    public function getAction()
    {
        return isset($this->settings[ProtectEndpointSettings::PROPERTY_GROUP]['action'])
            ? (int) $this->settings[ProtectEndpointSettings::PROPERTY_GROUP]['action']
            : 0;
    }

    /**
     * @return string[]
     */
    public function getWhitelistedDomains()
    {
        $result = isset($this->settings[ProtectEndpointSettings::PROPERTY_GROUP]['whitelist'])
                ? (array) $this->settings[ProtectEndpointSettings::PROPERTY_GROUP]['whitelist']
                : [''];

        return array_unique($result);
    }

    /**
     * @return string[]
     */
    public function getProtectedEndpoints()
    {
        $result = isset($this->settings[ProtectEndpointSettings::PROPERTY_GROUP]['protect'])
            ?  (array) $this->settings[ProtectEndpointSettings::PROPERTY_GROUP]['protect']
            : [''];

        return array_unique($result);
    }
}
