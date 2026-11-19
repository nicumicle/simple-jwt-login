<?php

namespace SimpleJWTLogin\Helpers;

use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\JWTException;
use SimpleJWTLogin\Exceptions\ValidationException;

class StatusCodeHelper
{
    /** @var list<int> */
    private static $codes401 = [
        // JWT structure / signature
        ErrorCodes::ERR_EMPTY_KEY,
        ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS,
        ErrorCodes::ERR_INVALID_HEADER_ENCODING,
        ErrorCodes::ERR_INVALID_CLAIMS_ENCODING,
        ErrorCodes::ERR_INVALID_SIGNATURE_ENCODING,
        ErrorCodes::ERR_EMPTY_ALGORITHM,
        ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED,
        ErrorCodes::ERR_ALGORITHM_NOT_ALLOWED,
        ErrorCodes::ERR_INVALID_KID,
        ErrorCodes::ERR_EMPTY_KID,
        ErrorCodes::ERR_SIGNATURE_VERIFICATION_FAILED,
        ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED_IN_SIGNATURE,
        // Token timing
        ErrorCodes::ERR_TOKEN_NBF,
        ErrorCodes::ERR_TOKEN_IAT,
        ErrorCodes::ERR_TOKEN_EXPIRED,
        ErrorCodes::ERR_JWT_REFRESH_JWT_TOO_OLD,
        // Revoked
        ErrorCodes::ERR_REVOKED_TOKEN,
        // Issued for another audience
        ErrorCodes::ERR_JWT_AUDIENCE_MISMATCH,
        // Missing / invalid JWT
        ErrorCodes::ERR_LOGIN_INVALID_JWT,
        ErrorCodes::ERR_DELETE_MISSING_JWT,
        ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH,
        ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE,
        ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT,
        // Wrong credentials
        ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS,
        // Invalid auth codes / keys
        ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED,
        ErrorCodes::ERR_REGISTER_INVALID_AUTH_KEY,
        ErrorCodes::ERR_RESET_PASSWORD_INVALID_AUTH_KEY,
        // Invalid nonce
        ErrorCodes::ERR_INVALID_NONCE,
        // OAuth invalid token / code
        ErrorCodes::ERR_GOOGLE_INVALID_CODE,
        ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN,
        ErrorCodes::ERR_AUTH0_INVALID_CODE,
        ErrorCodes::ERR_AUTH0_INVALID_TOKEN,
        // IIS login
        ErrorCodes::ERR_INVALID_IIS_LOGIN,
        // JWT cannot be used to change password
        ErrorCodes::ERR_JWT_CANNOT_CHANGE_PASSWORD,
        // Facebook / GitHub OAuth invalid code or token
        ErrorCodes::ERR_FACEBOOK_INVALID_CODE,
        ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN,
        ErrorCodes::ERR_GITHUB_INVALID_CODE,
        ErrorCodes::ERR_GITHUB_INVALID_TOKEN,
        // Two-factor verification
        ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REQUIRED,
        ErrorCodes::ERR_TWO_FACTOR_INVALID_NONCE,
        ErrorCodes::ERR_TWO_FACTOR_INVALID_CODE,
        ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REJECTED,
        // JWT without the claim used to find the user
        ErrorCodes::ERR_UNABLE_TO_FIND_PROPERTY_FOR_USER_IN_JWT,
        ErrorCodes::ERR_JWT_PARAMETER_FOR_USER_NOT_FOUND,
        // Google id_token issued for another client
        ErrorCodes::ERR_GOOGLE_ID_TOKEN_INVALID_AUDIENCE,
    ];

    /** @var list<int> */
    private static $codes403 = [
        // Feature not enabled
        ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED,
        ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED,
        ErrorCodes::ERR_DELETE_IS_NOT_ENABLED,
        ErrorCodes::ERR_AUTHENTICATION_IS_NOT_ENABLED,
        ErrorCodes::ERR_RESET_PASSWORD_IS_NOT_ALLOWED,
        ErrorCodes::ERR_REFRESH_TOKEN_NOT_ENABLED,
        ErrorCodes::ERR_VALIDATE_TOKEN_NOT_ENABLED,
        ErrorCodes::ERR_REVOKE_TOKEN_NOT_ENABLED,
        // IP blocked
        ErrorCodes::ERR_IP_IS_NOT_ALLOWED_TO_LOGIN,
        ErrorCodes::ERR_REGISTER_IP_IS_NOT_ALLOWED,
        ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP,
        // OAuth provider inactive
        ErrorCodes::ERR_OAUTH_PROVIDER_NOT_ACTIVE,
        // Two-factor plugin inactive
        ErrorCodes::ERR_TWO_FACTOR_NOT_ACTIVE,
        // API key unauthorized
        ErrorCodes::ERR_API_KEY_UNAUTHORIZED,
        // Endpoint role restriction
        ErrorCodes::ERR_PROTECT_ENDPOINTS_INSUFFICIENT_ROLE,
        // Revoked token management is administrator only
        ErrorCodes::ERR_REVOKED_TOKEN_UNAUTHORIZED,
        // OAuth account email not verified
        ErrorCodes::ERR_AUTH0_EMAIL_NOT_VERIFIED,
        ErrorCodes::ERR_GITHUB_EMAIL_NOT_VERIFIED,
        ErrorCodes::ERR_GOOGLE_ID_TOKEN_EMAIL_NOT_VERIFIED,
    ];

