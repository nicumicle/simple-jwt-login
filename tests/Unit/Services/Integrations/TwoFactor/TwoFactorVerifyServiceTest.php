<?php

namespace SimpleJwtLoginTests\Unit\Services\Integrations\TwoFactor;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorVerifyService;

class TwoFactorVerifyServiceTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface */
    private $wordPressDataMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RefreshTokenRepositoryInterface */
    private $refreshTokenRepoMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TwoFactorBridge
     */
    private $bridgeMock;

    /**
     * @var string
     */
    private static $jwtSecret = 'test-secret';

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock    = $this->createStub(WordPressDataInterface::class);
        $this->refreshTokenRepoMock = $this->createStub(RefreshTokenRepositoryInterface::class);
        $this->bridgeMock           = $this->createStub(TwoFactorBridge::class);

        $this->wordPressDataMock->method('sanitizeTextField')->willReturnArgument(0);
        $this->wordPressDataMock->method('getAdminUrl')->willReturn('https://admin.com');
    }

    private function makeSettings(array $extra = []): SimpleJWTLoginSettings
    {
        $base = array_merge([
            'allow_authentication' => 1,
            'decryption_key'       => self::$jwtSecret,
        ], $extra);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($base));
        return new SimpleJWTLoginSettings($this->wordPressDataMock);
    }

    private function makeInterimJwt(array $extra = []): string
    {
        $payload = array_merge([
            'iat'                                => time(),
            'exp'                                => time() + 300,
            AuthenticateService::TFA_PENDING_CLAIM => 1,
            'tfa_user_id'                        => 42,
            'tfa_nonce'                          => 'abc123',
            'tfa_provider'                       => 'Two_Factor_Totp',
        ], $extra);
        return JWT::encode($payload, self::$jwtSecret, 'HS256');
    }

    public function testRejectsWhenAuthenticationDisabled(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Authentication is not enabled.');

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([]));

        (new TwoFactorVerifyService())
            ->withRequest([])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();
    }

    public function testRejectsWhenTwoFactorPluginMissing(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_TWO_FACTOR_NOT_ACTIVE);

        $this->bridgeMock->method('isAvailable')->willReturn(false);

        (new TwoFactorVerifyService())
            ->withRequest(['JWT' => $this->makeInterimJwt()])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings())
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();
    }

    public function testRejectsWhenJwtMissing(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_JWT_IS_MISSING);

        $this->bridgeMock->method('isAvailable')->willReturn(true);

        (new TwoFactorVerifyService())
            ->withRequest([])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings())
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();
    }

    public function testRejectsNonInterimJwt(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REQUIRED);

        $this->bridgeMock->method('isAvailable')->willReturn(true);

        $regularJwt = JWT::encode(['iat' => time(), 'id' => 1], self::$jwtSecret, 'HS256');

        (new TwoFactorVerifyService())
            ->withRequest(['JWT' => $regularJwt])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings())
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();
    }

    public function testRejectsWhenUserNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND);

        $this->bridgeMock->method('isAvailable')->willReturn(true);
        $this->bridgeMock->method('isRateLimited')->willReturn(false);
        $this->bridgeMock->method('verifyNonce')->willReturn(true);

        $this->wordPressDataMock->method('getUserDetailsById')->willReturn(null);
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(false);

        (new TwoFactorVerifyService())
            ->withRequest(['JWT' => $this->makeInterimJwt(), 'code' => '123456'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings())
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();
    }

    public function testRejectsWhenRateLimited(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_TWO_FACTOR_RATE_LIMITED);

        $this->bridgeMock->method('isAvailable')->willReturn(true);
        $this->bridgeMock->method('isRateLimited')->willReturn(true);
        $this->bridgeMock->method('getTimeDelay')->willReturn(30);

        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);

        (new TwoFactorVerifyService())
            ->withRequest(['JWT' => $this->makeInterimJwt(), 'code' => '123456'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings())
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();
    }

    public function testRejectsInvalidNonce(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_TWO_FACTOR_INVALID_NONCE);

        $this->bridgeMock->method('isAvailable')->willReturn(true);
        $this->bridgeMock->method('isRateLimited')->willReturn(false);
        $this->bridgeMock->method('verifyNonce')->willReturn(false);

        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')->willReturn('test@test.com');

        (new TwoFactorVerifyService())
            ->withRequest(['JWT' => $this->makeInterimJwt(), 'code' => '123456'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings())
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();
    }

    public function testRejectsMissingCode(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_TWO_FACTOR_INVALID_CODE);

        $this->bridgeMock->method('isAvailable')->willReturn(true);
        $this->bridgeMock->method('isRateLimited')->willReturn(false);
        $this->bridgeMock->method('verifyNonce')->willReturn(true);

        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')->willReturn('test@test.com');

        (new TwoFactorVerifyService())
            ->withRequest(['JWT' => $this->makeInterimJwt()])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings())
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();
    }

    public function testSuccessfulVerificationIssuesFullJwt(): void
    {
        $this->bridgeMock->method('isAvailable')->willReturn(true);
        $this->bridgeMock->method('isRateLimited')->willReturn(false);
        $this->bridgeMock->method('verifyNonce')->willReturn(true);

        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')->willReturn('test@test.com');
        $this->wordPressDataMock->method('createResponse')->willReturn(true);

        $service = new class extends TwoFactorVerifyService {
            protected function verifyCodeForProvider($providerClass, $user, $code, $userId)
            {
                unset($providerClass, $user, $code, $userId);
                return true;
            }
        };

        $result = $service
            ->withRequest(['JWT' => $this->makeInterimJwt(), 'code' => '123456'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings())
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();

        $this->assertTrue($result);
    }

    public function testJwtPayloadActionHookCannotOverwriteReservedClaims(): void
    {
        /** @var array|null $capturedResponse */
        $capturedResponse = null;

        $this->bridgeMock->method('isAvailable')->willReturn(true);
        $this->bridgeMock->method('isRateLimited')->willReturn(false);
        $this->bridgeMock->method('verifyNonce')->willReturn(true);

        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')
            ->willReturnCallback(function ($user, $property) {
                unset($user);
                return $property === 'ID' ? 42 : 'subscriber@test.com';
            });
        $this->wordPressDataMock->method('applyFilters')
            ->willReturnCallback(function ($hookName, $payload, $request) {
                if ($hookName === SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME) {
                    $payload['email']    = $request['claim_email'];
                    $payload['id']       = 1;
                    $payload['injected'] = 'custom';
                }
                return $payload;
            });
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($response) use (&$capturedResponse) {
                $capturedResponse = $response;
                return true;
            });

        $this->makeServiceWithCodeResult(true)
            ->withRequest([
                'JWT'         => $this->makeInterimJwt(),
                'code'        => '123456',
                'claim_email' => 'admin@test.com',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings([
                'jwt_payload'   => [
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_ID,
                ],
                'enabled_hooks' => [SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME],
            ]))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($this->bridgeMock)
            ->makeAction();

        $jwtParts = explode('.', $capturedResponse['data']['jwt']);
        $payload  = json_decode(base64_decode(strtr($jwtParts[1], '-_', '+/')), true);
        $this->assertSame('subscriber@test.com', $payload['email']);
        $this->assertSame(42, $payload['id']);
        $this->assertSame('custom', $payload['injected']);
    }

    /**
     * @param bool $codeIsValid
     * @return TwoFactorVerifyService
     */
    private function makeServiceWithCodeResult($codeIsValid)
    {
        return new class ($codeIsValid) extends TwoFactorVerifyService {
            /** @var boolean */
            private $codeIsValid;

            public function __construct($codeIsValid)
            {
                $this->codeIsValid = $codeIsValid;
            }

            protected function verifyCodeForProvider($providerClass, $user, $code, $userId)
            {
                unset($providerClass, $user, $code, $userId);
                return $this->codeIsValid;
            }
        };
    }

    #[DataProvider('invalidCodeProvider')]
    public function testInvalidCodeRecordsFailedAttempt(bool $passwordWasReset, string $expectedMessage): void
    {
        $bridge = $this->createMock(TwoFactorBridge::class);
        $bridge->method('isAvailable')->willReturn(true);
        $bridge->method('isRateLimited')->willReturn(false);
        $bridge->method('verifyNonce')->willReturn(true);
        $bridge->expects($this->once())->method('recordFailedAttempt')->with('user', 42)
            ->willReturn($passwordWasReset);
        $bridge->expects($this->never())->method('resetFailedAttempts');
        $bridge->expects($this->never())->method('deleteNonce');

        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')->willReturn('test@test.com');

        try {
            $this->makeServiceWithCodeResult(false)
                ->withRequest(['JWT' => $this->makeInterimJwt(), 'code' => '000000'])
                ->withCookies([])
                ->withServerHelper(new ServerHelper([]))
                ->withSettings($this->makeSettings())
                ->withRefreshTokenRepository($this->refreshTokenRepoMock)
                ->withTwoFactorBridge($bridge)
                ->makeAction();
            $this->fail('An invalid code must be rejected.');
        } catch (Exception $exception) {
            $this->assertSame(ErrorCodes::ERR_TWO_FACTOR_INVALID_CODE, $exception->getCode());
            $this->assertSame($expectedMessage, $exception->getMessage());
        }
    }

    /**
     * @return array[]
     */
    public static function invalidCodeProvider(): array
    {
        return [
            'below the failed attempt limit' => [false, 'Invalid two-factor code.'],
            'password reset after too many failures' => [
                true,
                'Too many invalid two-factor codes. Your password was reset, check your email to set a new one.',
            ],
        ];
    }

    public function testValidCodeResetsFailedAttempts(): void
    {
        $bridge = $this->createMock(TwoFactorBridge::class);
        $bridge->method('isAvailable')->willReturn(true);
        $bridge->method('isRateLimited')->willReturn(false);
        $bridge->method('verifyNonce')->willReturn(true);
        $bridge->expects($this->never())->method('recordFailedAttempt');
        $bridge->expects($this->once())->method('resetFailedAttempts')->with(42);
        $bridge->expects($this->once())->method('deleteNonce')->with(42);

        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')->willReturn('test@test.com');
        $this->wordPressDataMock->method('createResponse')->willReturn(true);

        $result = $this->makeServiceWithCodeResult(true)
            ->withRequest(['JWT' => $this->makeInterimJwt(), 'code' => '123456'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings($this->makeSettings())
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withTwoFactorBridge($bridge)
            ->makeAction();

        $this->assertTrue($result);
    }

    #[DataProvider('rejectedBeforeCodeCheckProvider')]
    public function testRequestsRejectedBeforeTheCodeCheckDoNotTouchTheCounter(
        bool $rateLimited,
        bool $nonceIsValid,
        array $extraRequest,
        int $expectedCode
    ): void {
        $bridge = $this->createMock(TwoFactorBridge::class);
        $bridge->method('isAvailable')->willReturn(true);
        $bridge->method('isRateLimited')->willReturn($rateLimited);
        $bridge->method('getTimeDelay')->willReturn(30);
        $bridge->method('verifyNonce')->willReturn($nonceIsValid);
        $bridge->expects($this->never())->method('recordFailedAttempt');
        $bridge->expects($this->never())->method('resetFailedAttempts');

        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')->willReturn('test@test.com');

        try {
            $this->makeServiceWithCodeResult(false)
                ->withRequest(array_merge(['JWT' => $this->makeInterimJwt()], $extraRequest))
                ->withCookies([])
                ->withServerHelper(new ServerHelper([]))
                ->withSettings($this->makeSettings())
                ->withRefreshTokenRepository($this->refreshTokenRepoMock)
                ->withTwoFactorBridge($bridge)
                ->makeAction();
            $this->fail('The request must be rejected.');
        } catch (Exception $exception) {
            $this->assertSame($expectedCode, $exception->getCode());
        }
    }

    /**
     * @return array[]
     */
    public static function rejectedBeforeCodeCheckProvider(): array
    {
        return [
            'rate limited' => [true, true, ['code' => '000000'], ErrorCodes::ERR_TWO_FACTOR_RATE_LIMITED],
            'invalid nonce' => [false, false, ['code' => '000000'], ErrorCodes::ERR_TWO_FACTOR_INVALID_NONCE],
            'missing code' => [false, true, [], ErrorCodes::ERR_TWO_FACTOR_INVALID_CODE],
        ];
    }
}
