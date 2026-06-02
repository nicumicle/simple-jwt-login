<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Services\ApiKeys\CreateApiKeyService;
use SimpleJWTLogin\Services\ApiKeys\DeleteApiKeyService;
use SimpleJWTLogin\Services\ApiKeys\ListApiKeysService;
use SimpleJWTLogin\Services\ApiKeys\RevokeApiKeyService;
use SimpleJWTLogin\Services\ApiKeys\UpdateApiKeyService;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorVerifyService;

class RouteService extends BaseService
{
    const LOGIN_ROUTE = 'autologin';
    const USER_ROUTE = 'users';
    const AUTHENTICATION_ROUTE = 'auth';
    const AUTHENTICATION_REFRESH_ROUTE = 'auth/refresh';
    const AUTHENTICATION_VALIDATE_ROUTE = 'auth/validate';
    const AUTHENTICATION_REVOKE = 'auth/revoke';
    const AUTHENTICATION_2FA_ROUTE = 'auth/2fa';
    const RESET_PASSWORD_LINK = 'user/reset_password';
    const OAUTH_TOKEN = 'oauth/token';

    const API_KEYS_ROUTE         = 'api-keys';
    const API_KEYS_SINGLE_ROUTE  = 'api-keys/(?P<id>\d+)';
    const API_KEYS_REVOKE_ROUTE  = 'api-keys/(?P<id>\d+)/revoke';

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PUT = 'PUT';

    /**
     * @return array
     */
    public function getAllRoutes()
    {
        return [
            [
                'name' => self::LOGIN_ROUTE,
                'method' => self::METHOD_GET,
                'service' => LoginService::class,
            ],
            [
                'name' => self::USER_ROUTE,
                'method' => self::METHOD_POST,
                'service' => RegisterUserService::class,
            ],
            [
                'name' => self::USER_ROUTE,
                'method' => self::METHOD_DELETE,
                'service' => DeleteUserService::class,
            ],
            [
                'name' => self::AUTHENTICATION_ROUTE,
                'method' => self::METHOD_POST,
                'service' => AuthenticateService::class,
            ],
            [
                'name' => self::AUTHENTICATION_REFRESH_ROUTE,
                'method' => self::METHOD_POST,
                'service' => RefreshTokenService::class,
            ],
            [
                'name' => self::AUTHENTICATION_VALIDATE_ROUTE,
                'method' => self::METHOD_GET,
                'service' => ValidateTokenService::class,
            ],
            [
                'name' => self::AUTHENTICATION_VALIDATE_ROUTE,
                'method' => self::METHOD_POST,
                'service' => ValidateTokenService::class,
            ],
            [
                'name' => self::AUTHENTICATION_REVOKE,
                'method' => self::METHOD_POST,
                'service' => RevokeTokenService::class,
            ],
            [
                'name' => self::RESET_PASSWORD_LINK,
                'method' => self::METHOD_PUT,
                'service' => ResetPasswordService::class
            ],
            [
                'name' => self::RESET_PASSWORD_LINK,
                'method' => self::METHOD_POST,
                'service' => ResetPasswordService::class
            ],
            [
                'name' => self::OAUTH_TOKEN,
                'method' => self::METHOD_GET,
                'service' => OAuthService::class
            ],
            [
                'name' => self::OAUTH_TOKEN,
                'method' => self::METHOD_POST,
                'service' => OAuthService::class
            ],
            [
                'name'    => self::AUTHENTICATION_2FA_ROUTE,
                'method'  => self::METHOD_POST,
                'service' => TwoFactorVerifyService::class,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getApiKeyRoutes()
    {
        return [
            [
                'name'    => self::API_KEYS_ROUTE,
                'method'  => self::METHOD_GET,
                'service' => ListApiKeysService::class,
            ],
            [
                'name'          => self::API_KEYS_ROUTE,
                'method'        => self::METHOD_POST,
                'service'       => CreateApiKeyService::class,
                'audit_success' => AuditEvents::API_KEY_CREATE_SUCCESS,
                'audit_failure' => AuditEvents::API_KEY_CREATE_FAILED,
            ],
            [
                'name'          => self::API_KEYS_SINGLE_ROUTE,
                'method'        => self::METHOD_PUT,
                'service'       => UpdateApiKeyService::class,
                'audit_success' => AuditEvents::API_KEY_UPDATE_SUCCESS,
                'audit_failure' => AuditEvents::API_KEY_UPDATE_FAILED,
            ],
            [
                'name'          => self::API_KEYS_SINGLE_ROUTE,
                'method'        => self::METHOD_DELETE,
                'service'       => DeleteApiKeyService::class,
                'audit_success' => AuditEvents::API_KEY_DELETE_SUCCESS,
                'audit_failure' => AuditEvents::API_KEY_DELETE_FAILED,
            ],
            [
                'name'          => self::API_KEYS_REVOKE_ROUTE,
                'method'        => self::METHOD_POST,
                'service'       => RevokeApiKeyService::class,
                'audit_success' => AuditEvents::API_KEY_REVOKE_SUCCESS,
                'audit_failure' => AuditEvents::API_KEY_REVOKE_FAILED,
            ],
        ];
    }

    /**
     * @param string $jwt
     *
     * @return bool|int
     * @throws Exception
     */
    public function getUserIdFromJWT($jwt)
    {
        $user = $this->getUserFromJwt($jwt);

        return (int) $this->wordPressData->getUserProperty($user, 'ID');
    }

    /**
     * @param string $jwt
     *
     * @return \WP_User
     * @throws Exception
     */
    public function getUserFromJwt($jwt)
    {
        $this->jwt = $jwt;
        $userValue = $this->validateJWTAndGetUserValueFromPayload(
            $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
        );
        $user = $this->getUserDetails($userValue);
        if ($user === null) {
            throw new Exception(
                esc_html(__('WordPress User not found.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_GET_USER_ID_FROM_JWT)
            );
        }

        $this->validateJwtRevoked(
            $this->wordPressData->getUserProperty($user, 'ID'),
            $this->jwt
        );

        return $user;
    }
}
