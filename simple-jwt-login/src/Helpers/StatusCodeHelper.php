<?php

namespace SimpleJWTLogin\Helpers;

use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\JWTException;
use SimpleJWTLogin\Exceptions\ValidationException;

class StatusCodeHelper
{
    /** @var list<int> */
    private static array $codes401 = [
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
        // Missing / invalid JWT
        ErrorCodes::ERR_LOGIN_INVALID_JWT,
        ErrorCodes::ERR_DELETE_MISSING_JWT,
        ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH,
        ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE,
        ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT,
        // Wrong credentials
        ErrorCodes::AUTHENTICATION_WRONG_CREDENTIALS,
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
    ];

    /** @var list<int> */
    private static array $codes403 = [
        // Feature not enabled
        ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED,
        ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED,
        ErrorCodes::ERR_DELETE_IS_NOT_ENABLED,
        ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED,
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
        // API key unauthorized
        ErrorCodes::ERR_API_KEY_UNAUTHORIZED,
    ];

    /** @var list<int> */
    private static array $codes404 = [
        ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND,
        ErrorCodes::ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD,
        ErrorCodes::ERR_GOOGLE_USER_NOT_FOUND,
        ErrorCodes::ERR_AUTH0_USER_NOT_FOUND,
        ErrorCodes::ERR_API_KEY_NOT_FOUND,
    ];

    /** @var list<int> */
    private static array $codes405 = [
        ErrorCodes::ERR_ROUTE_CALLED_WITH_INVALID_METHOD,
    ];

    /** @var list<int> */
    private static array $codes409 = [
        ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS,
    ];

    /** @var list<int> */
    private static array $codes422 = [
        ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS,
        ErrorCodes::ERR_REGISTER_DOMAIN_FOR_USER,
        ErrorCodes::ERR_INVALID_RESET_PASSWORD_CODE,
        ErrorCodes::ERR_RESET_PASSWORD_INVALID_FLOW,
        ErrorCodes::ERR_EMPTY_CUSTOM_EMAIL_SUBJECT,
        ErrorCodes::ERR_JWT_IS_MISSING,
        ErrorCodes::ERR_AUTH_CODE_REQUIRED,
    ];

    /** @var list<int> */
    private static array $codes500 = [
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
