<?php

namespace SimpleJwtLoginTests\Unit\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\RevokeTokenService;
use WP_User;

class RevokeTokenServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RefreshTokenRepository
     */
    private $tokenRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this
            ->createStub(WordPressDataInterface::class);
        $this->tokenRepositoryMock = $this
            ->createStub(RefreshTokenRepository::class);
    }

    #[DataProvider('validationProvider')]
    /**
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
        $revokeService = (new RevokeTokenService())
            ->withRequest([
                'JWT' => '',
                'AUTH_KEY' => 'test',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $revokeService->makeAction();
    }

    public function testUserNotFound()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found.');

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
        $revokeService = (new RevokeTokenService())
            ->withRequest([
                'JWT' => JWT::encode([
                    'id' => 1
                ], $settings['decryption_key'], 'HS256'),
                'AUTH_KEY' => 'test',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $revokeService->makeAction();
    }

    public function testSuccess()
    {
        $settings = [
            'allow_authentication' => true,
            'auth_requires_auth_code' => false,
            'decryption_key' => 'test',
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'jwt_login_by_parameter' => 'id',
            'enabled_hooks' => [
                SimpleJWTLoginHooks::HOOK_RESPONSE_REVOKE_TOKEN,
            ],
        ];

        $user = $this->createStub(WP_User::class);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock->method('getUserDetailsById')
            ->willReturn($user);
        $this->wordPressDataMock->method('isInstanceOfuser')
            ->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')
            ->willReturn(1);
        $this->wordPressDataMock->method('applyFilters')
            ->willReturn(true);

        $this->wordPressDataMock->method('getUserMeta')
            ->willReturn([
                Jwt::encode(['exp' => 1000], 'test', 'HS256')
            ]);

        $this->wordPressDataMock->method('addUserMeta')
            ->willReturn(true);
        $this->wordPressDataMock->method('createResponse')
            ->willReturn(true);

        $revokeService = (new RevokeTokenService())
            ->withRequest([
                'JWT' => JWT::encode([
                    'id' => 1
                ], $settings['decryption_key'], 'HS256'),
                'AUTH_KEY' => 'test',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->tokenRepositoryMock);
        $result = $revokeService->makeAction();
        $this->assertTrue($result);
    }

    public static function validationProvider()
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
                    'allow_authentication'       => true,
                    'revoke_requires_auth_code'  => true,
                    'auth_codes'                 => [
                        [
                            'code'            => 'some-key',
                            'role'            => '',
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

    public function testRevokedTokenTwice()
    {
        $settings = [
            'allow_authentication' => true,
            'auth_requires_auth_code' => false,
            'decryption_key' => 'test',
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'jwt_login_by_parameter' => 'id',
            'enabled_hooks' => [
                SimpleJWTLoginHooks::HOOK_RESPONSE_REVOKE_TOKEN,
            ],
        ];

        $user = $this->createStub(WP_User::class);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock->method('getUserDetailsById')
            ->willReturn($user);
        $this->wordPressDataMock->method('isInstanceOfuser')
            ->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')
            ->willReturn(1);
        $this->wordPressDataMock->method('applyFilters')
            ->willReturn(true);

        $revokedJwt = JWT::encode(['id' => 1], 'test', 'HS256');
        $this->wordPressDataMock->method('getUserMeta')
            ->willReturn([
               $revokedJwt,
            ]);

        $this->wordPressDataMock->method('addUserMeta')
            ->willReturn(true);
        $this->wordPressDataMock->method('createResponse')
            ->willReturn(true);

        $revokeService = (new RevokeTokenService())
            ->withRequest([
                'JWT' => $revokedJwt,
                'AUTH_KEY' => 'test',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token was already revoked.');

        $revokeService->makeAction();
    }
}
