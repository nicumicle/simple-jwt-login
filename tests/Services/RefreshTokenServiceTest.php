<?php

namespace SimpleJwtLoginTests\Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\RefreshTokenService;

class RefreshTokenServiceTest extends TestCase
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
        $this->expectExceptionMessage($exceptionMessage);

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $refreshService = (new RefreshTokenService())
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
        $refreshService->makeAction();
    }

    public function testRefreshWithInvalidJWT()
    {
        $settings = [
            'allow_authentication' => true,
            'auth_requires_auth_code' => false,
            'decryption_key' => 'test',
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'jwt_login_by_parameter' => 'id',
        ];
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Wrong number of segments');

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $refreshService = (new RefreshTokenService())
            ->withRequest([
                'JWT' => '123.123',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $refreshService->makeAction();
    }

    public function testRefreshExpiredTokenThatWasBlacklisted()
    {
        $settings = [
            'allow_authentication' => true,
            'auth_requires_auth_code' => false,
            'decryption_key' => 'test',
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'jwt_login_by_parameter' => 'id',
            'jwt_auth_refresh_ttl' => 1000, //minutes
            'jwt_auth_ttl' => 1, //minutes
        ];
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Jwt is invalid');
        $this->expectExceptionCode(ErrorCodes::ERR_REVOKED_TOKEN);

        $jwt = JWT::encode(
            [
                'id' => 1,
                'exp' => time() - 1000 * 60,
            ],
            'test',
            'HS256'
        );
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock->method('getUserDetailsById')
            ->willReturn(1);
        $this->wordPressDataMock->method('isInstanceOfuser')
            ->willReturn(true);
        $this->wordPressDataMock->method('getUserMeta')
            ->willReturn([$jwt]);
        $refreshService = (new RefreshTokenService())
            ->withRequest([
                'JWT' => $jwt,
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $refreshService->makeAction();
    }

    public function testRefreshTooOldToken()
    {
        $settings = [
            'allow_authentication' => true,
            'auth_requires_auth_code' => false,
            'decryption_key' => 'test',
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'jwt_login_by_parameter' => 'id',
            'jwt_auth_refresh_ttl' => 10, //minutes
            'jwt_auth_ttl' => 1, //minutes
        ];
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('JWT is too old to be refreshed.');
        $this->expectExceptionCode(ErrorCodes::ERR_JWT_REFRESH_JWT_TOO_OLD);

        $jwt = JWT::encode(
            [
                'id' => 1,
                'exp' => time() - 11 * 60,
            ],
            'test',
            'HS256'
        );
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock->method('getUserDetailsById')
            ->willReturn(1);
        $this->wordPressDataMock->method('isInstanceOfuser')
            ->willReturn(true);
        $this->wordPressDataMock->method('getUserMeta')
            ->willReturn([]);
        $refreshService = (new RefreshTokenService())
            ->withRequest([
                'JWT' => $jwt,
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $refreshService->makeAction();
    }

    public function testSuccess()
    {
        $settings = [
            'allow_authentication' => true,
            'auth_requires_auth_code' => false,
            'decryption_key' => 'test',
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'jwt_login_by_parameter' => 'id',
            'jwt_auth_refresh_ttl' => 20, //minutes
            'jwt_auth_ttl' => 1, //minutes
        ];

        $jwt = JWT::encode(
            [
                'id' => 1,
                'exp' => time() - 11 * 60,
            ],
            'test',
            'HS256'
        );
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock->method('getUserDetailsById')
            ->willReturn(1);
        $this->wordPressDataMock->method('isInstanceOfuser')
            ->willReturn(true);
        $this->wordPressDataMock->method('getUserMeta')
            ->willReturn([]);
        $this->wordPressDataMock->method('createResponse')
            ->willReturn(true);
        $refreshService = (new RefreshTokenService())
            ->withRequest([
                'JWT' => $jwt,
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $result = $refreshService->makeAction();
        $this->assertTrue($result);
    }

    /**
     * @return array[]
     */
    public static function validationProvider()
    {
        return [
            'test_empty_settings' => [
                'settings' => [],
                'exceptionMessage' => 'Authentication is not enabled.',
            ],
            'test_authentication_is_false' => [
                'settings' => [
                    'allow_authentication' => false,
                ],
                'exceptionMessage' => 'Authentication is not enabled.',
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
                'exceptionMessage' => 'JWT is missing.',
            ],
        ];
    }
}
