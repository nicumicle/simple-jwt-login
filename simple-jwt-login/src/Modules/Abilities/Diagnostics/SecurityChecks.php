<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use SimpleJWTLogin\Modules\Abilities\SettingsPath;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

/**
 * Hardening checks on top of ConfigurationChecks: settings that work but
 * weaken security. Secrets are only measured, never returned.
 */
class SecurityChecks
{
    /**
     * JWT lifetime (minutes) above which a warning is raised: one day.
     */
    const MAX_TTL_MINUTES = 1440;

    /**
     * Minimum key bytes per HMAC algorithm (the hash output size).
     *
     * @var array<string, int>
     */
    public static $keyBytes = array(
        'HS256' => 32,
        'HS384' => 48,
        'HS512' => 64,
    );

    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

    /**
     * @param SimpleJWTLoginSettings $jwtSettings
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings)
    {
        $this->jwtSettings = $jwtSettings;
    }

    /**
     * @return array[] Each: {severity, code, message, settings}
     */
    public function run()
    {
        $settings = $this->jwtSettings->getStoredSettings();
        $autologin = $this->flag($settings, 'login.enabled');

        return array_values(array_filter([
            $this->finding(
                $this->isSigningKeyWeak(),
                ConfigurationChecks::SEVERITY_WARNING,
                'weak_signing_key',
                __('The JWT signing key is shorter than the output of the selected HMAC algorithm, which makes it easier to brute-force. Set a longer key on the settings page.', 'simple-jwt-login'),
                ['general.jwt_algorithm', 'general.decryption_key']
            ),
            $this->finding(
                (int) SettingsPath::get($settings, 'authorization.ttl') > self::MAX_TTL_MINUTES,
                ConfigurationChecks::SEVERITY_WARNING,
                'long_jwt_ttl',
                __('JWTs stay valid for more than a day, so a stolen token can be used for a long time. Use a short TTL and enable refresh tokens.', 'simple-jwt-login'),
                ['authorization.ttl', 'authorization.refresh_token_enabled']
            ),
            $this->finding(
                $autologin && !$this->flag($settings, 'login.auth_code')
                    && trim((string) SettingsPath::get($settings, 'login.ip_whitelist')) === '',
                ConfigurationChecks::SEVERITY_WARNING,
                'autologin_unrestricted',
                __('Autologin turns any valid JWT into a WordPress login session, without an auth code or IP whitelist. Require an auth code or restrict the IPs.', 'simple-jwt-login'),
                ['login.auth_code', 'login.ip_whitelist']
            ),
            $this->finding(
                $autologin && !$this->flag($settings, 'general.security.safe_redirect'),
                ConfigurationChecks::SEVERITY_WARNING,
                'unsafe_redirect',
                __('Autologin is enabled without safe redirects, so a redirect URL can send users to any external site (open redirect). Enable safe redirects.', 'simple-jwt-login'),
                ['general.security.safe_redirect']
            ),
            $this->finding(
                $this->hasAdministratorAuthCode($settings),
                ConfigurationChecks::SEVERITY_WARNING,
                'auth_code_creates_admins',
                __('An auth code registers users as administrators. Anyone who learns that code can create an administrator account.', 'simple-jwt-login'),
                ['auth_codes.codes']
            ),
            $this->finding(
                $this->flag($settings, 'authorization.enabled') && !$this->flag($settings, 'authorization.revoke_token_enabled'),
                ConfigurationChecks::SEVERITY_INFO,
                'revoke_endpoint_disabled',
                __('Clients cannot revoke their own JWTs (logout) because the revoke endpoint is off.', 'simple-jwt-login'),
                ['authorization.revoke_token_enabled']
            ),
            $this->finding(
                $this->jwtSettings->getIntegrationsSettings()->aiAbilities()->isPermissionGranted(AiAbilitiesSettings::PERMISSION_VIEW_SECRETS),
                ConfigurationChecks::SEVERITY_INFO,
                'ai_can_view_secrets',
                __('AI agents can read signing keys and other secrets, which may be sent to the AI provider.', 'simple-jwt-login'),
                ['integrations.3rdparty.ai_abilities']
            ),
        ]));
    }

    /**
     * @return bool
     */
    private function isSigningKeyWeak()
    {
        $general   = $this->jwtSettings->getGeneralSettings();
        $algorithm = (string) $general->getJWTDecryptAlgorithm();
        $key       = (string) $general->getDecryptionKey();
        $keyBytes  = isset(self::$keyBytes[$algorithm]) ? self::$keyBytes[$algorithm] : 0;
        if ($general->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_CODE
            || $keyBytes === 0
            || $key === ''
        ) {
            return false;
        }
        if ($general->isDecryptionKeyBase64Encoded()) {
            $decoded = base64_decode($key, true);
            $key     = $decoded === false ? $key : $decoded;
        }

        return strlen($key) < $keyBytes;
    }

    /**
     * @param array $settings
     * @return bool
     */
    private function hasAdministratorAuthCode(array $settings)
    {
        foreach ((array) SettingsPath::get($settings, 'auth_codes.codes') as $code) {
            if (is_array($code) && isset($code['role']) && $code['role'] === 'administrator') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param bool     $condition
     * @param string   $severity
     * @param string   $code
     * @param string   $message
     * @param string[] $paths
     * @return array|null
     */
    private function finding($condition, $severity, $code, $message, array $paths)
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
