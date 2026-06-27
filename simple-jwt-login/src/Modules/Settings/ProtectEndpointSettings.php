<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class ProtectEndpointSettings extends BaseSettings implements SettingsInterface
{
    const PROPERTY_GROUP = 'protect_endpoints';

    // Legacy action constants — kept for backward-compat reading of old DB data
    const ALL_ENDPOINTS      = 1;
    const SPECIFIC_ENDPOINTS = 2;

    // Default-action constants
    const DEFAULT_PROTECT_ALL = 'protect_all';
    const DEFAULT_ALLOW_ALL   = 'allow_all';

    // Rule type constants
    const RULE_TYPE_PUBLIC          = 'public';
    const RULE_TYPE_PROTECTED       = 'protected';
    const RULE_TYPE_PROTECTED_ROLES = 'protected_roles';

    const REQUEST_METHOD_ALL    = 'ALL';
    const REQUEST_METHOD_GET    = 'GET';
    const REQUEST_METHOD_POST   = 'POST';
    const REQUEST_METHOD_PUT    = 'PUT';
    const REQUEST_METHOD_PATCH  = 'PATCH';
    const REQUEST_METHOD_DELETE = 'DELETE';

    const ENDPOINT_MATCH_EXACT      = 'EXACT';
    const ENDPOINT_MATCH_START_WITH = 'STARTS_WITH';

    protected function getSectionKey()
    {
        return 'protect_endpoint';
    }

    protected function getFieldDefinitions()
    {
        $group = self::PROPERTY_GROUP;

        return [
            [null, 'enabled',        $group, 'enabled',        self::SETTINGS_TYPE_INT],
            [null, 'default_action', $group, 'default_action', self::SETTINGS_TYPE_STRING],
            [null, 'rules_url',      $group, 'rules_url',      self::SETTINGS_TYPE_ARRAY],
            [null, 'rules_method',   $group, 'rules_method',   self::SETTINGS_TYPE_ARRAY],
            [null, 'rules_match',    $group, 'rules_match',    self::SETTINGS_TYPE_ARRAY],
            [null, 'rules_type',     $group, 'rules_type',     self::SETTINGS_TYPE_ARRAY],
            [null, 'rules_roles',    $group, 'rules_roles',    self::SETTINGS_TYPE_ARRAY],
        ];
    }

    public function validateSettings()
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if ($this->getDefaultAction() === self::DEFAULT_ALLOW_ALL) {
            $protectedRules = array_filter($this->getRules(), function ($rule) {
                return $rule['type'] !== self::RULE_TYPE_PUBLIC;
            });
            if (empty($protectedRules)) {
                throw new Exception(
                    esc_html__('You need to add at least one endpoint.', 'simple-jwt-login'),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_PROTECT_ENDPOINTS,
                        SettingsErrors::ERR_EMPTY_SPECIFIC_ENDPOINT
                    ))
                );
            }
        }

        foreach ($this->getRules() as $rule) {
            if ($rule['type'] !== self::RULE_TYPE_PROTECTED_ROLES) {
                continue;
            }
            if (empty($rule['roles'])) {
                throw new Exception(
                    esc_html__('A "JWT + Roles" rule must have at least one role specified.', 'simple-jwt-login'),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_PROTECT_ENDPOINTS,
                        SettingsErrors::ERR_PROTECTED_ROLES_RULE_MISSING_ROLES
                    ))
                );
            }
            foreach ($rule['roles'] as $role) {
                if (!$this->wordPressData->roleExists($role)) {
                    throw new Exception(
                        sprintf(
                            esc_html__('Role "%s" does not exist in WordPress.', 'simple-jwt-login'),
                            esc_html($role)
                        ),
                        absint($this->settingsErrors->generateCode(
                            SettingsErrors::PREFIX_PROTECT_ENDPOINTS,
                            SettingsErrors::ERR_PROTECTED_ROLES_RULE_MISSING_ROLES
                        ))
                    );
                }
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return !empty($this->settings['enabled']);
    }

    /**
     * @return string
     */
    public function getDefaultAction()
    {
        if (isset($this->settings['default_action'])) {
            return (string) $this->settings['default_action'];
        }

        // Migrate from old int-based action field
        $oldAction = isset($this->settings['action']) ? (int) $this->settings['action'] : 0;
        if ($oldAction === self::ALL_ENDPOINTS) {
            return self::DEFAULT_PROTECT_ALL;
        }

        return self::DEFAULT_ALLOW_ALL;
    }

    /**
     * @deprecated Use getDefaultAction() instead
     * @return int
     */
    public function getAction()
    {
        $defaultAction = $this->getDefaultAction();
        if ($defaultAction === self::DEFAULT_PROTECT_ALL) {
            return self::ALL_ENDPOINTS;
        }
        if ($defaultAction === self::DEFAULT_ALLOW_ALL) {
            return self::SPECIFIC_ENDPOINTS;
        }

        return 0;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getRules()
    {
        if (!empty($this->settings['rules_url'])) {
            return $this->parseNewFormat();
        }

        return $this->migrateOldFormat();
    }

    /**
     * @deprecated Use getRules() and filter by type === RULE_TYPE_PUBLIC instead
     * @return array<int,array<string,mixed>>
     */
    public function getWhitelistedDomains()
    {
        return array_values(array_filter($this->getRules(), function ($rule) {
            return $rule['type'] === self::RULE_TYPE_PUBLIC;
        }));
    }

    /**
     * @deprecated Use getRules() and filter by type !== RULE_TYPE_PUBLIC instead
     * @return array<int,array<string,mixed>>
     */
    public function getProtectedEndpoints()
    {
        return array_values(array_filter($this->getRules(), function ($rule) {
            return $rule['type'] !== self::RULE_TYPE_PUBLIC;
        }));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function parseNewFormat()
    {
        $urls     = (array) $this->settings['rules_url'];
        $methods  = isset($this->settings['rules_method']) ? (array) $this->settings['rules_method'] : [];
        $matches  = isset($this->settings['rules_match'])  ? (array) $this->settings['rules_match']  : [];
        $types    = isset($this->settings['rules_type'])   ? (array) $this->settings['rules_type']   : [];
        $rolesRaw = isset($this->settings['rules_roles'])  ? (array) $this->settings['rules_roles']  : [];

        $rules = [];
        foreach ($urls as $index => $url) {
            if (empty(trim($url))) {
                continue;
            }
            $rolesStr = isset($rolesRaw[$index]) ? (string) $rolesRaw[$index] : '';
            $roles = !empty(trim($rolesStr))
                ? array_values(array_filter(array_map('trim', explode(',', $rolesStr))))
                : [];
            $rules[] = [
                'url'    => $url,
                'method' => !empty($methods[$index]) ? strtoupper($methods[$index]) : self::REQUEST_METHOD_ALL,
                'match'  => !empty($matches[$index]) ? $matches[$index] : self::ENDPOINT_MATCH_START_WITH,
                'type'   => !empty($types[$index]) ? $types[$index] : self::RULE_TYPE_PROTECTED,
                'roles'  => $roles,
            ];
        }

        return $rules;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function migrateOldFormat()
    {
        $rules = [];

        foreach ($this->parseOldEntries('protect_method', 'protect', 'protect_match', 'protect_roles') as $entry) {
            $type = !empty($entry['roles']) ? self::RULE_TYPE_PROTECTED_ROLES : self::RULE_TYPE_PROTECTED;
            $rules[] = [
                'url'    => $entry['url'],
                'method' => $entry['method'],
                'match'  => $entry['match'],
                'type'   => $type,
                'roles'  => $entry['roles'],
            ];
        }

        foreach ($this->parseOldEntries('whitelist_method', 'whitelist', 'whitelist_match', 'whitelist_roles') as $entry) {
            $rules[] = [
                'url'    => $entry['url'],
                'method' => $entry['method'],
                'match'  => $entry['match'],
                'type'   => self::RULE_TYPE_PUBLIC,
                'roles'  => [],
            ];
        }

        return $rules;
    }

    /**
     * @param string $methodKey
     * @param string $endpointsKey
     * @param string $matchKey
     * @param string $rolesKey
     * @return array<int,array<string,mixed>>
     */
    private function parseOldEntries($methodKey, $endpointsKey, $matchKey, $rolesKey)
    {
        $endpoints = isset($this->settings[$endpointsKey]) ? (array) $this->settings[$endpointsKey] : [];
        $methods   = isset($this->settings[$methodKey])    ? (array) $this->settings[$methodKey]    : [];
        $match     = isset($this->settings[$matchKey])     ? (array) $this->settings[$matchKey]     : [];
        $rolesRaw  = isset($this->settings[$rolesKey])     ? (array) $this->settings[$rolesKey]     : [];

        $result = [];
        foreach ($endpoints as $key => $endpointPath) {
            $rolesStr = isset($rolesRaw[$key]) ? (string) $rolesRaw[$key] : '';
            $roles = !empty(trim($rolesStr))
                ? array_values(array_filter(array_map('trim', explode(',', $rolesStr))))
                : [];
            $result[] = [
                'url'    => $endpointPath,
                'method' => !empty($methods[$key]) ? strtoupper($methods[$key]) : self::REQUEST_METHOD_ALL,
                'match'  => !empty($match[$key])   ? $match[$key]              : self::ENDPOINT_MATCH_START_WITH,
                'roles'  => $roles,
            ];
        }

        return array_values(array_filter($result, function ($endpoint) {
            return trim($endpoint['url']) !== '';
        }));
    }
}
