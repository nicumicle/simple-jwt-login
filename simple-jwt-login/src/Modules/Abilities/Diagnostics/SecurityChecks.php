<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use SimpleJWTLogin\Modules\Abilities\SettingsPath;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
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
     * OAuth options that accept tokens not bound to the configured app: provider => [option, severity].
     * Auth0 is only a warning: opaque tokens still come from the site's own Auth0 tenant.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public static $unboundTokenOptions = array(
        'google'   => array('allow_tokens_from_other_clients', 'critical'),
        'facebook' => array('allow_tokens_from_other_apps', 'critical'),
        'github'   => array('allow_tokens_from_other_apps', 'critical'),
        'auth0'    => array('allow_opaque_access_token', 'warning'),
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

        return array_values(array_filter(array_merge([
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
                $this->flag($settings, 'authorization.enabled') && $this->flag($settings, 'authorization.password_hash_enabled'),
                ConfigurationChecks::SEVERITY_WARNING,
                'password_hash_login',
                __('/auth accepts the stored password hash instead of the password, so anyone holding a database dump can get JWTs without cracking it, and lockouts from login protection plugins are not applied. Disable authentication with password_hash.', 'simple-jwt-login'),
                ['authorization.password_hash_enabled']
            ),
            $this->finding(
                !$this->jwtSettings->getAuthenticationSettings()->isPayloadDataEnabled(AuthenticationSettings::JWT_PAYLOAD_PARAM_JTI),
                // Info, not a warning: identical tokens fail safe (revoking one revokes both), so the dashboard does not list it.
                ConfigurationChecks::SEVERITY_INFO,
                'jti_disabled',
                __('The "jti" claim is off, so two JWTs issued for the same user in the same second are identical: revoking one revokes the other, and sessions can not be told apart.', 'simple-jwt-login'),
                ['authorization.jwt_payload']
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
        ], $this->checkOauthUnboundTokens($settings))));
    }

    /**
     * @param array $settings
     * @return array
     */
    private function checkOauthUnboundTokens(array $settings)
    {
        $messages = [
            'google'   => __('Google accepts id_tokens issued for other Google clients. Any Google OAuth client, including one created by an attacker, can exchange a token for a WordPress JWT of the matching user.', 'simple-jwt-login'),
            'facebook' => __('Facebook accepts access tokens issued for other Facebook apps. Any Facebook app can exchange a token of its users for a WordPress JWT of the matching user.', 'simple-jwt-login'),
            'github'   => __('GitHub accepts tokens issued for other GitHub apps and personal access tokens. Any GitHub app can exchange a token of its users for a WordPress JWT of the matching user.', 'simple-jwt-login'),
            'auth0'    => __('Auth0 accepts opaque access tokens, which can not be bound to the configured Client ID. Any application in the Auth0 tenant can exchange a token for a WordPress JWT of the matching user.', 'simple-jwt-login'),
        ];
        $findings = [];
        foreach (self::$unboundTokenOptions as $provider => $option) {
            $prefix     = 'integrations.oauth.' . $provider . '.';
            $findings[] = $this->finding(
                $this->flag($settings, $prefix . 'enabled') && $this->flag($settings, $prefix . $option[0]),
                $option[1],
                $provider . '_unbound_tokens',
                $messages[$provider],
                [$prefix . $option[0]]
            );
        }

        return $findings;
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
