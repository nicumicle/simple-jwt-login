<?php

namespace SimpleJwtLoginTests\Unit\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\RefreshTokenService;

class RefreshTokenServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RefreshTokenRepositoryInterface
     */
    private $refreshTokenRepoMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this
            ->getMockBuilder(WordPressDataInterface::class)
            ->getMock();

        $this->refreshTokenRepoMock = $this
            ->getMockBuilder(RefreshTokenRepositoryInterface::class)
            ->getMock();
    }

    #[DataProvider('validationProvider')]
    /**
     * @param array  $settings
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
                'AUTH_KEY' => 'test',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'HTTP_CLIENT_IP' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $refreshService->makeAction();
    }

    public function testInvalidRefreshToken()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid refresh token.');
        $this->expectExceptionCode(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH);

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
            ]));
        $this->refreshTokenRepoMock->method('getByToken')->willReturn(null);

        $refreshService = (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'bad-token-value'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'HTTP_CLIENT_IP' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $refreshService->makeAction();
    }

    public function testUserNotFound()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found.');
        $this->expectExceptionCode(ErrorCodes::ERR_REVOKED_TOKEN);

        $tokenData          = new \stdClass();
        $tokenData->user_id = 1;

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
            ]));
        $this->refreshTokenRepoMock->method('getByToken')->willReturn($tokenData);
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn(false);
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(false);

        $refreshService = (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'some-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'HTTP_CLIENT_IP' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $refreshService->makeAction();
    }

    public function testSuccess()
    {
        $tokenData          = new \stdClass();
        $tokenData->user_id = 1;

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
                'jwt_auth_refresh_ttl'    => 1440,
            ]));
        $this->refreshTokenRepoMock->method('getByToken')->willReturn($tokenData);
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user-object');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->refreshTokenRepoMock->method('deleteByToken')->willReturn(true);
        $this->refreshTokenRepoMock->method('insert')->willReturn(true);
        $this->wordPressDataMock->method('createResponse')->willReturn(true);

        $refreshService = (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'valid-refresh-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'HTTP_CLIENT_IP' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $result = $refreshService->makeAction();

        $this->assertTrue($result);
    }

    public function testOldRefreshTokenIsRotatedOnSuccess()
    {
        $tokenData          = new \stdClass();
        $tokenData->user_id = 42;

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
                'jwt_auth_refresh_ttl'    => 1440,
            ]));
        $this->refreshTokenRepoMock->method('getByToken')->willReturn($tokenData);
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user-object');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('createResponse')->willReturn(true);

        // The old token must be deleted and a new one stored — exactly once each
        $this->refreshTokenRepoMock->expects($this->once())
            ->method('deleteByToken');
        $this->refreshTokenRepoMock->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo(42),
                $this->isString(),
                $this->isInt()
            );

        $refreshService = (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'valid-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'HTTP_CLIENT_IP' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $refreshService->makeAction();
    }

    public function testResponseContainsNewJwtAndRefreshToken()
    {
        $tokenData          = new \stdClass();
        $tokenData->user_id = 1;
        $capturedResponse   = null;

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
                'jwt_auth_refresh_ttl'    => 1440,
            ]));
        $this->refreshTokenRepoMock->method('getByToken')->willReturn($tokenData);
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user-object');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->refreshTokenRepoMock->method('deleteByToken')->willReturn(true);
        $this->refreshTokenRepoMock->method('insert')->willReturn(true);
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($response) use (&$capturedResponse) {
                $capturedResponse = $response;
                return true;
            });

        $refreshService = (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'valid-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'HTTP_CLIENT_IP' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $refreshService->makeAction();

        $this->assertNotNull($capturedResponse);
        $this->assertTrue($capturedResponse['success']);
        $this->assertArrayHasKey('data', $capturedResponse);
        $this->assertArrayHasKey('jwt', $capturedResponse['data']);
        $this->assertArrayHasKey('refresh_token', $capturedResponse['data']);
        $this->assertNotEmpty($capturedResponse['data']['refresh_token']);
    }

    /**
     * @return array[]
     */
    public static function validationProvider()
    {
        return [
            'test_empty_settings' => [
                'settings'         => [],
                'exceptionMessage' => 'Authentication is not enabled.',
            ],
            'test_authentication_is_false' => [
                'settings'         => [
                    'allow_authentication' => false,
                ],
                'exceptionMessage' => 'Authentication is not enabled.',
            ],
            'test_refresh_token_not_enabled' => [
                'settings'         => [
                    'allow_authentication' => true,
                    'allow_refresh_token'  => false,
                ],
                'exceptionMessage' => 'Refresh Token endpoint is not enabled.',
            ],
            'test_not_allowed_ip' => [
                'settings'         => [
                    'allow_authentication' => true,
                    'allow_refresh_token'  => true,
                    'auth_ip'              => '127.1.1.1',
                ],
                'exceptionMessage' => 'You are not allowed to Authenticate from this IP',
            ],
            'test_invalid_auth_key' => [
                'settings'         => [
                    'allow_authentication'        => true,
                    'allow_refresh_token'         => true,
                    'refresh_requires_auth_code'  => true,
                    'auth_codes'                  => [
                        [
                            'code'            => 'some-key',
                            'role'            => '',
                            'expiration_date' => '',
                        ],
                    ],
                ],
                'exceptionMessage' => 'Invalid Auth Code',
            ],
            'test_missing_refresh_token' => [
                'settings'         => [
                    'allow_authentication'    => true,
                    'allow_refresh_token'     => true,
                    'auth_requires_auth_code' => false,
                ],
                'exceptionMessage' => 'Refresh token is missing.',
            ],
        ];
    }
}
