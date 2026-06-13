<?php

namespace SimpleJWTLogin\Modules\Settings\Migrations;

/**
 * Migrates flat v1 settings to the v2 sectioned schema.
 *
 * V1 stored all keys in a single flat JSON blob.
 * V2 groups them under named sections (login, register, authorization, ...).
 */
class V1ToV2Migration implements MigrationInterface
{
    public function getSourceVersion()
    {
        return 1;
    }

    public function getTargetVersion()
    {
        return 2;
    }

    public function migrate($settings)
    {
        if (!is_array($settings)) {
            $settings = [];
        }

        $result = [];
        $result['_schema_version'] = 2;

        $result['general']       = $this->migrateGeneral($settings);
        $result['login']         = $this->migrateLogin($settings);
        $result['register']      = $this->migrateRegister($settings);
        $result['authorization'] = $this->migrateAuthorization($settings);
        $result['cors']          = $this->migrateCors($settings);
        $result['delete_user']   = $this->migrateDeleteUser($settings);
        $result['hooks']         = $this->migrateHooks($settings);
        $result['reset_password'] = $this->migrateResetPassword($settings);
        $result['protect_endpoint'] = $this->migrateProtectEndpoint($settings);
        $result['auth_codes']    = $this->migrateAuthCodes($settings);
        $result['integrations']  = $this->migrateIntegrations($settings);
        $result['audit_log']     = $this->migrateAuditLog($settings);
        $result['jwt_rules']     = $this->migrateJwtRules($settings);
        $result['webhooks']      = $this->migrateWebhooks($settings);
        $result['api_keys']      = $this->migrateApiKeys($settings);

        return $result;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateGeneral($settings)
    {
        $general = [];
        $flatKeys = [
            'route_namespace',
            'jwt_algorithm',
            'decryption_source',
            'decryption_key',
            'decryption_key_base64',
            'decryption_key_public',
            'decryption_key_private',
            'request_jwt_url',
            'request_jwt_cookie',
            'request_jwt_header',
            'request_jwt_session',
        ];
        foreach ($flatKeys as $key) {
            if (array_key_exists($key, $settings)) {
                $general[$key] = $settings[$key];
            }
        }
        $subObjects = ['api_middleware', 'request_keys', 'security'];
        foreach ($subObjects as $key) {
            if (isset($settings[$key])) {
                $general[$key] = $settings[$key];
            }
        }
        return $general;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateLogin($settings)
    {
        $login = [];
        $map = [
            'allow_autologin'                  => 'enabled',
            'require_login_auth'               => 'auth_code',
            'jwt_login_by'                     => 'login_by',
            'jwt_login_by_parameter'           => 'login_by_parameter',
            'redirect'                         => 'redirect',
            'redirect_url'                     => 'redirect_url',
            'login_fail_redirect'              => 'fail_redirect',
            'include_login_request_parameters' => 'include_request_parameters',
            'allow_usage_redirect_parameter'   => 'allow_redirect_parameter',
            'login_remove_request_parameters'  => 'remove_request_parameters',
            'login_ip'                         => 'ip_whitelist',
            'login_iss'                        => 'iss_whitelist',
        ];
        foreach ($map as $old => $new) {
            if (array_key_exists($old, $settings)) {
                $login[$new] = $settings[$old];
            }
        }
        // legacy alias
        if (!isset($login['login_by_parameter']) && isset($settings['jwt_email_parameter'])) {
            $login['login_by_parameter'] = $settings['jwt_email_parameter'];
        }
        return $login;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateRegister($settings)
    {
        $register = [];
        $map = [
            'allow_register'         => 'enabled',
            'require_register_auth'  => 'auth_code',
            'new_user_profile'       => 'new_user_profile',
            'register_ip'            => 'ip_whitelist',
            'register_domain'        => 'domain_whitelist',
            'random_password'        => 'random_password',
            'random_password_length' => 'random_password_length',
            'register_force_login'   => 'force_login',
            'register_jwt'                => 'return_jwt',
            'allowed_user_meta'           => 'allowed_user_meta',
            'register_send_welcome_email' => 'send_welcome_email',
        ];
        foreach ($map as $old => $new) {
            if (array_key_exists($old, $settings)) {
                $register[$new] = $settings[$old];
            }
        }
        return $register;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateAuthorization($settings)
    {
        $auth = [];
        $map = [
            'allow_authentication'        => 'enabled',
            'auth_requires_auth_code'     => 'auth_code',
            'jwt_payload'                 => 'jwt_payload',
            'jwt_auth_ttl'                => 'ttl',
            'jwt_auth_refresh_ttl'        => 'refresh_ttl',
            'auth_ip'                     => 'ip_whitelist',
            'auth_password_base64'        => 'password_base64',
            'auth_password_hash_enabled'  => 'password_hash_enabled',
            'jwt_auth_iss'                => 'iss',
            'allow_refresh_token'         => 'refresh_token_enabled',
            'refresh_token_key'           => 'refresh_token_key',
            'allow_validate_token'        => 'validate_token_enabled',
            'allow_revoke_token'          => 'revoke_token_enabled',
            'refresh_requires_auth_code'  => 'refresh_auth_code',
            'validate_requires_auth_code' => 'validate_auth_code',
            'revoke_requires_auth_code'   => 'revoke_auth_code',
        ];
        foreach ($map as $old => $new) {
            if (array_key_exists($old, $settings)) {
                $auth[$new] = $settings[$old];
            }
        }
        return $auth;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateCors($settings)
    {
        return isset($settings['cors']) && is_array($settings['cors']) ? $settings['cors'] : [];
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateDeleteUser($settings)
    {
        $delete = [];
        $map = [
            'allow_delete'        => 'enabled',
            'require_delete_auth' => 'auth_code',
            'delete_ip'           => 'ip_whitelist',
        ];
        foreach ($map as $old => $new) {
            if (array_key_exists($old, $settings)) {
                $delete[$new] = $settings[$old];
            }
        }
        return $delete;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateHooks($settings)
    {
        $hooks = [];
        if (array_key_exists('enabled_hooks', $settings)) {
            $hooks['enabled_hooks'] = $settings['enabled_hooks'];
        }
        return $hooks;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateResetPassword($settings)
    {
        $resetPw = [];
        $map = [
            'allow_reset_password'              => 'enabled',
            'reset_password_requires_auth_code' => 'auth_code',
            'jwt_reset_password_flow'           => 'flow',
            'jwt_email_subject'                 => 'email_subject',
            'jwt_reset_password_email_body'     => 'email_body',
            'jwt_email_type'                    => 'email_type',
            'reset_password_jwt'                => 'return_jwt',
            'reset_password_send_changed_email' => 'send_password_changed_email',
        ];
        foreach ($map as $old => $new) {
            if (array_key_exists($old, $settings)) {
                $resetPw[$new] = $settings[$old];
            }
        }
        return $resetPw;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateProtectEndpoint($settings)
    {
        return isset($settings['protect_endpoints']) && is_array($settings['protect_endpoints'])
            ? $settings['protect_endpoints']
            : [];
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateAuthCodes($settings)
    {
        $authCodes = [];
        $authCodes['codes'] = isset($settings['auth_codes']) && is_array($settings['auth_codes'])
            ? $settings['auth_codes']
            : [];
        if (array_key_exists('auth_code_key', $settings)) {
            $authCodes['key'] = $settings['auth_code_key'];
        }
        return $authCodes;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateIntegrations($settings)
    {
        $oauth = [];
        foreach (['google', 'auth0'] as $slug) {
            if (isset($settings[$slug]) && is_array($settings[$slug])) {
                $oauth[$slug] = $settings[$slug];
            }
        }

        $thirdParty = [];
        if (isset($settings['wp_graphql']) && is_array($settings['wp_graphql'])) {
            $thirdParty['wpgraphql'] = $settings['wp_graphql'];
        }

        return [
            'oauth'    => $oauth,
            '3rdparty' => $thirdParty,
        ];
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateAuditLog($settings)
    {
        return isset($settings['audit_log']) && is_array($settings['audit_log']) ? $settings['audit_log'] : [];
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateJwtRules($settings)
    {
        $rules = isset($settings['jwt_rules']) && is_array($settings['jwt_rules']) ? $settings['jwt_rules'] : [];
        return ['rules' => $rules];
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateWebhooks($settings)
    {
        $webhooks = [];
        $webhooks['enabled'] = true;
        $webhooks['items'] = isset($settings['webhooks']) && is_array($settings['webhooks'])
            ? $settings['webhooks']
            : [];
        $webhooks['logs'] = [
            'enabled'   => isset($settings['webhook_logs_enabled'])
                ? (bool) $settings['webhook_logs_enabled']
                : true,
            'retention' => isset($settings['webhook_logs_retention_days'])
                ? (int) $settings['webhook_logs_retention_days']
                : 90,
        ];
        return $webhooks;
    }

    /**
     * @param array $settings
     * @return array
     */
    private function migrateApiKeys($settings)
    {
        return isset($settings['api_keys']) && is_array($settings['api_keys']) ? $settings['api_keys'] : [];
    }
}
