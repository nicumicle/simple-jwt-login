<?php
namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\AuthenticateService;

class AuthenticateServiceTest extends TestCase
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
            ->createStub(WordPressDataInterface::class);

        $this->refreshTokenRepoMock = $this->createStub(RefreshTokenRepositoryInterface::class);
    }

    #[DataProvider('validationProvider')]
    /**
     * @param array $settings
     * @param array $request
     * @param string $exceptionMessage
     *
     * @throws Exception
     */
    public function testValidation($settings, $request, $exceptionMessage)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $authService = (new AuthenticateService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $authService->makeAction();
    }

    /**
     * @return array[]
     */
    public static function validationProvider()
    {
        return [
            [
                'settings' => [],
                'request' => [],
                'exceptionMessage' => 'Authentication is not enabled.',
            ],
            [
                'settings' => [
                    'allow_authentication' => '0',
                ],
                'request' => [],
                'exceptionMessage' => 'Authentication is not enabled.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                ],
                'request' => [],
                'exceptionMessage' => 'The email, username, or login parameter is missing from the request.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                ],
                'request' => [
                    'email' => '',
                ],
                'exceptionMessage' => 'The password or password_hash parameter is missing from request.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                ],
                'request' => [
                    'username' => '',
                ],
                'exceptionMessage' => 'The password or password_hash parameter is missing from request.'
            ],
        ];
    }

    public function testIpLimitation()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You are not allowed to Authenticate from this IP:');
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(
                [
                    'allow_authentication' => 1,
                    'auth_ip' => '127.0.0.1',
                ]
            ));
        $authService = (new AuthenticateService())
            ->withRequest([
                'email' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['HTTP_CLIENT_IP' => '127.0.0.2']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $authService->makeAction();
    }

    public function testUserNotFoundWithEmail()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Wrong user credentials.');
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
            ]));
        $this->wordPressDataMock->method('getUserDetailsByEmail')
            ->willReturn(null);
        $authService = (new AuthenticateService())
            ->withRequest([
                'email' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $authService->makeAction();
    }

    public function testUserNotFoundWithUsername()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Wrong user credentials.');
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
            ]));
        $this->wordPressDataMock->method('getUserByUserLogin')
                                ->willReturn(null);
        $authService = (new AuthenticateService())
            ->withRequest([
                'username' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $authService->makeAction();
    }

    public function testWrongUserCredentials()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Wrong user credentials.');

        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
            ]));
        $this->wordPressDataMock
            ->method('getUserByUserLogin')
            ->willReturn('user');
        $this->wordPressDataMock
            ->method('getUserPassword')
            ->willReturn('1234');
        $this->wordPressDataMock
            ->method('checkPassword')
            ->willReturn(false);
        $authService = (new AuthenticateService())
            ->withRequest([
                'username' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $authService->makeAction();
    }

    public function testWrongUserCredentialsWithHash()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Wrong user credentials.');

        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
            ]));
        $this->wordPressDataMock
            ->method('getUserByUserLogin')
            ->willReturn('user');
        $this->wordPressDataMock
            ->method('getUserPassword')
            ->willReturn('1234');
        $this->wordPressDataMock
            ->method('checkPassword')
            ->willReturn(false);
        $authService = (new AuthenticateService())
            ->withRequest([
                'username' => 'test@test.com',
                'password_hash' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $authService->makeAction();
    }

    public function testMissingAuthCodes()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid Auth Code ( AUTH_KEY ) provided.');

        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
                'auth_requires_auth_code' => true,
            ]));

        $authService = (new AuthenticateService())
            ->withRequest([
                'username' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $authService->makeAction();
    }

    public function testSuccessFlowWithFullPayload()
    {
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
                'auth_requires_auth_code' => true,
                'jwt_payload' => [
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_ID,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_SITE,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME
                ],
                'enabled_hooks' => [
                    SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME
                ],
                'auth_codes' => [
                    [
                        'code' => '123',
                        'role' => '',
                        'expiration_date' => '',
                    ]
                ]
            ]));
        $this->wordPressDataMock
            ->method('getUserByUserLogin')
            ->willReturn('user');
        $this->wordPressDataMock
            ->method('getUserPassword')
            ->willReturn('1234');
        $this->wordPressDataMock
            ->method('checkPassword')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);
        $this->refreshTokenRepoMock->method('insert')->willReturn(true);

        $authService = (new AuthenticateService())
            ->withRequest(
                [
                    'username' => 'test@test.com',
                    'password' => '123',
                    'AUTH_KEY' => '123',
                ]
            )
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $result = $authService->makeAction();
        $this->assertTrue($result);
    }

    public function testSuccessFlowWithFullPayloadAndPasshash()
    {
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
                'auth_requires_auth_code' => true,
                'jwt_payload' => [
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_ID,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_SITE,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME
                ],
                'enabled_hooks' => [
                    SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME
                ],
                'auth_codes' => [
                    [
                        'code' => '123',
                        'role' => '',
                        'expiration_date' => '',
                    ]
                ]
            ]));
        $this->wordPressDataMock
            ->method('getUserByUserLogin')
            ->willReturn('user');
        $this->wordPressDataMock
            ->method('getUserPassword')
            ->willReturn('1234');
        $this->wordPressDataMock
            ->method('checkPassword')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);
        $this->refreshTokenRepoMock->method('insert')->willReturn(true);

        $authService = (new AuthenticateService())
            ->withRequest(
                [
                    'username' => 'test@test.com',
                    'password_hash' => '123',
                    'AUTH_KEY' => '123',
                ]
            )
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $result = $authService->makeAction();
        $this->assertTrue($result);
    }

    public function testAuthResponseContainsRefreshToken()
    {
        /** @var array|null $capturedResponse */
        $capturedResponse = null;

        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'   => 1,
                'allow_refresh_token'    => 1,
                'decryption_key'         => 'test-secret',
                'jwt_auth_refresh_ttl'   => 60,
            ]));
        $this->wordPressDataMock->method('getUserByUserLogin')->willReturn('user');
        $this->wordPressDataMock->method('getUserPassword')->willReturn('pass');
        $this->wordPressDataMock->method('checkPassword')->willReturn(true);
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($response) use (&$capturedResponse) {
                $capturedResponse = $response;
                return true;
            });
        $this->refreshTokenRepoMock->method('insert')->willReturn(true);

        $authService = (new AuthenticateService())
            ->withRequest(['username' => 'test', 'password' => 'pass'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $authService->makeAction();

        $this->assertNotNull($capturedResponse);
        $this->assertArrayHasKey('data', $capturedResponse);
        $this->assertArrayHasKey('refresh_token', $capturedResponse['data']);
        $this->assertNotEmpty($capturedResponse['data']['refresh_token']);
    }

    public function testInsertRefreshTokenIsCalledOnSuccessfulAuth()
    {
        $this->refreshTokenRepoMock = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
                'allow_refresh_token'  => 1,
                'decryption_key'       => 'test-secret',
                'jwt_auth_refresh_ttl' => 60,
            ]));
        $this->wordPressDataMock->method('getUserByUserLogin')->willReturn('user');
        $this->wordPressDataMock->method('getUserPassword')->willReturn('pass');
        $this->wordPressDataMock->method('checkPassword')->willReturn(true);
        $this->wordPressDataMock->method('createResponse')->willReturn(true);

        $this->refreshTokenRepoMock->expects($this->once())
            ->method('insert')
            ->with(
                $this->anything(),
                $this->isString(),
                $this->isInt()
            );

        $authService = (new AuthenticateService())
            ->withRequest(['username' => 'test', 'password' => 'pass'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock);
        $authService->makeAction();
    }
}
