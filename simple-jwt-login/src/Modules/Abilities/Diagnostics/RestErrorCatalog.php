<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use SimpleJWTLogin\ErrorCodes;

/**
 * Hand-written guidance for the error_code values returned by the plugin REST
 * endpoints: likely causes plus the settings paths an agent should inspect.
 */
class RestErrorCatalog
{
    /**
     * Codes 1..22 are thrown by the bundled JWT library (HTTP 400).
     */
    const LAST_JWT_LIBRARY_CODE = 22;

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
     * @return array<int, array{0: string, 1: string[]}>
     */
    protected function entries()
    {
        return $this->jwtEntries() + $this->endpointEntries() + $this->oauthEntries() + $this->otherEntries();
    }

    /**
     * @return array<int, array{0: string, 1: string[]}>
     */
    private function jwtEntries()
    {
        $keys  = ['general.jwt_algorithm', 'general.decryption_source', 'general.decryption_key_base64'];
        $input = [
            'general.request_jwt_url', 'general.request_jwt_header', 'general.request_jwt_cookie',
            'general.request_jwt_session', 'general.request_keys', 'general.request_jwt_header_require_bearer',
        ];

        return [
            ErrorCodes::ERR_EMPTY_KEY => ['The JWT verification key is empty. Set the decryption key on the plugin settings page (secrets cannot be set by AI) or use a key source that defines it in code.', $keys],
            ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS => ['The value sent as JWT is not a JWT (it must have 3 dot-separated parts). Often the client sends "Bearer " twice, a refresh token, or the JWT is read from the wrong input source.', $input],
            ErrorCodes::ERR_INVALID_HEADER_ENCODING => ['The JWT header is not valid base64url JSON. The token was truncated or altered.', []],
            ErrorCodes::ERR_INVALID_CLAIMS_ENCODING => ['The JWT payload is not valid base64url JSON. The token was truncated or altered.', []],
            ErrorCodes::ERR_INVALID_SIGNATURE_ENCODING => ['The JWT signature is not valid base64url. The token was truncated or altered.', []],
            ErrorCodes::ERR_EMPTY_ALGORITHM => ['The JWT header has no "alg".', ['general.jwt_algorithm']],
            ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED => ['The "alg" in the JWT header is not supported by the plugin.', ['general.jwt_algorithm', 'jwt_rules.rules']],
            ErrorCodes::ERR_ALGORITHM_NOT_ALLOWED => ['The JWT was signed with a different algorithm than the configured one. Align general.jwt_algorithm (or a matching JWT rule) with the issuer.', ['general.jwt_algorithm', 'jwt_rules.rules']],
            ErrorCodes::ERR_INVALID_KID => ['The "kid" in the JWT header does not match a configured key.', ['jwt_rules.rules']],
            ErrorCodes::ERR_EMPTY_KID => ['The JWT header has no "kid" but one is needed to choose the key.', ['jwt_rules.rules']],
            ErrorCodes::ERR_SIGNATURE_VERIFICATION_FAILED => ['The signature does not match the configured key: the JWT was issued by another site/service, the key changed, or the base64 flag does not match how the key is stored. For third-party issuers, add a JWT rule for their "iss".', array_merge($keys, ['jwt_rules.rules'])],
            ErrorCodes::ERR_TOKEN_NBF => ['The JWT "nbf" is in the future. Check the clock of the issuing server.', []],
            ErrorCodes::ERR_TOKEN_IAT => ['The JWT "iat" is in the future. Check the clock of the issuing server.', []],
            ErrorCodes::ERR_TOKEN_EXPIRED => ['The JWT expired. Get a new one (/auth) or refresh it (/auth/refresh). Increase authorization.ttl for longer-lived tokens.', ['authorization.ttl', 'authorization.refresh_ttl', 'authorization.refresh_token_enabled']],
            ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED_IN_SIGNATURE => ['The configured algorithm cannot sign tokens.', ['general.jwt_algorithm']],
            ErrorCodes::ERR_OPENSSL_SIGN => ['OpenSSL could not sign with the private key: the RS key is missing, malformed, or the base64 flag is wrong.', $keys],
            ErrorCodes::ERR_UNSUPPORTED_SIGN_FUNCTION => ['The server PHP lacks the function needed for this algorithm (OpenSSL / hash_hmac).', ['general.jwt_algorithm']],
            ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED_VERIFY => ['The configured algorithm cannot verify tokens.', ['general.jwt_algorithm']],
            ErrorCodes::ERR_OPEN_SSL_VERIFY => ['OpenSSL failed to verify the signature: the public key is malformed or the base64 flag is wrong.', $keys],
            ErrorCodes::ERR_JSON_DECODE_NON_NULL_INPUT => ['The JWT JSON could not be decoded.', []],
            ErrorCodes::ERR_JSON_ENCODE_NON_NULL_INPUT => ['The JWT payload could not be JSON-encoded (often invalid UTF-8 in user data).', ['authorization.jwt_payload']],
            ErrorCodes::ERR_UNKNOWN_ERROR => ['Unknown JSON error while handling the JWT.', []],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string[]}>
     */
    private function endpointEntries()
    {
        $input  = ['general.request_jwt_url', 'general.request_jwt_header', 'general.request_jwt_cookie', 'general.request_jwt_session', 'general.request_keys'];
        $authCd = ['auth_codes.key', 'auth_codes.codes'];

        return [
            ErrorCodes::ERR_JWT_IS_MISSING => ['No JWT was found in the request. Check which input sources are enabled and the parameter/header names.', $input],
            ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND => ['No WordPress user matches the JWT claim used for login.', ['login.login_by', 'login.login_by_parameter']],
            ErrorCodes::ERR_LOGIN_INVALID_JWT => ['The JWT sent to autologin is invalid.', ['login.enabled']],
            ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED => ['Autologin is disabled.', ['login.enabled']],
            ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED => ['The auth code sent in the request does not match any configured, non-expired auth code. Check the request parameter name and auth code expiry.', $authCd],
            ErrorCodes::ERR_IP_IS_NOT_ALLOWED_TO_LOGIN => ['The client IP is not in the autologin IP whitelist. Behind a proxy, also check general.security.trust_ip_headers.', ['login.ip_whitelist', 'general.security.trust_ip_headers']],
            ErrorCodes::ERR_UNABLE_TO_FIND_PROPERTY_FOR_USER_IN_JWT => ['The JWT does not contain the claim configured to identify the user.', ['login.login_by', 'login.login_by_parameter', 'jwt_rules.rules']],
            ErrorCodes::ERR_JWT_PARAMETER_FOR_USER_NOT_FOUND => ['The JWT does not contain the claim configured to identify the user.', ['login.login_by', 'login.login_by_parameter']],
            ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED => ['User registration through the API is disabled.', ['register.enabled']],
            ErrorCodes::ERR_REGISTER_INVALID_AUTH_KEY => ['Registration requires an auth code and the one provided is wrong or missing.', array_merge(['register.auth_code'], $authCd)],
            ErrorCodes::ERR_REGISTER_IP_IS_NOT_ALLOWED => ['The client IP is not in the registration IP whitelist.', ['register.ip_whitelist', 'general.security.trust_ip_headers']],
            ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD => ['The register request is missing email or password (password can be generated with register.random_password).', ['register.random_password']],
            ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS => ['The email or username sent to /users is invalid or too long.', []],
            ErrorCodes::ERR_REGISTER_DOMAIN_FOR_USER => ['The email domain is not in the registration domain whitelist.', ['register.domain_whitelist']],
            ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS => ['A user with this email or username already exists.', []],
            ErrorCodes::ERR_DELETE_IS_NOT_ENABLED => ['Deleting users through the API is disabled.', ['delete_user.enabled']],
            ErrorCodes::ERR_DELETE_MISSING_AUTH_KEY => ['Deleting users requires an auth code and none was sent.', array_merge(['delete_user.auth_code'], $authCd)],
            ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP => ['The client IP is not allowed for this endpoint (delete user or authentication IP whitelist).', ['delete_user.ip_whitelist', 'authorization.ip_whitelist', 'general.security.trust_ip_headers']],
            ErrorCodes::ERR_DELETE_MISSING_JWT => ['The delete request has no jwt parameter.', $input],
            ErrorCodes::ERR_INVALID_ROUTE_METHOD => ['The endpoint was called with an unsupported HTTP method.', []],
            ErrorCodes::ERR_INVALID_ROUTE_NAME => ['Unknown plugin route. Check the route namespace in the URL.', ['general.route_namespace']],
            ErrorCodes::ERR_AUTHENTICATION_IS_NOT_ENABLED => ['The /auth endpoint (issue JWT) is disabled.', ['authorization.enabled']],
            ErrorCodes::ERR_AUTHENTICATION_MISSING_EMAIL => ['The /auth request has no email, username or login parameter.', []],
            ErrorCodes::ERR_AUTHENTICATION_MISSING_PASSWORD => ['The /auth request has no password or password_hash. If the client base64-encodes the password, check authorization.password_base64.', ['authorization.password_base64', 'authorization.password_hash_enabled']],
            ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS => ['Wrong username/email or password. If passwords are sent base64-encoded, authorization.password_base64 must be on (and off otherwise).', ['authorization.password_base64']],
            ErrorCodes::ERR_JWT_REFRESH_NULL_PAYLOAD => ['The JWT sent for refresh has an empty payload.', ['authorization.jwt_payload']],
            ErrorCodes::ERR_JWT_REFRESH_JWT_TOO_OLD => ['The JWT is past the refresh window and cannot be refreshed; authenticate again.', ['authorization.refresh_ttl']],
            ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH => ['The refresh request has no valid refresh token.', ['authorization.refresh_token_enabled', 'authorization.refresh_ttl']],
            ErrorCodes::ERR_CREATE_USER_ERROR => ['WordPress refused to create the user (see the message for the WordPress error).', ['register.new_user_profile', 'register.allowed_user_meta']],
            ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE => ['The request has no jwt (or code) parameter.', $input],
            ErrorCodes::ERR_GET_USER_ID_FROM_JWT => ['The user referenced by the JWT does not exist anymore.', ['login.login_by']],
            ErrorCodes::ERR_REVOKED_TOKEN => ['The JWT was revoked, is invalid, its user is gone, or the user lacks the role required by a protect-endpoint rule (see the message).', ['authorization.revoke_token_enabled', 'protect_endpoint.rules_roles']],
            ErrorCodes::ERR_RESET_PASSWORD_IS_NOT_ALLOWED => ['The reset password endpoint is disabled.', ['reset_password.enabled']],
            ErrorCodes::ERR_ROUTE_CALLED_WITH_INVALID_METHOD => ['The reset password route was called with the wrong HTTP method (POST sends the code, PUT changes the password).', []],
            ErrorCodes::ERR_RESET_PASSWORD_INVALID_AUTH_KEY => ['Reset password requires an auth code and the one provided is wrong.', array_merge(['reset_password.auth_code'], $authCd)],
            ErrorCodes::ERR_MISSING_EMAIL_FOR_CHANGE_PASSWORD => ['The change password request has no email.', []],
            ErrorCodes::ERR_MISSING_CODE_FOR_CHANGE_PASSWORD => ['The change password request has no code (or jwt).', []],
            ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_CHANGE_PASSWORD => ['The change password request has no new_password.', []],
            ErrorCodes::ERR_INVALID_RESET_PASSWORD_CODE => ['The reset code is wrong or was already used.', ['reset_password.flow']],
            ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_RESET_PASSWORD => ['The new password or email is missing, or new_password is not valid base64 while base64 passwords are enabled.', ['authorization.password_base64']],
            ErrorCodes::ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD => ['No user exists with this email.', []],
            ErrorCodes::ERR_RESET_PASSWORD_INVALID_FLOW => ['reset_password.flow has an unknown value (0 = save code only, 1 = default WP email, 2 = custom email).', ['reset_password.flow']],
            ErrorCodes::ERR_MISSING_CODE_FROM_EMAIL_BODY => ['The custom reset email body must contain {{CODE}}.', ['reset_password.email_body', 'reset_password.flow']],
            ErrorCodes::ERR_INVALID_NONCE => ['The admin form nonce expired. Reload the settings page and save again.', []],
            ErrorCodes::ERR_INVALID_IIS_LOGIN => ['The JWT "iss" is not in the autologin issuer whitelist.', ['login.iss_whitelist', 'jwt_rules.rules']],
            ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT => ['The endpoint is protected and the request has no valid JWT. Either send a JWT or adjust the protect-endpoint rules.', array_merge(['protect_endpoint.enabled', 'protect_endpoint.default_action', 'protect_endpoint.rules_url', 'protect_endpoint.rules_type'], $input)],
            ErrorCodes::ERR_EMPTY_CUSTOM_EMAIL_SUBJECT => ['The custom reset email subject is empty.', ['reset_password.email_subject']],
            ErrorCodes::ERR_REFRESH_TOKEN_NOT_ENABLED => ['The refresh token endpoint is disabled.', ['authorization.refresh_token_enabled']],
            ErrorCodes::ERR_VALIDATE_TOKEN_NOT_ENABLED => ['The validate token endpoint is disabled.', ['authorization.validate_token_enabled']],
            ErrorCodes::ERR_REVOKE_TOKEN_NOT_ENABLED => ['The revoke token endpoint is disabled.', ['authorization.revoke_token_enabled']],
            ErrorCodes::ERR_JWT_CANNOT_CHANGE_PASSWORD => ['This JWT is not allowed to change the password (it belongs to another user or lacks the right claims).', ['reset_password.enabled']],
            ErrorCodes::ERR_AUTH_CODE_REQUIRED => ['This endpoint requires an auth code and the request has none. Send it in the auth_codes.key parameter, or disable the auth code requirement for the endpoint.', array_merge($authCd, ['login.auth_code', 'register.auth_code', 'delete_user.auth_code', 'authorization.auth_code', 'reset_password.auth_code'])],
            ErrorCodes::ERR_INVALID_EMAIL_FOR_CHANGE_PASSWORD => ['The email parameter is not a valid email address.', []],
            ErrorCodes::ERR_INVALID_EMAIL_FOR_RESET_PASSWORD => ['The email parameter is not a valid email address.', []],
            ErrorCodes::ERR_AUTHENTICATION_PASSWORD_HASH_NOT_ALLOWED => ['password_hash was sent but authentication with password_hash is disabled.', ['authorization.password_hash_enabled']],
            ErrorCodes::ERR_PROTECT_ENDPOINTS_INSUFFICIENT_ROLE => ['The user in the JWT lacks a role required by a protect-endpoint rule.', ['protect_endpoint.rules_url', 'protect_endpoint.rules_type', 'protect_endpoint.rules_roles']],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string[]}>
     */
    private function oauthEntries()
    {
        $google = ['integrations.oauth.google'];
        $auth0  = ['integrations.oauth.auth0'];
        $fbook  = ['integrations.oauth.facebook'];
        $github = ['integrations.oauth.github'];

        return [
            ErrorCodes::ERR_OAUTH_INVALID_PROVIDER => ['The provider parameter is not one of google, auth0, facebook, github.', []],
            ErrorCodes::ERR_OAUTH_PROVIDER_NOT_ACTIVE => ['This OAuth provider is disabled. Enable integrations.oauth.<provider>.enabled and the flow used (oauth / exchange code / exchange token).', ['integrations.oauth']],
            ErrorCodes::ERR_MISSING_GOOGLE_PARAM => ['The Google request is missing code or id_token.', $google],
            ErrorCodes::ERR_GOOGLE_INVALID_CODE => ['Google rejected the authorization code: it expired, was used twice, or the redirect URI differs from the one registered in Google.', $google],
            ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN => ['The Google id_token is invalid or has a wrong issuer.', $google],
            ErrorCodes::ERR_GOOGLE_USER_NOT_FOUND => ['No WordPress user matches the Google email and user creation is off.', ['integrations.oauth.google.create_user_if_not_exists']],
            ErrorCodes::ERR_GOOGLE_ID_TOKEN_INVALID_AUDIENCE => ['The id_token was issued for another Google client ID.', ['integrations.oauth.google.client_id']],
            ErrorCodes::ERR_GOOGLE_ID_TOKEN_EMAIL_NOT_VERIFIED => ['The Google account email is not verified.', $google],
            ErrorCodes::ERR_MISSING_AUTH0_PARAM => ['The Auth0 request is missing code or token.', $auth0],
            ErrorCodes::ERR_AUTH0_INVALID_CODE => ['Auth0 rejected the authorization code (expired, reused, or wrong redirect URI).', $auth0],
            ErrorCodes::ERR_AUTH0_INVALID_TOKEN => ['Auth0 did not accept the token. Check the Auth0 domain and client ID.', ['integrations.oauth.auth0.domain', 'integrations.oauth.auth0.client_id']],
            ErrorCodes::ERR_AUTH0_USER_NOT_FOUND => ['No WordPress user matches the Auth0 email and user creation is off.', ['integrations.oauth.auth0.create_user_if_not_exists']],
            ErrorCodes::ERR_AUTH0_EMAIL_NOT_VERIFIED => ['The Auth0 email is not verified.', ['integrations.oauth.auth0.allow_unverified_email']],
            ErrorCodes::ERR_MISSING_FACEBOOK_PARAM => ['The Facebook request is missing code or token.', $fbook],
            ErrorCodes::ERR_FACEBOOK_INVALID_CODE => ['Facebook rejected the authorization code (expired, reused, or wrong redirect URI).', $fbook],
            ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN => ['Facebook did not accept the token, or the app lacks the email permission.', $fbook],
            ErrorCodes::ERR_FACEBOOK_USER_NOT_FOUND => ['No WordPress user matches the Facebook email and user creation is off.', ['integrations.oauth.facebook.create_user_if_not_exists']],
            ErrorCodes::ERR_MISSING_GITHUB_PARAM => ['The GitHub request is missing code or token.', $github],
            ErrorCodes::ERR_GITHUB_INVALID_CODE => ['GitHub rejected the authorization code (expired, reused, or wrong redirect URI).', $github],
            ErrorCodes::ERR_GITHUB_INVALID_TOKEN => ['GitHub did not accept the token or returned no usable email.', $github],
            ErrorCodes::ERR_GITHUB_USER_NOT_FOUND => ['No WordPress user matches the GitHub email and user creation is off.', ['integrations.oauth.github.create_user_if_not_exists']],
            ErrorCodes::ERR_GITHUB_EMAIL_NOT_VERIFIED => ['The GitHub account has no verified primary email.', ['integrations.oauth.github.allow_unverified_email']],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string[]}>
     */
    private function otherEntries()
    {
        $apiKeys = ['api_keys.enabled', 'api_keys.allow_user_api_keys'];
        $twoFa   = ['integrations.3rdparty.two_factor'];

        return [
            ErrorCodes::ERR_API_KEY_UNAUTHORIZED => ['The caller may not manage this API key or assign this permission (log in, or enable user API keys).', $apiKeys],
            ErrorCodes::ERR_API_KEY_MISSING_NAME => ['API keys need a name.', []],
            ErrorCodes::ERR_API_KEY_MISSING_PERMISSIONS => ['API keys need at least one permission.', []],
            ErrorCodes::ERR_API_KEY_INVALID_PERMISSION => ['Unknown API key permission.', []],
            ErrorCodes::ERR_API_KEY_CREATE_FAILED => ['The database insert for the API key failed.', []],
            ErrorCodes::ERR_API_KEY_NOT_FOUND => ['No API key has this id.', []],
            ErrorCodes::ERR_API_KEY_UPDATE_FAILED => ['The database update for the API key failed.', []],
            ErrorCodes::ERR_API_KEY_REVOKE_FAILED => ['Revoking the API key failed (already revoked or missing).', []],
            ErrorCodes::ERR_API_KEY_DELETE_FAILED => ['Deleting the API key failed.', []],
            ErrorCodes::ERR_TWO_FACTOR_NOT_ACTIVE => ['Two-factor is enabled in the plugin but no supported 2FA plugin is active.', $twoFa],
            ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REQUIRED => ['The 2FA verify call needs the interim JWT returned by /auth.', $twoFa],
            ErrorCodes::ERR_TWO_FACTOR_INVALID_NONCE => ['The two-factor session expired. Authenticate again (interim_ttl controls its lifetime).', ['integrations.3rdparty.two_factor.interim_ttl']],
            ErrorCodes::ERR_TWO_FACTOR_RATE_LIMITED => ['Too many wrong 2FA codes; wait before retrying.', []],            ErrorCodes::ERR_TWO_FACTOR_INVALID_CODE => ['The two-factor code is missing or wrong.', []],
            ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REJECTED => ['An interim (pre-2FA) JWT was used as a normal JWT. Complete the 2FA verify step first.', $twoFa],
            ErrorCodes::ERR_REVOKED_TOKEN_UNAUTHORIZED => ['Only administrators can manage revoked tokens.', []],
            ErrorCodes::ERR_REVOKED_TOKEN_NOT_FOUND => ['No revoked token entry has this id.', []],
            ErrorCodes::ERR_REVOKED_TOKEN_DELETE_FAILED => ['Deleting the revoked token entry failed.', []],
            ErrorCodes::ERR_ABILITY_UNKNOWN_SECTION => ['The settings section does not exist. Call simple-jwt-login/get-settings to list sections.', []],
            ErrorCodes::ERR_ABILITY_UNKNOWN_SETTING => ['The setting key does not exist in that section. Only existing keys can be changed.', []],
            ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE => ['The setting is a secret or human-only (AI access level). Change it on the plugin settings page.', []],
            ErrorCodes::ERR_ABILITY_INVALID_VALUE => ['The value has the wrong type or the list operation is malformed.', []],
            ErrorCodes::ERR_ABILITY_WEBHOOK_NOT_FOUND => ['The webhook index and URL do not match. List the webhooks again.', ['webhooks.items']],
            ErrorCodes::ERR_ABILITY_INVALID_WEBHOOK => ['A webhook needs an HTTPS URL and at least one valid event.', ['webhooks.items']],
            ErrorCodes::ERR_ABILITY_LIST_ITEM_MISMATCH => ['The list item at that index no longer matches "match". Read the settings again.', []],
            ErrorCodes::ERR_ABILITY_UNKNOWN_ERROR_CODE => ['The error code is not a Simple JWT Login code.', []],
            ErrorCodes::ERR_ABILITY_PERMISSION_DENIED  => ['The action needs an AI permission the administrator has not granted (e.g. "Allow changing roles"). Ask the administrator to enable it in Integrations > AI Abilities (MCP); AI agents cannot change these options.', ['integrations.3rdparty.ai_abilities']],
        ];
    }
}
