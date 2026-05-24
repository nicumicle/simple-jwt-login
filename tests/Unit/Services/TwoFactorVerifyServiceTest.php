<?php

namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Services\TwoFactorBridge;
use SimpleJWTLogin\Services\TwoFactorVerifyService;

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
}
