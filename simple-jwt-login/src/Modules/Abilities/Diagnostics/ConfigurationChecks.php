<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use SimpleJWTLogin\Modules\Abilities\SettingsPath;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

/**
 * Finds common Simple JWT Login misconfigurations. Each warning names the
 * settings paths to change; secrets are only checked for being empty.
 */
class ConfigurationChecks
{
    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_WARNING  = 'warning';
    const SEVERITY_INFO     = 'info';

    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

    /**
     * @var TwoFactorBridge
     */
    private $twoFactorBridge;

    /**
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param TwoFactorBridge        $twoFactorBridge
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings, TwoFactorBridge $twoFactorBridge)
    {
        $this->jwtSettings     = $jwtSettings;
        $this->twoFactorBridge = $twoFactorBridge;
    }

    /**
     * @return array[] Each: {severity, code, message, settings}
     */
    public function run()
    {
        $settings = $this->jwtSettings->getStoredSettings();

        return array_values(array_filter(array_merge(
            [$this->checkSigningKey()],
            $this->checkOpenEndpoints($settings),
            [
                $this->checkTwoFactor($settings),
                $this->when(
                    $this->flag($settings, 'general.request_jwt_url'),
                    self::SEVERITY_WARNING,
                    'jwt_in_url',
                    __('JWTs are accepted in the URL, so they end up in server logs, browser history and Referer headers. Prefer the Authorization header.', 'simple-jwt-login'),
                    ['general.request_jwt_url', 'general.request_jwt_header']
                ),
                $this->when(
                    $this->flag($settings, 'cors.enabled') && $this->flag($settings, 'cors.allow_origin_enabled')
                        && SettingsPath::get($settings, 'cors.allow_origin') === '*',
                    self::SEVERITY_INFO,
                    'cors_any_origin',
                    __('CORS allows every origin (*). Restrict it to the domains of your apps.', 'simple-jwt-login'),
                    ['cors.allow_origin']
                ),
                $this->when(
                    !$this->flag($settings, 'audit_log.enabled'),
                    self::SEVERITY_INFO,
                    'audit_log_disabled',
                    __('The audit log is off, so failed logins and token errors are not recorded (simple-jwt-login/get-audit-log returns nothing).', 'simple-jwt-login'),
                    ['audit_log.enabled', 'audit_log.enabled_events']
                ),
            ]
        )));
    }

    /**
     * @return array|null
     */
    private function checkSigningKey()
    {
        $general = $this->jwtSettings->getGeneralSettings();
        if ($general->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_CODE) {
            return null;
        }
        $isRsa   = strpos($general->getJWTDecryptAlgorithm(), 'RS') !== false;
        $missing = $isRsa
            ? ($general->getDecryptionKeyPublic() === '' || $general->getDecryptionKeyPrivate() === '')
            : $general->getDecryptionKey() === '';

        return $this->when(
            $missing,
            self::SEVERITY_CRITICAL,
            'signing_key_missing',
            __('No JWT signing key is configured for the selected algorithm, so JWTs cannot be issued or verified. Set it on the plugin settings page (AI cannot set keys).', 'simple-jwt-login'),
            ['general.jwt_algorithm', 'general.decryption_source']
        );
    }

    /**
     * @param array $settings
     * @return array
     */
    private function checkOpenEndpoints(array $settings)
    {
        $registerOpen = $this->flag($settings, 'register.enabled') && !$this->flag($settings, 'register.auth_code');

        return [
            $this->when(
                $registerOpen && in_array('administrator', (array) SettingsPath::get($settings, 'register.new_user_profile'), true),
                self::SEVERITY_CRITICAL,
                'register_creates_admins',
                __('Anyone can register through the API and new users become administrators. Change the default role or require an auth code.', 'simple-jwt-login'),
                ['register.new_user_profile', 'register.auth_code']
            ),
            $this->when(
                $registerOpen,
                self::SEVERITY_WARNING,
                'register_without_auth_code',
                __('Anyone can create accounts through the API. Require an auth code or restrict IPs/domains if that is not intended.', 'simple-jwt-login'),
                ['register.auth_code', 'register.ip_whitelist', 'register.domain_whitelist']
            ),
            $this->when(
                $this->flag($settings, 'delete_user.enabled') && !$this->flag($settings, 'delete_user.auth_code'),
                self::SEVERITY_CRITICAL,
                'delete_without_auth_code',
                __('Users can be deleted through the API with only a JWT. Require an auth code and an IP whitelist.', 'simple-jwt-login'),
                ['delete_user.auth_code', 'delete_user.ip_whitelist']
            ),
        ];
    }

    /**
     * @param array $settings
     * @return array|null
     */
    private function checkTwoFactor(array $settings)
    {
        return $this->when(
            $this->flag($settings, 'integrations.3rdparty.two_factor.enabled') && !$this->twoFactorBridge->isAvailable(),
            self::SEVERITY_CRITICAL,
            'two_factor_plugin_missing',
            __('Two-factor is enabled but the Two Factor plugin is not active, so authentication fails (error_code 105). Activate the plugin or disable the integration.', 'simple-jwt-login'),
            ['integrations.3rdparty.two_factor.enabled']
        );
    }

    /**
     * @param bool     $condition
     * @param string   $severity
     * @param string   $code
     * @param string   $message
     * @param string[] $paths
     * @return array|null
     */
    private function when($condition, $severity, $code, $message, array $paths)
    {
        if (!$condition) {
            return null;
        }

        return ['severity' => $severity, 'code' => $code, 'message' => $message, 'settings' => $paths];
    }

    /**
     * @param array  $settings
     * @param string $path
     * @return bool
     */
    private function flag(array $settings, $path)
    {
        return !empty(SettingsPath::get($settings, $path));
    }
}
