<?php

namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\RefreshTokenService;
use stdClass;

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

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RevokedTokenRepository
     */
    private $revokedTokenRepoMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this
            ->createStub(WordPressDataInterface::class);

        $this->refreshTokenRepoMock = $this->createStub(RefreshTokenRepositoryInterface::class);
        $this->revokedTokenRepoMock = $this->createStub(RevokedTokenRepository::class);
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
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
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
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
        $refreshService->makeAction();
    }

    public function testRefreshTokenSentAsAnArrayIsRejected()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Refresh token is missing.');
        $this->expectExceptionCode(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH);

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
            ]));

        $refreshService = (new RefreshTokenService())
            ->withRequest(['refresh_token' => [null, null]])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
        $refreshService->makeAction();
    }

    public function testUserNotFound()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found.');
        $this->expectExceptionCode(ErrorCodes::ERR_REVOKED_TOKEN);

        $tokenData          = new stdClass();
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
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
        $refreshService->makeAction();
    }

    public function testSuccess()
    {
        $tokenData          = new stdClass();
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
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
        $result = $refreshService->makeAction();

        $this->assertTrue($result);
    }

    public static function passwordChangeProvider()
    {
        $cutoff = 1700000000;

        return [
            'token created before the change is rejected' => [
                $cutoff, gmdate('Y-m-d H:i:s', $cutoff - 60), true, false,
            ],
            'token without creation time is rejected' => [$cutoff, null, true, false],
            'token created after the change is accepted' => [
                $cutoff, gmdate('Y-m-d H:i:s', $cutoff + 60), true, true,
            ],
            'setting off accepts an old token' => [
                $cutoff, gmdate('Y-m-d H:i:s', $cutoff - 60), false, true,
            ],
        ];
    }

    /**
     * The row survives when deleteByUserId() failed on the password change: the cutoff must still block it.
     */
    #[DataProvider('passwordChangeProvider')]
    public function testRefreshTokenIssuedBeforePasswordChange($cutoff, $createdAt, $revokeEnabled, $accepted)
    {
        $tokenData          = new stdClass();
        $tokenData->user_id = 1;
        if ($createdAt !== null) {
            $tokenData->created_at = $createdAt;
        }

        $this->wordPressDataMock->method('getOptionFromDatabase')
            // Nested format: the legacy flat format has no "revoke on password change" key.
            ->willReturn(json_encode([
                '_schema_version' => 2,
                'general'         => ['decryption_key' => 'test-secret'],
                'authorization'   => [
                    'enabled'                   => true,
                    'refresh_token_enabled'     => true,
                    'auth_code'                 => false,
                    'revoke_on_password_change' => $revokeEnabled,
                ],
            ]));
        $this->wordPressDataMock->method('getUserMeta')->willReturn([(string) $cutoff]);
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user-object');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('createResponse')->willReturn(true);
        $refreshTokenRepo = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepo->method('getByToken')->willReturn($tokenData);
        $refreshTokenRepo->method('insert')->willReturn(true);
        $refreshTokenRepo->expects($accepted ? $this->once() : $this->never())
            ->method('deleteByToken')->willReturn(true);

        if (!$accepted) {
            $this->expectException(\Exception::class);
            $this->expectExceptionCode(ErrorCodes::ERR_REVOKED_TOKEN);
        }

        $result = (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'stolen-refresh-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($refreshTokenRepo)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock)
            ->makeAction();

        $this->assertTrue($result);
    }

    public function testOldRefreshTokenIsRotatedOnSuccess()
    {
        $this->refreshTokenRepoMock = $this->createMock(RefreshTokenRepositoryInterface::class);
        $tokenData          = new stdClass();
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
            ->method('deleteByToken')
            ->willReturn(true);
        $this->refreshTokenRepoMock->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo(42),
                $this->isString(),
                $this->isInt()
            )
            ->willReturn(true);

        $refreshService = (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'valid-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
        $refreshService->makeAction();
    }

    /**
     * When a parallel request already claimed (deleted) the token, nothing is issued.
     */
    public function testTokenClaimedByAnotherRequestIssuesNothing()
    {
        $this->refreshTokenRepoMock = $this->createMock(RefreshTokenRepositoryInterface::class);
        $tokenData          = new stdClass();
        $tokenData->user_id = 42;

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
            ]));
        $this->refreshTokenRepoMock->method('getByToken')->willReturn($tokenData);
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user-object');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->refreshTokenRepoMock->method('deleteByToken')->willReturn(false);
        $this->refreshTokenRepoMock->expects($this->never())->method('insert');

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH);

        (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'valid-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '127.0.0.1']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock)
            ->makeAction();
    }

    /**
     * A refresh token that could not be saved must never be handed to the client.
     */
    public function testNewTokenThatCouldNotBeSavedIsNotReturned()
    {
        $tokenData          = new stdClass();
        $tokenData->user_id = 42;

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
            ]));
        $this->refreshTokenRepoMock->method('getByToken')->willReturn($tokenData);
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user-object');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->refreshTokenRepoMock->method('deleteByToken')->willReturn(true);
        $this->refreshTokenRepoMock->method('insert')->willReturn(false);

        $this->expectExceptionCode(ErrorCodes::ERR_REFRESH_TOKEN_STORE_FAILED);

        (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'valid-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '127.0.0.1']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock)
            ->makeAction();
    }

    public function testResponseContainsNewJwtAndRefreshToken()
    {
        $tokenData          = new stdClass();
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
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
        $refreshService->makeAction();

        $this->assertNotNull($capturedResponse);
        $this->assertTrue($capturedResponse['success']);
        $this->assertArrayHasKey('data', $capturedResponse);
        $this->assertArrayHasKey('jwt', $capturedResponse['data']);
        $this->assertArrayHasKey('refresh_token', $capturedResponse['data']);
        $this->assertNotEmpty($capturedResponse['data']['refresh_token']);
    }

    /**
     * A payload hook that copies request data must not plant a nested login-by claim (data.id).
     */
    public function testPayloadHookCannotPlantANestedLoginByClaim(): void
    {
        $tokenData          = new stdClass();
        $tokenData->user_id = 1;
        $capturedResponse   = null;

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
                'jwt_auth_refresh_ttl'    => 1440,
                'jwt_login_by_parameter'  => 'data.id',
                'enabled_hooks'           => [SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME],
            ]));
        $this->refreshTokenRepoMock->method('getByToken')->willReturn($tokenData);
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user-object');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->refreshTokenRepoMock->method('deleteByToken')->willReturn(true);
        $this->refreshTokenRepoMock->method('insert')->willReturn(true);
        $this->wordPressDataMock->method('applyFilters')
            ->willReturnCallback(static function ($hookName, $value) {
                return $hookName === SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME
                    ? $value + ['data' => ['id' => 'victim', 'note' => 'kept']]
                    : $value;
            });
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($response) use (&$capturedResponse) {
                $capturedResponse = $response;
                return true;
            });

        (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'valid-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '127.0.0.1']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock)
            ->makeAction();

        $payload = $this->decodeJwtPayload($capturedResponse['data']['jwt']);
        $this->assertSame(['note' => 'kept'], $payload['data']);
    }

    public function testFailingPayloadHookKeepsTheRefreshTokenUsable(): void
    {
        $tokenData          = new stdClass();
        $tokenData->user_id = 1;

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
                'enabled_hooks'           => [SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME],
            ]));
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user-object');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('applyFilters')
            ->willThrowException(new Exception('hook failed'));
        $refreshTokenRepo = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokenRepo->method('getByToken')->willReturn($tokenData);
        // The token is claimed only once the replacement is ready, so a retry can still use it.
        $refreshTokenRepo->expects($this->never())->method('deleteByToken');
        $refreshTokenRepo->expects($this->never())->method('insert');

        $this->expectExceptionMessage('hook failed');
        (new RefreshTokenService())
            ->withRequest(['refresh_token' => 'valid-token'])
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '127.0.0.1']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($refreshTokenRepo)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock)
            ->makeAction();
    }

    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        $payloadSegment = strtr($parts[1], '-_', '+/');
        $decoded = json_decode(base64_decode($payloadSegment), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function testRequestPayloadAcceptsJsonEncodedString(): void
    {
        /** @var array|null $capturedResponse */
        $capturedResponse = null;

        $tokenData          = new stdClass();
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
        $this->wordPressDataMock->method('sanitizeTextField')->willReturnArgument(0);
        $this->wordPressDataMock->method('sanitizeArray')->willReturnArgument(0);
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($response) use (&$capturedResponse) {
                $capturedResponse = $response;
                return true;
            });

        $refreshService = (new RefreshTokenService())
            ->withRequest([
                'refresh_token' => 'valid-token',
                'payload'       => json_encode(['department' => 'engineering', 'region' => 'eu']),
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
        $refreshService->makeAction();

        $payload = $this->decodeJwtPayload($capturedResponse['data']['jwt']);
        $this->assertSame('engineering', $payload['department']);
        $this->assertSame('eu', $payload['region']);
    }

    public function testRequestPayloadAcceptsNativeArray(): void
    {
        /** @var array|null $capturedResponse */
        $capturedResponse = null;

        $tokenData          = new stdClass();
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
        $this->wordPressDataMock->method('sanitizeArray')->willReturnArgument(0);
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($response) use (&$capturedResponse) {
                $capturedResponse = $response;
                return true;
            });

        $refreshService = (new RefreshTokenService())
            ->withRequest([
                'refresh_token' => 'valid-token',
                'payload'       => ['department' => 'engineering', 'region' => 'eu'],
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
        $refreshService->makeAction();

        $payload = $this->decodeJwtPayload($capturedResponse['data']['jwt']);
        $this->assertSame('engineering', $payload['department']);
        $this->assertSame('eu', $payload['region']);
    }

    public function testRequestPayloadCannotOverwriteReservedEmailClaim(): void
    {
        /** @var array|null $capturedResponse */
        $capturedResponse = null;

        $tokenData          = new stdClass();
        $tokenData->user_id = 1;

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication'    => true,
                'allow_refresh_token'     => true,
                'auth_requires_auth_code' => false,
                'decryption_key'          => 'test-secret',
                'jwt_auth_refresh_ttl'    => 1440,
                'jwt_payload'             => [AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL],
            ]));
        $this->refreshTokenRepoMock->method('getByToken')->willReturn($tokenData);
        $this->wordPressDataMock->method('getUserDetailsById')->willReturn('user-object');
        $this->wordPressDataMock->method('isInstanceOfuser')->willReturn(true);
        $this->wordPressDataMock->method('getUserProperty')->willReturn('real-user@test.com');
        $this->refreshTokenRepoMock->method('deleteByToken')->willReturn(true);
        $this->refreshTokenRepoMock->method('insert')->willReturn(true);
        $this->wordPressDataMock->method('sanitizeArray')->willReturnArgument(0);
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($response) use (&$capturedResponse) {
                $capturedResponse = $response;
                return true;
            });

        $refreshService = (new RefreshTokenService())
            ->withRequest([
                'refresh_token' => 'valid-token',
                // Attempt to impersonate another account via the payload claim.
                'payload'       => ['email' => 'admin@test.com'],
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRefreshTokenRepository($this->refreshTokenRepoMock)
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);
        $refreshService->makeAction();

        $payload = $this->decodeJwtPayload($capturedResponse['data']['jwt']);
        $this->assertSame('real-user@test.com', $payload['email']);
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
