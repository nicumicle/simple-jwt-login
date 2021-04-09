<?php

namespace SimpleJWTLogin;

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

    #login
    const ERR_LOGIN_MISSING_JWT_PARAMETER_KEY = 1;
    const ERR_LOGIN_INVALID_CUSTOM_URL = 2;

    #delete
    const ERR_DELETE_MISSING_JWT_PARAM = 1;

    #cors
    const ERR_CORS_NO_OPTION = 1;

    #register
    const ERR__REGISTER_MISSING_NEW_USER_PROFILE = 1;

    /**
     * @param int $sectionPrefix
     * @param int $code
     * @return float|int
     */
    public static function generateCode($sectionPrefix, $code){
        return (self::PREFIX_LEEWAY * $sectionPrefix) + $code;
    }

    /**
     * @param int $errorCode
     * @return int
     */
    public static function getSectionFromErrorCode($errorCode){

        if(empty($errorCode)){
            return 0;
        }

        return intval($errorCode / self::PREFIX_LEEWAY);
    }

}