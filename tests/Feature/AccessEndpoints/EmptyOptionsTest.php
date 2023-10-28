<?php

namespace SimpleJwtLoginTests\Feature\AccessEndpoints;

use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\TestBase;

class EmptyOptionsTest extends TestBase
{
    /**
     * @return array[]
     */
    public static function endpointsProvider()
    {
        return [
            'autologin' => [
                'method' => 'GET',
                'endpoint' => '/autologin',
                'expectedError' =>  self::generateErrorJson(
                    "Auto-login is not enabled on this website.",
                    ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED
                ),
            ],
            'register_user' => [
                'method' => 'POST',
                'endpoint' => '/users',
                'expectedError' =>  self::generateErrorJson(
                    "Register is not allowed.",
                    ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED
                ),
            ],
            'delete_user' => [
                'method' => 'DELETE',
                'endpoint' => '/users',
                'expectedError' =>  self::generateErrorJson(
                    "Delete is not enabled.",
                    ErrorCodes::ERR_DELETE_IS_NOT_ENABLED
                ),
            ],
            'reset_password' => [
                'method' => 'POST',
                'endpoint' => '/user/reset_password',
                'expectedError' =>  self::generateErrorJson(
                    "Reset Password is not allowed.",
                    ErrorCodes::ERR_RESET_PASSWORD_IS_NOT_ALLOWED
                ),
            ],
            'change_password' => [
                'method' => 'PUT',
                'endpoint' => '/user/reset_password',
                'expectedError' =>  self::generateErrorJson(
                    "Reset Password is not allowed.",
                    ErrorCodes::ERR_RESET_PASSWORD_IS_NOT_ALLOWED
                ),
            ],
            'auth' => [
                'method' => 'POST',
                'endpoint' => '/auth',
                'expectedError' =>  self::generateErrorJson(
                    "Authentication is not enabled.",
                    ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED
                ),
            ],
            'auth_refresh' => [
                'method' => 'POST',
                'endpoint' => '/auth/refresh',
                'expectedError' =>  self::generateErrorJson(
                    "Authentication is not enabled.",
                    ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED
                ),
            ],
            'auth_validate' => [
                'method' => 'POST',
                'endpoint' => '/auth/validate',
                'expectedError' =>  self::generateErrorJson(
                    "Authentication is not enabled.",
                    ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED
                ),
            ],
            'auth_validate_get' => [
                'method' => 'GET',
                'endpoint' => '/auth/validate',
                'expectedError' =>  self::generateErrorJson(
                    "Authentication is not enabled.",
                    ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED
                ),
            ],
        ];
    }

    /**
     * @testdox Access endpoints is not allowed
     * @dataProvider endpointsProvider
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testEmptyOptions($method, $endpoint, $expectedError)
    {
        self::updateSimpleJWTOption([]);
        $uri = self::API_URL . "?rest_route=/simple-jwt-login/v1" . $endpoint;
        $result = $this->client->request($method, $uri);
        $this->assertSame(
            400,
            $result->getStatusCode()
        );

        $contents = $result->getBody()->getContents();
        $this->assertJson($contents);
        $json = json_decode($contents, true);
        $this->assertEquals(
            $json,
            $expectedError
        );
    }
}
