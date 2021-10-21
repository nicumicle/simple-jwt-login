<?php

namespace SimpleJwtLoginTests\Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\RevokeTokenService;

class RevokeTokenServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this
            ->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
    }

    /**
     * @dataProvider validationProvider
     * @param array $settings
     * @param string $exceptionMessage
     * @throws \Exception
     */
    public function testValidation($settings, $exceptionMessage)
    {
        $this->expectException(\Exception::class);
        $this->expectErrorMessage($exceptionMessage);

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $authenticationService = (new RevokeTokenService())
            ->withRequest([
                'JWT' => '',
                'AUTH_KEY' => 'test',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'HTTP_CLIENT_IP' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authenticationService->makeAction();
    }

    public function testUserNotFound()
    {
        $this->expectException(\Exception::class);
        $this->expectErrorMessage('User not found.');

        $settings = [
            'allow_authentication' => true,
            'auth_requires_auth_code' => false,
            'decryption_key' => 'test',
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'jwt_login_by_parameter' => 'id',
        ];

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock->method('getUserDetailsById')
            ->willReturn(false);
        $this->wordPressDataMock->method('isInstanceOfuser')
            ->willReturn(false);
        $authenticationService = (new RevokeTokenService())
            ->withRequest([
                'JWT' => JWT::encode([
                    'id' => 1
                ],$settings['decryption_key'], 'HS256'),
                'AUTH_KEY' => 'test',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'HTTP_CLIENT_IP' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authenticationService->makeAction();
    }

    public function validationProvider()
    {
        return [
            'test_empty_settings' => [
                'settings' => [],
                'exceptionMessage' => 'Authentication is not enabled',
            ],
            'test_not_allowed_ip' => [
                'settings' => [
                    'allow_authentication' => true,
                    'auth_ip' => '127.1.1.1',
                ],
                'exceptionMessage' => 'You are not allowed to Authenticate from this IP',
            ],
            'test_invalid_auth_key' => [
                'settings' => [
                    'allow_authentication' => true,
                    'auth_requires_auth_code' => true,
                    'auth_codes' => [
                        [
                            'code' => 'some-key',
                            'role' => '',
                            'expiration_date' => '',
                        ],
                    ],
                ],
                'exceptionMessage' => 'Invalid Auth Code',
            ],
            'test_missing_jwt' => [
                'settings' => [
                    'allow_authentication' => true,
                    'auth_requires_auth_code' => false,
                ],
                'exceptionMessage' => 'The `jwt` parameter is missing.',
            ],

        ];
    }
}
