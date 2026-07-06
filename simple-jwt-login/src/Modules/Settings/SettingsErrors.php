<?php

namespace SimpleJWTLogin\Modules\Settings;

class SettingsErrors extends \Exception
{
    const PREFIX_LEEWAY = 1000;

    const PREFIX_DASHBOARD = 1;
    const PREFIX_GENERAL   = 2;
    const PREFIX_LOGIN     = 3;
    const PREFIX_REGISTER  = 4;
    const PREFIX_DELETE    = 5;
    const PREFIX_AUTHENTICATION = 6;
    const PREFIX_AUTH_CODES = 7;
    const PREFIX_HOOKS = 8;
    const PREFIX_CORS = 9;
    const PREFIX_RESET_PASSWORD = 10;
    const PREFIX_PROTECT_ENDPOINTS = 11;
    const PREFIX_APPLICATIONS = 12;
    const PREFIX_AUDIT_LOGS = 13;
    const PREFIX_WEBHOOKS   = 14;
    const PREFIX_REFRESH_TOKEN  = 15;
    const PREFIX_VALIDATE_TOKEN = 16;
    const PREFIX_REVOKE_TOKEN   = 17;
    const PREFIX_WEBHOOK_LOGS   = 18;
    const PREFIX_AUDIT_LOG_LOGS = 19;
    const PREFIX_API_KEYS          = 20;
    const PREFIX_3RD_PARTY_APPS    = 21;
    const PREFIX_JWT_DECODER       = 22;
    const PREFIX_REVOKED_TOKENS    = 23;

    #authentication
    const ERR_AUTHENTICATION_EMPTY_PAYLOAD = 1;
    const ERR_AUTHENTICATION_TTL = 2;
    const ERR_AUTHENTICATION_REFRESH_TTL_ZERO = 3;
    const ERR_AUTHENTICATION_REFRESH_TOKEN_KEY_REQUIRED = 4;
    const ERR_AUTHENTICATION_CUSTOM_CLAIM_PROTECTED_PAYLOAD = 5;
    const ERR_AUTHENTICATION_CUSTOM_CLAIM_PROTECTED_HEADER  = 6;
    const ERR_AUTHENTICATION_CUSTOM_CLAIM_EMPTY_KEY         = 7;
    const ERR_AUTHENTICATION_EMPTY_ISS = 8;

    #general
    const ERR_GENERAL_EMPTY_NAMESPACE = 1;
    const ERR_GENERAL_PRIVATE_KEY_MISSING_FROM_CODE_RS = 2;
    const ERR_GENERAL_PRIVATE_KEY_NOT_PRESENT_IN_CODE_HS = 3;
    const ERR_GENERAL_MISSING_PRIVATE_AND_PUBLIC_KEY = 4;
    const ERR_GENERAL_DECRYPTION_KEY_REQUIRED = 5;
    const ERR_GENERAL_GET_JWT_FROM = 7;
    const ERR_GENERAL_REQUEST_KEYS = 8;
    const ERR_GENERAL_MISSING_JWT_PAYLOAD_KEY = 9;

    #auth-codes
    const ERR_EMPTY_AUTH_CODES = 1;
    const ERR_INVALID_ROLE = 2;

    #login
    const ERR_LOGIN_INVALID_CUSTOM_URL = 1;

    #cors
    const ERR_CORS_NO_OPTION = 1;

    #register
    const ERR_REGISTER_MISSING_NEW_USER_PROFILE = 1;
    const ERR_REGISTER_INVALID_ROLE = 2;
    const ERR_REGISTER_RANDOM_PASS_LENGTH_NUMERIC = 3;
    const ERR_REGISTER_RANDOM_PASS_LENGTH_MIN_LENGTH = 4;
    const ERR_REGISTER_RANDOM_PASS_LENGTH_MAX_LENGTH = 5;

    #protect endpoints
    const ERR_EMPTY_SPECIFIC_ENDPOINT = 1;
    const ERR_PROTECTED_ROLES_RULE_MISSING_ROLES = 2;

    # Applications - Google
    const ERR_GOOGLE_AT_LEAST_ONE_OPTION_ENABLED = 1;
    const ERR_GOOGLE_CLIENT_ID_REQUIRED = 2;
    const ERR_GOOGLE_CLIENT_SECRET_REQUIRED = 3;
    const ERR_GOOGLE_REDIRECT_URI_REQUIRED_FOR_EXCHANGE_CODE = 4;

    # Applications - Auth0
    const ERR_AUTH0_AT_LEAST_ONE_OPTION_ENABLED = 6;
    const ERR_AUTH0_DOMAIN_REQUIRED             = 7;
    const ERR_AUTH0_CLIENT_ID_REQUIRED          = 8;
    const ERR_AUTH0_CLIENT_SECRET_REQUIRED      = 9;
    const ERR_AUTH0_REDIRECT_URI_REQUIRED       = 10;

    # Applications - Facebook
    const ERR_FACEBOOK_AT_LEAST_ONE_OPTION_ENABLED = 11;
    const ERR_FACEBOOK_CLIENT_ID_REQUIRED          = 12;
    const ERR_FACEBOOK_CLIENT_SECRET_REQUIRED      = 13;
    const ERR_FACEBOOK_REDIRECT_URI_REQUIRED       = 14;

    # Applications - GitHub
    const ERR_GITHUB_AT_LEAST_ONE_OPTION_ENABLED = 15;
    const ERR_GITHUB_CLIENT_ID_REQUIRED          = 16;
    const ERR_GITHUB_CLIENT_SECRET_REQUIRED      = 17;
    const ERR_GITHUB_REDIRECT_URI_REQUIRED       = 18;

    # Webhooks
    const ERR_WEBHOOKS_INVALID_URL = 1;

    /**
     * @param int $sectionPrefix
     * @param int $code
     * @return float|int
     */
    public function generateCode($sectionPrefix, $code)
    {
        return (self::PREFIX_LEEWAY * $sectionPrefix) + $code;
    }

    /**
     * @param int $errorCode
     * @return int
     */
    public function getSectionFromErrorCode($errorCode)
    {
        if (empty($errorCode)) {
            return 0;
        }

        return (int) ($errorCode / self::PREFIX_LEEWAY);
    }
}
