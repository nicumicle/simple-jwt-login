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

    protected function getSectionKey()
    {
        return 'protect_endpoint';
    }

    protected function getFieldDefinitions()
    {
        $group = self::PROPERTY_GROUP;

        return [
            [null, 'enabled',            $group, 'enabled',            self::SETTINGS_TYPE_INT],
            [null, 'action',             $group, 'action',             self::SETTINGS_TYPE_INT],
            [null, 'protect',            $group, 'protect',            self::SETTINGS_TYPE_ARRAY],
            [null, 'protect_method',     $group, 'protect_method',     self::SETTINGS_TYPE_ARRAY],
            [null, 'protect_match',      $group, 'protect_match',      self::SETTINGS_TYPE_ARRAY],
            [null, 'whitelist',          $group, 'whitelist',          self::SETTINGS_TYPE_ARRAY],
            [null, 'whitelist_method',   $group, 'whitelist_method',   self::SETTINGS_TYPE_ARRAY],
            [null, 'whitelist_match',    $group, 'whitelist_match',    self::SETTINGS_TYPE_ARRAY],
        ];
    }

    public function validateSettings()
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $filteredEndpoints = array_filter($this->getProtectedEndpoints(), function ($value) {
            return !empty(trim($value['url'], ' '));
        });

        if ($this->getAction() === self::SPECIFIC_ENDPOINTS && empty($filteredEndpoints)) {
            throw new Exception(
                esc_html__('You need to add at least one endpoint.', 'simple-jwt-login'),
                absint($this->settingsErrors->generateCode(
                    SettingsErrors::PREFIX_PROTECT_ENDPOINTS,
                    SettingsErrors::ERR_EMPTY_SPECIFIC_ENDPOINT
                ))
            );
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return !empty($this->settings['enabled']);
    }

    /**
     * @return int
     */
    public function getAction()
    {
        return isset($this->settings['action'])
            ? (int) $this->settings['action']
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
     * @param string $matchKey
     * @return array<int,array<string,mixed>>
     */
    private function parseProtectSettings($methodKey, $endpointsKey, $matchKey)
    {
        $endpoints = isset($this->settings[$endpointsKey])
            ? (array) $this->settings[$endpointsKey]
            : [''];
        $methods = isset($this->settings[$methodKey])
            ? (array) $this->settings[$methodKey]
            : [''];
        $match = isset($this->settings[$matchKey])
            ? (array) $this->settings[$matchKey]
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
            return trim($endpoint['url']) !== '';
        }));
    }
}