    /** @var list<int> */
    private static $codes404 = [
        ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND,
        ErrorCodes::ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD,
        ErrorCodes::ERR_GOOGLE_USER_NOT_FOUND,
        ErrorCodes::ERR_AUTH0_USER_NOT_FOUND,
        ErrorCodes::ERR_API_KEY_NOT_FOUND,
        ErrorCodes::ERR_FACEBOOK_USER_NOT_FOUND,
        ErrorCodes::ERR_GITHUB_USER_NOT_FOUND,
        ErrorCodes::ERR_REVOKED_TOKEN_NOT_FOUND,
    ];

    /** @var list<int> */
    private static $codes405 = [
        ErrorCodes::ERR_ROUTE_CALLED_WITH_INVALID_METHOD,
    ];

    /** @var list<int> */
    private static $codes409 = [
        ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS,
    ];

    /** @var list<int> */
    private static $codes422 = [
        ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS,
        ErrorCodes::ERR_REGISTER_DOMAIN_FOR_USER,
        ErrorCodes::ERR_INVALID_RESET_PASSWORD_CODE,
        ErrorCodes::ERR_RESET_PASSWORD_INVALID_FLOW,
        ErrorCodes::ERR_EMPTY_CUSTOM_EMAIL_SUBJECT,
        ErrorCodes::ERR_JWT_IS_MISSING,
        ErrorCodes::ERR_AUTH_CODE_REQUIRED,
        // Missing or invalid request parameters
        ErrorCodes::ERR_MISSING_CODE_FOR_CHANGE_PASSWORD,
        ErrorCodes::ERR_OAUTH_INVALID_PROVIDER,
        ErrorCodes::ERR_MISSING_GOOGLE_PARAM,
        ErrorCodes::ERR_MISSING_AUTH0_PARAM,
        ErrorCodes::ERR_MISSING_FACEBOOK_PARAM,
        ErrorCodes::ERR_MISSING_GITHUB_PARAM,
        ErrorCodes::ERR_API_KEY_MISSING_NAME,
        ErrorCodes::ERR_API_KEY_MISSING_PERMISSIONS,
        ErrorCodes::ERR_API_KEY_INVALID_PERMISSION,
    ];

    /** @var list<int> */
    private static $codes429 = [
        ErrorCodes::ERR_TWO_FACTOR_RATE_LIMITED,
    ];

    /** @var list<int> */
    private static $codes500 = [
        // OpenSSL / signing failures
        ErrorCodes::ERR_OPENSSL_SIGN,
        ErrorCodes::ERR_UNSUPPORTED_SIGN_FUNCTION,
        ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED_VERIFY,
        ErrorCodes::ERR_OPEN_SSL_VERIFY,
        ErrorCodes::ERR_JSON_ENCODE_NON_NULL_INPUT,
        // DB / resource creation failures
        ErrorCodes::ERR_CREATE_USER_ERROR,
        ErrorCodes::ERR_API_KEY_CREATE_FAILED,
        ErrorCodes::ERR_API_KEY_UPDATE_FAILED,
        ErrorCodes::ERR_API_KEY_REVOKE_FAILED,
        ErrorCodes::ERR_API_KEY_DELETE_FAILED,
        ErrorCodes::ERR_REFRESH_TOKEN_STORE_FAILED,
        ErrorCodes::ERR_REVOKED_TOKEN_DELETE_FAILED,
        ErrorCodes::ERR_TWO_FACTOR_NONCE_CREATE_FAILED,
        // Unknown
        ErrorCodes::ERR_UNKNOWN_ERROR,
    ];

    /**
     * @param \Throwable $exception
     * @param int $defaultStatusCode
     * @return int
     */
    public static function getStatusCodeFromException($exception, $defaultStatusCode = 500)
    {
        if (!($exception instanceof \Exception)) {
            return 500;
        }

        if ($exception instanceof JWTException) {
            return 400;
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        $map = [
            401 => self::$codes401,
            403 => self::$codes403,
            404 => self::$codes404,
            405 => self::$codes405,
            409 => self::$codes409,
            422 => self::$codes422,
            429 => self::$codes429,
            500 => self::$codes500,
        ];

        foreach ($map as $statusCode => $codes) {
            if (in_array($exception->getCode(), $codes, true)) {
                return $statusCode;
            }
        }

        return $defaultStatusCode;
    }
}
