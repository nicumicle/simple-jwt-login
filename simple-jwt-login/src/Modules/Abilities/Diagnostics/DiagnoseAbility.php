<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use SimpleJWTLogin\Modules\Abilities\SettingsPath;
use SimpleJWTLogin\Modules\Abilities\AbstractAbility;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;
use SimpleJWTLogin\Services\RouteService;

/**
 * One-call health report: versions, the exact endpoint URLs with their
 * enabled / auth-code state, how JWTs are read and verified, valid role
 * names, and the misconfigurations found by ConfigurationChecks.
 */
class DiagnoseAbility extends AbstractAbility
{
    /**
     * @var TwoFactorBridge
     */
    private $twoFactorBridge;

    /**
     * route, method, enabled flag path, auth code flag path (or null).
     *
     * @var array[]
     */
    private static $endpoints = array(
        array('autologin', 'GET', 'login.enabled', 'login.auth_code'),
        array('users', 'POST', 'register.enabled', 'register.auth_code'),
        array('users', 'DELETE', 'delete_user.enabled', 'delete_user.auth_code'),
        array('auth', 'POST', 'authorization.enabled', 'authorization.auth_code'),
        array('auth/refresh', 'POST', 'authorization.refresh_token_enabled', 'authorization.refresh_auth_code'),
        array('auth/validate', 'GET, POST', 'authorization.validate_token_enabled', 'authorization.validate_auth_code'),
        array('auth/revoke', 'POST', 'authorization.revoke_token_enabled', 'authorization.revoke_auth_code'),
        array('user/reset_password', 'POST, PUT', 'reset_password.enabled', 'reset_password.auth_code'),
        array('auth/2fa', 'POST', 'integrations.3rdparty.two_factor.enabled', null),
        array('api-keys', 'GET, POST, PUT, DELETE', 'api_keys.enabled', null),
    );

