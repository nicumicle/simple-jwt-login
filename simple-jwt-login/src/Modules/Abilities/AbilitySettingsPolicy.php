<?php

namespace SimpleJWTLogin\Modules\Abilities;

use SimpleJWTLogin\Modules\Settings\Oauth\OauthProviderRegistry;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;

/**
 * Decides which settings paths (dot-notation, e.g. "general.decryption_key")
 * are secrets and which ones may be changed through the WordPress Abilities API.
 *
 * Secrets are never writable and are masked unless the administrator granted
 * the "view secrets" permission. Role settings are writable only with the
 * "change roles" permission, OAuth providers, JWT rules and the JWT-to-user
 * mapping only with the "change authentication providers" permission.
 * Every other setting is writable, except the AI
 * Abilities settings themselves (only a human can grant or widen AI access),
 * the schema version, and webhook items (they hold masked header values and
 * have dedicated add/remove abilities).
 *
 * A policy built without permissions (new AbilitySettingsPolicy()) grants
 * nothing, so audit-log output always stays masked. Abilities call
 * withPermissions() at call time, after the settings were reloaded.
 */
class AbilitySettingsPolicy
{
    /**
     * Settings that decide which role a user gets ("*" = one list index).
     * register.allowed_user_meta is here because user meta can carry
     * capabilities (RegisterUserService also hard-denies those keys).
     * Writable only with the "change roles" permission.
     *
     * @var string[]
     */
    public static $rolePaths = array(
        'register.new_user_profile',
        'register.allowed_user_meta',
        'auth_codes.codes.*.role',
    );

    /**
     * Settings whose value is a role slug. Even with the "change roles"
     * permission an agent can never set a privileged role here: a new user or
     * an auth code with administrator rights is a takeover of the site.
     *
     * @var string[]
     */
    public static $roleValuePaths = array(
        'register.new_user_profile',
        'auth_codes.codes.*.role',
    );

    /**
     * A role holding any of these capabilities is privileged.
     *
     * @var string[]
     */
    public static $adminCapabilities = array(
        'manage_options',
        'manage_network',
        'promote_users',
        'create_users',
        'edit_users',
        'delete_users',
        'install_plugins',
        'activate_plugins',
        'edit_plugins',
    );

