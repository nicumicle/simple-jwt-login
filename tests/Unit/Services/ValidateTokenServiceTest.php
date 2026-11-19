<?php

namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\ValidateTokenService;

class ValidateTokenServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RevokedTokenRepository
     */
    private $revokedTokenRepoMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this
            ->createStub(WordPressDataInterface::class);
        $this->revokedTokenRepoMock = $this
            ->createStub(RevokedTokenRepository::class);
    }

    #[DataProvider('validationProvider')]
    /**
     * @param mixed $settings
     * @param string $expectedMessage
     * @param array $request
     *
     * @throws Exception
     */
    public function testValidation($settings, $expectedMessage, $request = [])
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $validateTokenService = (new ValidateTokenService())
            ->withSession([])
            ->withCookies([])
            ->withRequest($request)
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $validateTokenService->makeAction();
    }

    public static function validationProvider()
    {
        return [
            [
                'settings' => [],
                'expectedMessage'  => 'Authentication is not enabled',
            ],
            [
                'settings' => [
                    'allow_authentication'  => '1',
                    'allow_validate_token'  => '1',
                    'request_jwt_header'    => '0',
                    'request_jwt_url'       => '1',
                ],
                'expectedMessage'  => 'The `jwt` parameter is missing.'
            ],
            [
                'settings' => [
                    'allow_authentication'  => '1',
                    'allow_validate_token'  => '1',
                    'request_jwt_header'    => '0',
                    'request_jwt_url'       => '1',
                    'decryption_key'        => '123',
                    'request_keys'          => [
                        'url' => 'JWT'
                    ]
                ],
                'expectedMessage'  => 'Wrong number of segments',
                'request'  => [
                    'JWT' => '123'
                ]
            ]
        ];
    }

    /**
     * An interim two-factor JWT is rejected before the login-by claim is read, dotted path or not.
     */
    #[DataProvider('loginByParameterProvider')]
    public function testInterimTwoFactorJwtIsRejectedForEveryLoginByParameter(string $loginByParameter)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REJECTED);
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'   => '1',
                'allow_validate_token'   => '1',
                'request_jwt_header'     => '0',
                'request_jwt_url'        => '1',
                'decryption_key'         => '123',
                'jwt_login_by'           => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
                'jwt_login_by_parameter' => $loginByParameter,
                'request_keys'           => ['url' => 'JWT'],
            ]));
        $interimJwt = JWT::encode(
            ['tfa_pending' => 1, 'id' => 1, 'data' => ['id' => 1], 'exp' => time() + 300],
            '123',
            'HS256'
        );

        (new ValidateTokenService())
            ->withSession([])
            ->withCookies([])
            ->withRequest(['JWT' => $interimJwt])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock)
            ->makeAction();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function loginByParameterProvider(): array
    {
        return [
            'flat parameter'   => ['id'],
            'dotted parameter' => ['data.id'],
        ];
    }

    public function testUserNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found');
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(
                json_encode(
                    [
                        'allow_authentication' => '1',
                        'allow_validate_token' => '1',
                        'request_jwt_header'   => '0',
                        'request_jwt_url'      => '1',
                        'decryption_key'       => '123',
                        'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
                        'jwt_login_by_parameter' => 'id',
                        'request_keys'         => [
                            'url' => 'JWT'
                        ],
                    ]
                )
            );
        $validateTokenService = (new ValidateTokenService())
            ->withSession([])
            ->withCookies([])
            ->withRequest(['JWT' => JWT::encode(['id' => 123], '123', 'HS256')])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $validateTokenService->makeAction();
    }

    public function testSuccessFlow()
    {
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(
                json_encode(
                    [
                        'allow_authentication' => '1',
                        'allow_validate_token' => '1',
                        'request_jwt_header'   => '0',
                        'request_jwt_url'      => '1',
                        'decryption_key'       => '123',
                        'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
                        'jwt_login_by_parameter' => 'id',
                        'request_keys'         => [
                            'url' => 'JWT'
                        ],
                        'enabled_hooks' => [
                            SimpleJWTLoginHooks::HOOK_RESPONSE_VALIDATE_TOKEN,
                        ],
                    ]
                )
            );

        $this->wordPressDataMock
            ->method('getUserDetailsById')
            ->willReturn([]);
        $this->wordPressDataMock
            ->method('isInstanceOfUser')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('wordpressUserToArray')
            ->willReturn(
                [
                    'user_pass' => 123,
                    'user_activation_key' => '1700000000:$P$hash',
                    'test' => 123,
                ]
            );
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(['success' => true]);
        $filteredResponse = null;
        $this->wordPressDataMock
            ->method('applyFilters')
            ->willReturnCallback(function ($hook, $response) use (&$filteredResponse) {
                if ($hook === SimpleJWTLoginHooks::HOOK_RESPONSE_VALIDATE_TOKEN) {
                    $filteredResponse = $response;
                }
                return $response;
            });
        $validateTokenService = (new ValidateTokenService())
            ->withSession([])
            ->withCookies([])
            ->withRequest(['JWT' => JWT::encode(['id' => 123, 'exp' => strtotime('+1Year')], '123', 'HS256')])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $result  = $validateTokenService->makeAction();
        $this->assertSame(['success' => true], $result);
        $this->assertSame(['test' => 123], $filteredResponse['data']['user']);
    }
}
