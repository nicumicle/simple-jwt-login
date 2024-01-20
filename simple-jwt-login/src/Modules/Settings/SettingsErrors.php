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

    #authentication
    const ERR_AUTHENTICATION_EMPTY_PAYLOAD = 1;
    const ERR_AUTHENTICATION_TTL = 2;
    const ERR_AUTHENTICATION_REFRESH_TTL_ZERO = 3;

    #general
    const ERR_GENERAL_EMPTY_NAMESPACE = 1;
    const ERR_GENERAL_PRIVATE_KEY_MISSING_FROM_CODE_RS = 2;
    const ERR_GENERAL_PRIVATE_KEY_NOT_PRESENT_IN_CODE_HS = 3;
    const ERR_GENERAL_MISSING_PRIVATE_AND_PUBLIC_KEY = 4;
    const ERR_GENERAL_DECRYPTION_KEY_REQUIRED = 5;
    const ERR_GENERAL_GET_JWT_FROM = 7;
    const ERR_GENERAL_REQUEST_KEYS = 8;

    #auth-codes
    const ERR_EMPTY_AUTH_CODES = 1;
    const ERR_INVALID_ROLE = 2;

    #login
    const ERR_LOGIN_MISSING_JWT_PARAMETER_KEY = 1;
    const ERR_LOGIN_INVALID_CUSTOM_URL = 2;

    #delete
    const ERR_DELETE_MISSING_JWT_PARAM = 1;

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

    # Applications
    const ERR_GOOGLE_AT_LEAST_ONE_OPTION_ENABLED = 1;
    const ERR_GOOGLE_CLIENT_ID_REQUIRED = 2;
    const ERR_GOOGLE_CLIENT_SECRET_REQUIRED = 3;
    const ERR_GOOGLE_REDIRECT_URI_REQUIRED_FOR_EXCHANGE_CODE = 4;
    const ERR_GOOGLE_REDIRECT_URI_REQUIRED_FOR_EXCHANGE_ID_TOKEN = 5;


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

        return intval($errorCode / self::PREFIX_LEEWAY);
    }
}