    /**
     * @param string $path
     * @return bool
     */
    public function isRoleValuePath($path)
    {
        $segments = explode('.', (string) $path);
        foreach (self::$roleValuePaths as $pattern) {
            if ($this->matchesPattern($segments, explode('.', $pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string               $role
     * @param array<string, mixed> $capabilities The role's capabilities.
     * @return bool
     */
    public function isPrivilegedRole($role, array $capabilities)
    {
        if (strtolower(trim((string) $role)) === 'administrator') {
            return true;
        }
        foreach (self::$adminCapabilities as $capability) {
            if (!empty($capabilities[$capability])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Settings that decide who can log in: the trusted JWT issuers, how a JWT
     * maps to a WordPress user and two-factor enforcement. Each path
     * also covers everything below it ("*" = one path segment). The OAuth
     * providers (integrations.oauth.<provider>) are matched through the
     * provider registry, see isAuthProviderPath(). Writable only with the
     * "change authentication providers" permission.
     *
     * @var string[]
     */
    public static $authProviderPaths = array(
        'jwt_rules',
        'login.login_by',
        'login.login_by_parameter',
        'login.iss_whitelist',
        'integrations.3rdparty.two_factor',
    );

    /**
     * @var AiAbilitiesSettings|null
     */
    private $aiSettings;

    /**
     * Copy of this policy that applies the administrator's AI permissions.
     *
     * @param AiAbilitiesSettings $aiSettings
     * @return static
     */
    public function withPermissions(AiAbilitiesSettings $aiSettings)
    {
        $policy             = clone $this;
        $policy->aiSettings = $aiSettings;

        return $policy;
    }

    /**
     * @param string $permission One of AiAbilitiesSettings::PERMISSION_*.
     * @return bool
     */
    public function isGranted($permission)
    {
        return $this->aiSettings !== null && $this->aiSettings->isPermissionGranted($permission);
    }

    /**
     * @return array<string, bool>
     */
    public function getPermissions()
    {
        return $this->aiSettings !== null
            ? $this->aiSettings->getPermissions()
            : array_fill_keys(AiAbilitiesSettings::$permissions, false);
    }

    /**
     * @return bool
     */
    public function canViewSecrets()
    {
        return $this->isGranted(AiAbilitiesSettings::PERMISSION_VIEW_SECRETS);
    }

    /**
     * The permission that is missing to write this path, or '' when none is needed.
     *
     * @param string $path
     * @return string
     */
    public function missingPermission($path)
    {
        if ($this->isSecret($path)) {
            // No permission makes a secret writable.
            return '';
        }
        if ($this->isRolePath($path) && !$this->isGranted(AiAbilitiesSettings::PERMISSION_CHANGE_ROLES)) {
            return AiAbilitiesSettings::PERMISSION_CHANGE_ROLES;
        }
        if ($this->isAuthProviderPath($path) && !$this->isGranted(AiAbilitiesSettings::PERMISSION_CHANGE_AUTH_PROVIDERS)) {
            return AiAbilitiesSettings::PERMISSION_CHANGE_AUTH_PROVIDERS;
        }

        return '';
    }

    /**
     * Paths the AI may not write right now (always read-only paths plus the
     * paths whose permission is not granted).
     *
     * @return string[]
     */
    public function readOnlyPaths()
    {
        $paths = self::$readOnlyPaths;
        if (!$this->isGranted(AiAbilitiesSettings::PERMISSION_CHANGE_ROLES)) {
            $paths = array_merge($paths, self::$rolePaths);
        }
        if (!$this->isGranted(AiAbilitiesSettings::PERMISSION_CHANGE_AUTH_PROVIDERS)) {
            $paths = array_merge($paths, self::$authProviderPaths, $this->oauthProviderPaths());
        }

        return $paths;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isAuthProviderPath($path)
    {
        $segments = explode('.', (string) $path);
        if (count($segments) >= 3
            && $segments[0] === 'integrations'
            && $segments[1] === 'oauth'
            && OauthProviderRegistry::has($segments[2])
        ) {
            return true;
        }

        foreach (self::$authProviderPaths as $pattern) {
            $patternSegments = explode('.', $pattern);
            if (count($segments) >= count($patternSegments)
                && $this->matchesPattern(array_slice($segments, 0, count($patternSegments)), $patternSegments)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function oauthProviderPaths()
    {
        $paths = array();
        foreach (OauthProviderRegistry::all() as $provider) {
            $paths[] = 'integrations.oauth.' . $provider->getSlug();
        }

        return $paths;
    }
    /**
     * Leaf keys holding secrets, wherever they appear in the settings tree.
     *
     * @var string[]
     */
    public static $secretLeafKeys = array(
        'decryption_key',
        'decryption_key_public',
        'decryption_key_private',
        'client_secret',
        'refresh_token_key',
    );

    /**
     * Secret paths whose leaf key is too generic to be secret everywhere.
     * "*" matches exactly one path segment (a list index).
     *
     * @var string[]
     */
    public static $secretPaths = array(
        'auth_codes.codes.*.code',
    );

    /**
     * Lists whose items always hold a secret. Abilities can edit or remove
     * their items but never add one, even when the list is empty: an item
     * added without its secret would be stored without it (an auth code
     * without "code" is read back as the literal code "Array").
     *
     * @var string[]
     */
    public static $secretLists = array(
        'auth_codes.codes',
        'jwt_rules.rules',
    );

    /**
     * Paths (and everything below them) that abilities can never write.
     * general.security holds safe redirects and proxy IP trust: a
     * prompt-injected agent must not be able to turn off safe redirects or
     * make the client IP spoofable.
     *
     * @var string[]
     */
    public static $readOnlyPaths = array(
        '_schema_version',
        'integrations.3rdparty.ai_abilities',
        'webhooks.items',
        'general.security',
    );

    /**
     * Paths stored base64-encoded; abilities read and write them as plain text.
     *
     * @var string[]
     */
    public static $base64Paths = array(
        'reset_password.email_body',
    );

    /**
     * Lists that are stored side by side and must keep the same length
     * (one rule / claim per index), keyed by the path of their parent.
     *
     * @var array<string, string[]>
     */
    public static $parallelLists = array(
        'protect_endpoint'                   => array('rules_url', 'rules_method', 'rules_match', 'rules_type', 'rules_roles'),
        'authorization.custom_claims.payload' => array('key', 'value'),
        'authorization.custom_claims.header'  => array('key', 'value'),
    );

    /**
     * @param string $path
     * @return bool
     */
    public function isSecret($path)
    {
        $segments = explode('.', (string) $path);
        if (in_array(end($segments), self::$secretLeafKeys, true)) {
            return true;
        }

        foreach (self::$secretPaths as $pattern) {
            if ($this->matchesPattern($segments, explode('.', $pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isWritable($path)
    {
        $path = (string) $path;
        if ($this->isSecret($path) || $this->missingPermission($path) !== '') {
            return false;
        }

        foreach (self::$readOnlyPaths as $readOnlyPath) {
            if ($path === $readOnlyPath || strpos($path, $readOnlyPath . '.') === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isRolePath($path)
    {
        $segments = explode('.', (string) $path);
        foreach (self::$rolePaths as $pattern) {
            if ($this->matchesPattern($segments, explode('.', $pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isSecretList($path)
    {
        return in_array((string) $path, self::$secretLists, true);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isBase64($path)
    {
        return in_array((string) $path, self::$base64Paths, true);
    }

    /**
     * @param string[] $segments
     * @param string[] $pattern
     * @return bool
     */
    private function matchesPattern(array $segments, array $pattern)
    {
        if (count($segments) !== count($pattern)) {
            return false;
        }
        foreach ($pattern as $index => $patternSegment) {
            if ($patternSegment !== '*' && $patternSegment !== $segments[$index]) {
                return false;
            }
        }

        return true;
    }
}
