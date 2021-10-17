<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;

class RouteService extends BaseService
{
    const LOGIN_ROUTE = 'autologin';
    const REGISTER_ROUTE_OLD = 'register';
    const USER_ROUTE = 'users';
    const AUTHENTICATION_ROUTE = 'auth';
    const AUTHENTICATION_REFRESH_ROUTE = 'auth/refresh';
    const AUTHENTICATION_VALIDATE_ROUTE = 'auth/validate';
    const AUTHENTICATION_REVOKE = 'auth/revoke';
    const RESET_PASSWORD_LINK = 'user/reset_password';

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
                'name' => self::REGISTER_ROUTE_OLD,
                'method' => self::METHOD_POST,
                'service' => RegisterUserService::class,
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
            ]
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
        $this->jwt = $jwt;
        $userValue = $this->validateJWTAndGetUserValueFromPayload(
            $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
        );
        $user = $this->getUserDetails($userValue);
        if ($user === null) {
            throw new Exception(
                __('WordPress User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_GET_USER_ID_FROM_JWT
            );
        }
        return (int) $this->wordPressData->getUserProperty($user, 'id');
    }
}
