<?php

namespace SimpleJwtLoginTests\Unit\Helpers;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\StatusCodeHelper;

class StatusCodeHelperTest extends TestCase
{
    public static function statusCodeProvider(): array
    {
        return [
            // 401 — JWT structure / signature
            'ERR_EMPTY_KEY'                          => [ErrorCodes::ERR_EMPTY_KEY, 401],
            'ERR_WRONG_NUMBER_OF_SEGMENTS'           => [ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS, 401],
            'ERR_INVALID_HEADER_ENCODING'            => [ErrorCodes::ERR_INVALID_HEADER_ENCODING, 401],
            'ERR_INVALID_CLAIMS_ENCODING'            => [ErrorCodes::ERR_INVALID_CLAIMS_ENCODING, 401],
            'ERR_INVALID_SIGNATURE_ENCODING'         => [ErrorCodes::ERR_INVALID_SIGNATURE_ENCODING, 401],
            'ERR_EMPTY_ALGORITHM'                    => [ErrorCodes::ERR_EMPTY_ALGORITHM, 401],
            'ERR_ALGORITHM_NOT_SUPPORTED'            => [ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED, 401],
            'ERR_ALGORITHM_NOT_ALLOWED'              => [ErrorCodes::ERR_ALGORITHM_NOT_ALLOWED, 401],
            'ERR_INVALID_KID'                        => [ErrorCodes::ERR_INVALID_KID, 401],
            'ERR_EMPTY_KID'                          => [ErrorCodes::ERR_EMPTY_KID, 401],
            'ERR_SIGNATURE_VERIFICATION_FAILED'      => [ErrorCodes::ERR_SIGNATURE_VERIFICATION_FAILED, 401],
            'ERR_ALGORITHM_NOT_SUPPORTED_IN_SIGNATURE' => [ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED_IN_SIGNATURE, 401],
            // 401 — Token timing
            'ERR_TOKEN_NBF'                          => [ErrorCodes::ERR_TOKEN_NBF, 401],
            'ERR_TOKEN_IAT'                          => [ErrorCodes::ERR_TOKEN_IAT, 401],
            'ERR_TOKEN_EXPIRED'                      => [ErrorCodes::ERR_TOKEN_EXPIRED, 401],
            'ERR_JWT_REFRESH_JWT_TOO_OLD'            => [ErrorCodes::ERR_JWT_REFRESH_JWT_TOO_OLD, 401],
            // 401 — Revoked
            'ERR_REVOKED_TOKEN'                      => [ErrorCodes::ERR_REVOKED_TOKEN, 401],
            // 401 — Missing / invalid JWT
            'ERR_LOGIN_INVALID_JWT'                  => [ErrorCodes::ERR_LOGIN_INVALID_JWT, 401],
            'ERR_DELETE_MISSING_JWT'                 => [ErrorCodes::ERR_DELETE_MISSING_JWT, 401],
            'ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH'      => [ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH, 401],
            'ERR_MISSING_JWT_AUTH_VALIDATE'          => [ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE, 401],
            'ERR_PROTECT_ENDPOINTS_MISSING_JWT'      => [ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT, 401],
            // 401 — Wrong credentials
            'AUTHENTICATION_WRONG_CREDENTIALS'       => [ErrorCodes::AUTHENTICATION_WRONG_CREDENTIALS, 401],
            // 401 — Invalid auth codes / keys
            'ERR_INVALID_AUTH_CODE_PROVIDED'         => [ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED, 401],
            'ERR_REGISTER_INVALID_AUTH_KEY'          => [ErrorCodes::ERR_REGISTER_INVALID_AUTH_KEY, 401],
            'ERR_RESET_PASSWORD_INVALID_AUTH_KEY'    => [ErrorCodes::ERR_RESET_PASSWORD_INVALID_AUTH_KEY, 401],
            // 401 — Invalid nonce
            'ERR_INVALID_NONCE'                      => [ErrorCodes::ERR_INVALID_NONCE, 401],
            // 401 — OAuth invalid
            'ERR_GOOGLE_INVALID_CODE'                => [ErrorCodes::ERR_GOOGLE_INVALID_CODE, 401],
            'ERR_GOOGLE_INVALID_ID_TOKEN'            => [ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN, 401],
            'ERR_AUTH0_INVALID_CODE'                 => [ErrorCodes::ERR_AUTH0_INVALID_CODE, 401],
            'ERR_AUTH0_INVALID_TOKEN'                => [ErrorCodes::ERR_AUTH0_INVALID_TOKEN, 401],
            // 401 — IIS login
            'ERR_INVALID_IIS_LOGIN'                  => [ErrorCodes::ERR_INVALID_IIS_LOGIN, 401],

            // 403 — Feature not enabled
            'ERR_AUTO_LOGIN_NOT_ENABLED'             => [ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED, 403],
            'ERR_REGISTER_IS_NOT_ALLOWED'            => [ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED, 403],
            'ERR_DELETE_IS_NOT_ENABLED'              => [ErrorCodes::ERR_DELETE_IS_NOT_ENABLED, 403],
            'AUTHENTICATION_IS_NOT_ENABLED'          => [ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED, 403],
            'ERR_RESET_PASSWORD_IS_NOT_ALLOWED'      => [ErrorCodes::ERR_RESET_PASSWORD_IS_NOT_ALLOWED, 403],
            'ERR_REFRESH_TOKEN_NOT_ENABLED'          => [ErrorCodes::ERR_REFRESH_TOKEN_NOT_ENABLED, 403],
            'ERR_VALIDATE_TOKEN_NOT_ENABLED'         => [ErrorCodes::ERR_VALIDATE_TOKEN_NOT_ENABLED, 403],
            'ERR_REVOKE_TOKEN_NOT_ENABLED'           => [ErrorCodes::ERR_REVOKE_TOKEN_NOT_ENABLED, 403],
            // 403 — IP blocked
            'ERR_IP_IS_NOT_ALLOWED_TO_LOGIN'         => [ErrorCodes::ERR_IP_IS_NOT_ALLOWED_TO_LOGIN, 403],
            'ERR_REGISTER_IP_IS_NOT_ALLOWED'         => [ErrorCodes::ERR_REGISTER_IP_IS_NOT_ALLOWED, 403],
            'ERR_DELETE_INVALID_CLIENT_IP'           => [ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP, 403],
            // 403 — OAuth provider inactive / API key
            'ERR_OAUTH_PROVIDER_NOT_ACTIVE'          => [ErrorCodes::ERR_OAUTH_PROVIDER_NOT_ACTIVE, 403],
            'ERR_API_KEY_UNAUTHORIZED'               => [ErrorCodes::ERR_API_KEY_UNAUTHORIZED, 403],

            // 404 — Not found
            'ERR_DO_LOGIN_USER_NOT_FOUND'            => [ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND, 404],
            'ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD'  => [ErrorCodes::ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD, 404],
            'ERR_GOOGLE_USER_NOT_FOUND'              => [ErrorCodes::ERR_GOOGLE_USER_NOT_FOUND, 404],
            'ERR_AUTH0_USER_NOT_FOUND'               => [ErrorCodes::ERR_AUTH0_USER_NOT_FOUND, 404],
            'ERR_API_KEY_NOT_FOUND'                  => [ErrorCodes::ERR_API_KEY_NOT_FOUND, 404],

            // 405 — Method not allowed
            'ERR_ROUTE_CALLED_WITH_INVALID_METHOD'   => [ErrorCodes::ERR_ROUTE_CALLED_WITH_INVALID_METHOD, 405],

            // 409 — Conflict
            'ERR_REGISTER_USER_ALREADY_EXISTS'       => [ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS, 409],

            // 422 — Validation
            'ERR_REGISTER_INVALID_EMAIL_ADDRESS'     => [ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS, 422],
            'ERR_REGISTER_DOMAIN_FOR_USER'           => [ErrorCodes::ERR_REGISTER_DOMAIN_FOR_USER, 422],
            'ERR_INVALID_RESET_PASSWORD_CODE'        => [ErrorCodes::ERR_INVALID_RESET_PASSWORD_CODE, 422],
            'ERR_RESET_PASSWORD_INVALID_FLOW'        => [ErrorCodes::ERR_RESET_PASSWORD_INVALID_FLOW, 422],
            'ERR_EMPTY_CUSTOM_EMAIL_SUBJECT'         => [ErrorCodes::ERR_EMPTY_CUSTOM_EMAIL_SUBJECT, 422],

            // 500 — Server errors
            'ERR_OPENSSL_SIGN'                       => [ErrorCodes::ERR_OPENSSL_SIGN, 500],
            'ERR_UNSUPPORTED_SIGN_FUNCTION'          => [ErrorCodes::ERR_UNSUPPORTED_SIGN_FUNCTION, 500],
            'ERR_ALGORITHM_NOT_SUPPORTED_VERIFY'     => [ErrorCodes::ERR_ALGORITHM_NOT_SUPPORTED_VERIFY, 500],
            'ERR_OPEN_SSL_VERIFY'                    => [ErrorCodes::ERR_OPEN_SSL_VERIFY, 500],
            'ERR_JSON_ENCODE_NON_NULL_INPUT'         => [ErrorCodes::ERR_JSON_ENCODE_NON_NULL_INPUT, 500],
            'ERR_CREATE_USER_ERROR'                  => [ErrorCodes::ERR_CREATE_USER_ERROR, 500],
            'ERR_API_KEY_CREATE_FAILED'              => [ErrorCodes::ERR_API_KEY_CREATE_FAILED, 500],
            'ERR_API_KEY_UPDATE_FAILED'              => [ErrorCodes::ERR_API_KEY_UPDATE_FAILED, 500],
            'ERR_API_KEY_REVOKE_FAILED'              => [ErrorCodes::ERR_API_KEY_REVOKE_FAILED, 500],
            'ERR_API_KEY_DELETE_FAILED'              => [ErrorCodes::ERR_API_KEY_DELETE_FAILED, 500],
            'ERR_UNKNOWN_ERROR'                      => [ErrorCodes::ERR_UNKNOWN_ERROR, 500],

            // 400 — Bad request (explicit default fallthrough codes)
            'ERR_JSON_DECODE_NON_NULL_INPUT'         => [ErrorCodes::ERR_JSON_DECODE_NON_NULL_INPUT, 400],
            'ERR_VALIDATE_LOGIN_WRONG_REQUEST'       => [ErrorCodes::ERR_VALIDATE_LOGIN_WRONG_REQUEST, 400],
            'ERR_UNABLE_TO_FIND_PROPERTY_FOR_USER_IN_JWT' => [ErrorCodes::ERR_UNABLE_TO_FIND_PROPERTY_FOR_USER_IN_JWT, 400],
            'ERR_JWT_PARAMETER_FOR_USER_NOT_FOUND'   => [ErrorCodes::ERR_JWT_PARAMETER_FOR_USER_NOT_FOUND, 400],
            'ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD' => [ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD, 400],
            'ERR_DELETE_MISSING_AUTH_KEY'            => [ErrorCodes::ERR_DELETE_MISSING_AUTH_KEY, 400],
            'ERR_INVALID_ROUTE_METHOD'               => [ErrorCodes::ERR_INVALID_ROUTE_METHOD, 400],
            'ERR_INVALID_ROUTE_NAME'                 => [ErrorCodes::ERR_INVALID_ROUTE_NAME, 400],
            'AUTHENTICATION_MISSING_EMAIL'           => [ErrorCodes::AUTHENTICATION_MISSING_EMAIL, 400],
            'AUTHENTICATION_MISSING_PASSWORD'        => [ErrorCodes::AUTHENTICATION_MISSING_PASSWORD, 400],
            'ERR_JWT_REFRESH_NULL_PAYLOAD'           => [ErrorCodes::ERR_JWT_REFRESH_NULL_PAYLOAD, 400],
            'ERR_GET_USER_ID_FROM_JWT'               => [ErrorCodes::ERR_GET_USER_ID_FROM_JWT, 400],
            'ERR_MISSING_EMAIL_FOR_CHANGE_PASSWORD'  => [ErrorCodes::ERR_MISSING_EMAIL_FOR_CHANGE_PASSWORD, 400],
            'ERR_MISSING_CODE_FOR_CHANGE_PASSWORD'   => [ErrorCodes::ERR_MISSING_CODE_FOR_CHANGE_PASSWORD, 400],
            'ERR_MISSING_NEW_PASSWORD_FOR_CHANGE_PASSWORD' => [ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_CHANGE_PASSWORD, 400],
            'ERR_MISSING_NEW_PASSWORD_FOR_RESET_PASSWORD'  => [ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_RESET_PASSWORD, 400],
            'ERR_MISSING_CODE_FROM_EMAIL_BODY'       => [ErrorCodes::ERR_MISSING_CODE_FROM_EMAIL_BODY, 400],
            'ERR_OAUTH_INVALID_PROVIDER'             => [ErrorCodes::ERR_OAUTH_INVALID_PROVIDER, 400],
            'ERR_MISSING_GOOGLE_PARAM'               => [ErrorCodes::ERR_MISSING_GOOGLE_PARAM, 400],
            'ERR_MISSING_AUTH0_PARAM'                => [ErrorCodes::ERR_MISSING_AUTH0_PARAM, 400],
            'ERR_API_KEY_MISSING_NAME'               => [ErrorCodes::ERR_API_KEY_MISSING_NAME, 400],
            'ERR_API_KEY_MISSING_PERMISSIONS'        => [ErrorCodes::ERR_API_KEY_MISSING_PERMISSIONS, 400],
            'ERR_API_KEY_INVALID_PERMISSION'         => [ErrorCodes::ERR_API_KEY_INVALID_PERMISSION, 400],
        ];
    }

    #[DataProvider('statusCodeProvider')]
    public function testKnownCodeMapsToExpectedStatus(int $errorCode, int $expectedStatus): void
    {
        $exception = new Exception('', $errorCode);

        $this->assertSame(
            $expectedStatus,
            StatusCodeHelper::getStatusCodeFromExeption($exception, 400)
        );
    }

    public function testUnknownCodeFallsBackToDefault(): void
    {
        $exception = new Exception('', 9999);

        $this->assertSame(400, StatusCodeHelper::getStatusCodeFromExeption($exception, 400));
        $this->assertSame(503, StatusCodeHelper::getStatusCodeFromExeption($exception, 503));
    }

    public function testDefaultStatusCodeIsRespected(): void
    {
        $exception = new Exception('', 9999);

        foreach ([400, 500, 503] as $default) {
            $this->assertSame($default, StatusCodeHelper::getStatusCodeFromExeption($exception, $default));
        }
    }

    public function testExceptionWithCodeZeroFallsToDefault(): void
    {
        $exception = new Exception('message with no code');

        $this->assertSame(400, StatusCodeHelper::getStatusCodeFromExeption($exception, 400));
    }
}
