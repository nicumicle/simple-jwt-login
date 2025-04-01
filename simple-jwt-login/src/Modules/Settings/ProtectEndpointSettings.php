<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class ProtectEndpointSettings extends BaseSettings implements SettingsInterface
{
    const PROPERTY_GROUP = 'protect_endpoints';
    const ALL_ENDPOINTS = 1;
    const SPECIFIC_ENDPOINTS = 2;

    const REQUEST_METHOD_ALL = 'ALL';
    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_PUT = 'PUT';
    const REQUEST_METHOD_PATCH = 'PATCH';
    const REQUEST_METHOD_DELETE = 'DELETE';

    const ENDPOINT_MATCH_EXACT = 'EXACT';
    const ENDPOINT_MATCH_START_WITH = 'STARTS_WITH';

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
            'protect_method',
            self::PROPERTY_GROUP,
            'protect_method',
            BaseSettings::SETTINGS_TYPE_ARRAY
        );
        $this->assignSettingsPropertyFromPost(
            self::PROPERTY_GROUP,
            'protect_match',
            self::PROPERTY_GROUP,
            'protect_match',
            BaseSettings::SETTINGS_TYPE_ARRAY
        );
        $this->assignSettingsPropertyFromPost(
            self::PROPERTY_GROUP,
            'whitelist',
            self::PROPERTY_GROUP,
            'whitelist',
            BaseSettings::SETTINGS_TYPE_ARRAY
        );
        $this->assignSettingsPropertyFromPost(
            self::PROPERTY_GROUP,
            'whitelist_method',
            self::PROPERTY_GROUP,
            'whitelist_method',
            BaseSettings::SETTINGS_TYPE_ARRAY
        );
        $this->assignSettingsPropertyFromPost(
            self::PROPERTY_GROUP,
            'whitelist_match',
            self::PROPERTY_GROUP,
            'whitelist_match',
            BaseSettings::SETTINGS_TYPE_ARRAY
        );
    }

    public function validateSettings()
    {
        if ($this->isEnabled() === false) {
            return true;
        }

        $filteredEndpoints = array_filter($this->getProtectedEndpoints(), function ($value) {
            return !empty(trim($value['url'], " "));
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
     * @return array<int,array<string,mixed>>
     */
    public function getWhitelistedDomains()
    {
        return $this->parseProtectSettings('whitelist_method', 'whitelist', 'whitelist_match');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getProtectedEndpoints()
    {
        return $this->parseProtectSettings('protect_method', 'protect', 'protect_match');
    }

    /**
     * @param string $methodKey
     * @param string $endpointsKey
     * @return array<int,array<string,mixed>>
     */
    private function parseProtectSettings($methodKey, $endpointsKey, $matchKey)
    {
        $endpoints = isset($this->settings[ProtectEndpointSettings::PROPERTY_GROUP][$endpointsKey])
            ? (array) $this->settings[ProtectEndpointSettings::PROPERTY_GROUP][$endpointsKey]
            : [''];
        $methods = isset($this->settings[ProtectEndpointSettings::PROPERTY_GROUP][$methodKey])
            ? (array) $this->settings[ProtectEndpointSettings::PROPERTY_GROUP][$methodKey]
            : [''];
        $match = isset($this->settings[ProtectEndpointSettings::PROPERTY_GROUP][$matchKey])
            ? (array) $this->settings[ProtectEndpointSettings::PROPERTY_GROUP][$matchKey]
            : [''];

        $return = [];
        foreach ($endpoints as $key => $endpointPath) {
            $return[] = [
                'url' => $endpointPath,
                'method' => !empty($methods[$key])
                    ? strtoupper($methods[$key])
                    : self::REQUEST_METHOD_ALL,
                'match' => !empty($match[$key])
                    ? $match[$key]
                    : self::ENDPOINT_MATCH_START_WITH,
            ];
        }

        return array_values(array_filter($return, function ($endpoint) {
            if (trim($endpoint['url']) === "") {
                return false;
            };

            return true;
        }));
    }
}
