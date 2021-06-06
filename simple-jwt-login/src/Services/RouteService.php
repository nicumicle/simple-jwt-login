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
                'action' => LoginService::ACTION_NAME_LOGIN_USER
            ],
            [
                'name' => self::REGISTER_ROUTE_OLD,
                'method' => self::METHOD_POST,
                'service' => RegisterUserService::class,
                'action'  => RegisterUserService::ACTION_NAME_CREATE_USER,
            ],
            [
                'name' => self::USER_ROUTE,
                'method' => self::METHOD_POST,
                'service' => RegisterUserService::class,
                'action'  => RegisterUserService::ACTION_NAME_CREATE_USER,
            ],
            [
                'name' => self::USER_ROUTE,
                'method' => self::METHOD_DELETE,
                'service' => DeleteUserService::class,
                'action'  => DeleteUserService::ACTION_NAME_DELETE_USER,
            ],
            [
                'name' => self::AUTHENTICATION_ROUTE,
                'method' => self::METHOD_POST,
                'service' => AuthenticateService::class,
                'action'  => AuthenticateService::ACTION_NAME_AUTHENTICATE,
            ],
            [
                'name' => self::AUTHENTICATION_REFRESH_ROUTE,
                'method' => self::METHOD_POST,
                'service' => AuthenticateService::class,
                'action'  => AuthenticateService::ACTION_NAME_REFRESH_JWT,
            ],
            [
                'name' => self::AUTHENTICATION_VALIDATE_ROUTE,
                'method' => self::METHOD_GET,
                'service' => AuthenticateService::class,
                'action'  => AuthenticateService::ACTION_NAME_VALIDATE_JWT,
            ],
            [
                'name' => self::AUTHENTICATION_REVOKE,
                'method' => self::METHOD_POST,
                'service' => AuthenticateService::class,
                'action'  => AuthenticateService::ACTION_NAME_REVOKE_JWT,
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
        return (int)$user->get('id');
    }
}