    /**
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param TwoFactorBridge        $twoFactorBridge
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings, TwoFactorBridge $twoFactorBridge)
    {
        parent::__construct($jwtSettings);
        $this->twoFactorBridge = $twoFactorBridge;
    }

    protected function getSlug()
    {
        return 'diagnose';
    }

    public function isReadOnly()
    {
        return true;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Diagnose the Simple JWT Login setup', 'simple-jwt-login'),
            'description'   => __(
                'Returns a health report: plugin/WordPress/PHP versions, the full URL of every plugin endpoint with whether it is enabled and needs an auth code, how JWTs are read from requests and verified (no secrets, only whether a key is set), the WordPress role names valid in settings, and a list of misconfigurations with the settings paths to fix. Call it first when someone reports that the plugin does not work.',
                'simple-jwt-login'
            ),
            'input_schema'  => $this->objectSchema([
                'warnings_only' => [
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => __('Return only the misconfiguration warnings.', 'simple-jwt-login'),
                ],
            ]),
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'versions'  => ['type' => 'object'],
                    'rest'      => ['type' => 'object'],
                    'endpoints' => ['type' => 'array'],
                    'jwt'       => ['type' => 'object'],
                    'roles'     => ['type' => 'object'],
                    'warnings'  => ['type' => 'array'],
                ],
            ],
            'annotations'   => $this->annotations(true, false, true),
        ];
    }

    public function execute(array $input)
    {
        $warnings = (new ConfigurationChecks($this->jwtSettings, $this->twoFactorBridge))->run();
        if ($this->inputValue($input, 'warnings_only', false) === true) {
            return ['warnings' => $warnings];
        }

        $wordPressData = $this->jwtSettings->getWordPressData();
        $namespace     = trim($this->jwtSettings->getGeneralSettings()->getRouteNamespace(), '/');
        $siteUrl       = rtrim((string) $wordPressData->getSiteUrl(), '/');

        return [
            'versions'  => (object) [
                'plugin'          => defined('SIMPLE_JWT_LOGIN_VERSION') ? SIMPLE_JWT_LOGIN_VERSION : null,
                'wordpress'       => $wordPressData->getWordPressVersion(),
                'php'             => PHP_VERSION,
                'openssl'         => extension_loaded('openssl'),
                'abilities_api'   => $wordPressData->isAbilitiesApiAvailable(),
                'two_factor_plugin_active' => $this->twoFactorBridge->isAvailable(),
            ],
            'rest'      => (object) [
                'namespace'  => $namespace,
                'base_url'   => $siteUrl . '/wp-json/' . $namespace . '/',
                'plain_url'  => $siteUrl . '/?rest_route=/' . $namespace . '/',
                'note'       => __('Use plain_url when pretty permalinks are disabled (the wp-json URL then returns 404).', 'simple-jwt-login'),
            ],
            'endpoints' => $this->endpoints($siteUrl . '/wp-json/' . $namespace . '/'),
            'jwt'       => $this->jwtSummary(),
            'roles'     => (object) $wordPressData->getRoleNames(),
            'warnings'  => $warnings,
        ];
    }

    /**
     * @param string $baseUrl
     * @return array[]
     */
    private function endpoints($baseUrl)
    {
        $settings = $this->jwtSettings->getStoredSettings();
        $result   = [];
        foreach (self::$endpoints as $endpoint) {
            list($route, $method, $enabledPath, $authCodePath) = $endpoint;
            $result[] = [
                'url'                => $baseUrl . $route,
                'method'             => $method,
                'enabled'            => !empty(SettingsPath::get($settings, $enabledPath)),
                'auth_code_required' => $authCodePath !== null && !empty(SettingsPath::get($settings, $authCodePath)),
                'settings'           => array_values(array_filter([$enabledPath, $authCodePath])),
            ];
        }
        $result[] = [
            'url'                => $baseUrl . RouteService::OAUTH_TOKEN,
            'method'             => 'GET, POST',
            'enabled'            => $this->anyOauthProviderEnabled(),
            'auth_code_required' => false,
            'settings'           => ['integrations.oauth'],
        ];

        return $result;
    }

    /**
     * @return object
     */
    private function jwtSummary()
    {
        $general = $this->jwtSettings->getGeneralSettings();
        $sources = [];
        if ($general->isJwtFromHeaderEnabled()) {
            $sources['header'] = $general->getRequestKeyHeader();
        }
        if ($general->isJwtFromURLEnabled()) {
            $sources['url'] = $general->getRequestKeyUrl();
        }
        if ($general->isJwtFromCookieEnabled()) {
            $sources['cookie'] = $general->getRequestKeyCookie();
        }
        if ($general->isJwtFromSessionEnabled()) {
            $sources['session'] = $general->getRequestKeySession();
        }
        $fromCode = $general->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_CODE;

        return (object) [
            'algorithm'       => $general->getJWTDecryptAlgorithm(),
            'key_source'      => $fromCode ? 'wp-config.php constants' : 'plugin settings',
            'key_is_base64'   => (bool) $general->isDecryptionKeyBase64Encoded(),
            'input_sources'   => (object) $sources,
            'bearer_required' => (bool) $general->isJwtFromHeaderBearerRequired(),
            'jwt_rules'       => count($this->jwtSettings->getJwtRulesSettings()->getRules()),
            'login_by_claim'  => $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter(),
            'ttl_minutes'     => $this->jwtSettings->getAuthenticationSettings()->getAuthJwtTtl(),
        ];
    }

    /**
     * @return bool
     */
    private function anyOauthProviderEnabled()
    {
        $settings = $this->jwtSettings->getStoredSettings();
        $oauth    = isset($settings['integrations']['oauth']) ? (array) $settings['integrations']['oauth'] : [];
        foreach ($oauth as $provider) {
            if (is_array($provider) && !empty($provider['enabled'])) {
                return true;
            }
        }

        return false;
    }
}
