<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;

/**
 * Hand-written guidance for the codes raised when settings fail validation
 * (admin page save or simple-jwt-login/update-settings). Keyed by the full
 * code (section prefix * 1000 + error), exactly as each validator builds it:
 * error numbers repeat across sections and some validators borrow another
 * section's prefix.
 */
class SettingsErrorCatalog
{
    /**
     * @param int $code
     * @return array{0: string, 1: string[]}|null [explanation, settings paths]
     */
    public function find($code)
    {
        $entries = $this->entries();

        return isset($entries[$code]) ? $entries[$code] : null;
    }

    /**
     * @param int $code
     * @return bool
     */
    public function isSettingsCode($code)
    {
        return (int) $code >= SettingsErrors::PREFIX_LEEWAY;
    }

    /**
     * @return array<int, array{0: string, 1: string[]}>
     */
    protected function entries()
    {
        return $this->coreEntries() + $this->applicationEntries();
    }

    /**
     * @param int $prefix
     * @param int $error
     * @return int
     */
    private function code($prefix, $error)
    {
        return SettingsErrors::PREFIX_LEEWAY * $prefix + $error;
    }

    /**
     * @return array<int, array{0: string, 1: string[]}>
     */
    private function coreEntries()
    {
        $keys    = ['general.jwt_algorithm', 'general.decryption_source', 'general.decryption_key_base64'];
        $sources = ['general.request_jwt_url', 'general.request_jwt_header', 'general.request_jwt_cookie', 'general.request_jwt_session'];
        $flags   = ['login.auth_code', 'register.auth_code', 'delete_user.auth_code', 'authorization.auth_code', 'reset_password.auth_code'];

        return [
            $this->code(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_EMPTY_NAMESPACE) => ['The route namespace is empty.', ['general.route_namespace']],
            $this->code(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_REQUEST_KEYS) => ['Every JWT input source needs a parameter/header name.', ['general.request_keys']],
            $this->code(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_PRIVATE_KEY_MISSING_FROM_CODE_RS) => ['Keys are read from code, but the RS public/private key constants are not defined in wp-config.php.', $keys],
            $this->code(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_PRIVATE_KEY_NOT_PRESENT_IN_CODE_HS) => ['Keys are read from code, but the HS key constant is not defined in wp-config.php.', $keys],
            $this->code(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_MISSING_PRIVATE_AND_PUBLIC_KEY) => ['An RS algorithm needs a public and a private key. Set them on the plugin settings page (AI cannot set keys) or go back to an HS algorithm.', $keys],
            $this->code(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_DECRYPTION_KEY_REQUIRED) => ['An HS algorithm needs a JWT verification key. Set it on the plugin settings page (AI cannot set keys).', $keys],
            $this->code(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_GET_JWT_FROM) => ['At least one JWT input source must be enabled.', $sources],
            $this->code(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_MISSING_JWT_PAYLOAD_KEY) => ['Autologin is enabled but the JWT claim that identifies the user is empty.', ['login.enabled', 'login.login_by', 'login.login_by_parameter']],
            $this->code(SettingsErrors::PREFIX_LOGIN, SettingsErrors::ERR_LOGIN_INVALID_CUSTOM_URL) => ['The custom redirect URL after autologin is not a valid URL.', ['login.redirect', 'login.redirect_url']],
            $this->code(SettingsErrors::PREFIX_CORS, SettingsErrors::ERR_CORS_NO_OPTION) => ['CORS is enabled but none of origin/methods/headers is enabled.', ['cors.enabled', 'cors.allow_origin_enabled', 'cors.allow_methods_enabled', 'cors.allow_headers_enabled']],
            $this->code(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_MISSING_NEW_USER_PROFILE) => ['Registration needs a default role for new users.', ['register.new_user_profile']],
            $this->code(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_INVALID_ROLE) => ['The default role for new users does not exist in WordPress.', ['register.new_user_profile']],
            $this->code(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_NUMERIC) => ['The random password length must be an integer.', ['register.random_password_length']],
            $this->code(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_MIN_LENGTH) => ['The random password length must be at least 6.', ['register.random_password_length']],
            $this->code(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_MAX_LENGTH) => ['The random password length can be at most 255.', ['register.random_password_length']],
            $this->code(SettingsErrors::PREFIX_AUTHENTICATION, SettingsErrors::ERR_AUTHENTICATION_EMPTY_PAYLOAD) => ['Authentication needs at least one claim in the JWT payload.', ['authorization.jwt_payload']],
            $this->code(SettingsErrors::PREFIX_AUTHENTICATION, SettingsErrors::ERR_AUTHENTICATION_TTL) => ['The JWT time to live must be greater than zero.', ['authorization.ttl']],
            $this->code(SettingsErrors::PREFIX_AUTHENTICATION, SettingsErrors::ERR_AUTHENTICATION_EMPTY_ISS) => ['The issuer (iss) for issued JWTs is empty.', ['authorization.iss']],
            $this->code(SettingsErrors::PREFIX_AUTHENTICATION, SettingsErrors::ERR_AUTHENTICATION_CUSTOM_CLAIM_EMPTY_KEY) => ['A custom claim has an empty key.', ['authorization.custom_claims']],
            $this->code(SettingsErrors::PREFIX_AUTHENTICATION, SettingsErrors::ERR_AUTHENTICATION_CUSTOM_CLAIM_PROTECTED_PAYLOAD) => ['A custom payload claim uses a reserved JWT claim name (exp, iat, iss, ...).', ['authorization.custom_claims.payload']],
            $this->code(SettingsErrors::PREFIX_AUTHENTICATION, SettingsErrors::ERR_AUTHENTICATION_CUSTOM_CLAIM_PROTECTED_HEADER) => ['A custom header claim uses a reserved JWT header name (alg, typ, kid).', ['authorization.custom_claims.header']],
            $this->code(SettingsErrors::PREFIX_REFRESH_TOKEN, SettingsErrors::ERR_AUTHENTICATION_REFRESH_TTL_ZERO) => ['The JWT refresh window must be greater than zero.', ['authorization.refresh_ttl']],
            $this->code(SettingsErrors::PREFIX_REFRESH_TOKEN, SettingsErrors::ERR_AUTHENTICATION_REFRESH_TOKEN_KEY_REQUIRED) => ['Refresh tokens are enabled but have no secret key. Set it on the plugin settings page (AI cannot set secrets) or disable refresh tokens.', ['authorization.refresh_token_enabled']],
            $this->code(SettingsErrors::PREFIX_AUTH_CODES, SettingsErrors::ERR_EMPTY_AUTH_CODES) => ['An endpoint requires an auth code but no auth code exists. Add one on the plugin settings page, or turn off the auth code requirement.', array_merge(['auth_codes.codes'], $flags)],
            $this->code(SettingsErrors::PREFIX_AUTH_CODES, SettingsErrors::ERR_INVALID_ROLE) => ['An auth code is linked to a role that does not exist.', ['auth_codes.codes']],
            $this->code(SettingsErrors::PREFIX_RESET_PASSWORD, ErrorCodes::ERR_MISSING_CODE_FROM_EMAIL_BODY) => ['The custom reset password email body must contain {{CODE}}.', ['reset_password.email_body', 'reset_password.flow']],
            $this->code(SettingsErrors::PREFIX_RESET_PASSWORD, ErrorCodes::ERR_EMPTY_CUSTOM_EMAIL_SUBJECT) => ['The custom reset password email subject is empty.', ['reset_password.email_subject', 'reset_password.flow']],
            $this->code(SettingsErrors::PREFIX_PROTECT_ENDPOINTS, SettingsErrors::ERR_EMPTY_SPECIFIC_ENDPOINT) => ['Endpoint protection is enabled with no endpoint rule.', ['protect_endpoint.enabled', 'protect_endpoint.default_action', 'protect_endpoint.rules_url']],
            $this->code(SettingsErrors::PREFIX_PROTECT_ENDPOINTS, SettingsErrors::ERR_PROTECTED_ROLES_RULE_MISSING_ROLES) => ['A "JWT + Roles" rule has no roles, or a role that does not exist.', ['protect_endpoint.rules_type', 'protect_endpoint.rules_roles']],
            $this->code(SettingsErrors::PREFIX_WEBHOOKS, SettingsErrors::ERR_WEBHOOKS_INVALID_URL) => ['A webhook URL is invalid or not HTTPS.', ['webhooks.items']],
            $this->code(SettingsErrors::PREFIX_WEBHOOKS, SettingsErrors::ERR_WEBHOOKS_INVALID_URL + 1) => ['Webhook log retention must be at least 1 day.', ['webhooks.logs.retention']],
            $this->code(SettingsErrors::PREFIX_AUDIT_LOGS, 1) => ['Audit log retention must be at least 1 day.', ['audit_log.retention_days']],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string[]}>
     */
    private function applicationEntries()
    {
        $apps    = SettingsErrors::PREFIX_APPLICATIONS;
        $entries = [];
        $byProvider = [
            'google'   => [
                SettingsErrors::ERR_GOOGLE_AT_LEAST_ONE_OPTION_ENABLED, SettingsErrors::ERR_GOOGLE_CLIENT_ID_REQUIRED,
                SettingsErrors::ERR_GOOGLE_CLIENT_SECRET_REQUIRED, SettingsErrors::ERR_GOOGLE_REDIRECT_URI_REQUIRED_FOR_EXCHANGE_CODE,
            ],
            'auth0'    => [
                SettingsErrors::ERR_AUTH0_AT_LEAST_ONE_OPTION_ENABLED, SettingsErrors::ERR_AUTH0_CLIENT_ID_REQUIRED,
                SettingsErrors::ERR_AUTH0_CLIENT_SECRET_REQUIRED, SettingsErrors::ERR_AUTH0_REDIRECT_URI_REQUIRED,
            ],
            'facebook' => [
                SettingsErrors::ERR_FACEBOOK_AT_LEAST_ONE_OPTION_ENABLED, SettingsErrors::ERR_FACEBOOK_CLIENT_ID_REQUIRED,
                SettingsErrors::ERR_FACEBOOK_CLIENT_SECRET_REQUIRED, SettingsErrors::ERR_FACEBOOK_REDIRECT_URI_REQUIRED,
            ],
            'github'   => [
                SettingsErrors::ERR_GITHUB_AT_LEAST_ONE_OPTION_ENABLED, SettingsErrors::ERR_GITHUB_CLIENT_ID_REQUIRED,
                SettingsErrors::ERR_GITHUB_CLIENT_SECRET_REQUIRED, SettingsErrors::ERR_GITHUB_REDIRECT_URI_REQUIRED,
            ],
        ];
        foreach ($byProvider as $slug => $codes) {
            $base = 'integrations.oauth.' . $slug;
            list($oneOption, $clientId, $clientSecret, $redirect) = $codes;
            $entries[$this->code($apps, $oneOption)]    = ['The provider is enabled but none of its flows (oauth, exchange code, exchange token/id_token) is enabled.', [$base]];
            $entries[$this->code($apps, $clientId)]     = ['The provider needs a client ID.', [$base . '.client_id']];
            $entries[$this->code($apps, $clientSecret)] = ['The provider needs a client secret. Set it on the plugin settings page (AI cannot set secrets).', [$base . '.enabled']];
            $entries[$this->code($apps, $redirect)]     = ['Exchange code is enabled but the redirect URI is empty. It must equal the one registered with the provider.', [$base . '.enable_exchange_code', $base . '.redirect_uri_exchange_code']];
        }
        $entries[$this->code($apps, SettingsErrors::ERR_AUTH0_DOMAIN_REQUIRED)] = ['Auth0 needs its tenant domain.', ['integrations.oauth.auth0.domain']];

        return $entries;
    }
}
