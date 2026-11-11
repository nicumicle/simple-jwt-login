<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\DiagnoseAbility;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\InspectJwtAbility;
use SimpleJWTLogin\Modules\Abilities\SettingsPath;
use SimpleJWTLogin\Modules\Abilities\Webhooks\GetWebhookLogsAbility;
use SimpleJWTLogin\Modules\Jwt\JwtWrapper;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

#[RunTestsInSeparateProcesses]
class DiagnosticsAbilitiesTest extends AbilityTestCase
{
    const SIGNING_KEY = 'fixture-secret';

    /**
     * @param array $payload
     * @param string $key
     * @return string
     */
    private static function token(array $payload, $key = self::SIGNING_KEY)
    {
        return (new JwtWrapper())->encode($payload, $key, 'HS256');
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool, 3: int|null, 4: bool}>
     */
    public static function jwtProvider()
    {
        $future = time() + 3600;

        return [
            'valid token'         => [self::token(['email' => 'admin@example.com', 'exp' => $future]), 'ok', true, null, false],
            'bearer prefix'       => ['Bearer ' . self::token(['email' => 'admin@example.com', 'exp' => $future]), 'ok', true, null, false],
            'wrong key'           => [self::token(['email' => 'admin@example.com', 'exp' => $future], 'other-key'), 'signature', false, ErrorCodes::ERR_SIGNATURE_VERIFICATION_FAILED, false],
            'expired'             => [self::token(['email' => 'admin@example.com', 'exp' => time() - 7200]), 'signature', false, ErrorCodes::ERR_TOKEN_EXPIRED, false],
            'not a jwt'           => ['not-a-jwt', 'decode', false, ErrorCodes::ERR_WRONG_NUMBER_OF_SEGMENTS, false],
            'login claim missing' => [self::token(['id' => 1, 'exp' => $future]), 'user', false, null, false],
            'unknown user'        => [self::token(['email' => 'ghost@example.com', 'exp' => $future]), 'user', false, null, false],
            'two factor pending'  => [self::token(['email' => 'admin@example.com', 'exp' => $future, 'tfa_pending' => true]), 'two_factor_pending', false, ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REJECTED, false],
            'revoked'             => [self::token(['email' => 'admin@example.com', 'exp' => $future]), 'revoked', false, ErrorCodes::ERR_REVOKED_TOKEN, true],
        ];
    }

    #[DataProvider('jwtProvider')]
    public function testInspectJwt($jwt, $stage, $valid, $errorCode, $revoked)
    {
        $wordPressData = $this->makeWordPressDataStub($this->storedSettings());
        $user          = (object) ['ID' => 1, 'user_login' => 'admin'];
        $wordPressData->method('getUserDetailsByEmail')->willReturnCallback(function ($email) use ($user) {
            return $email === 'admin@example.com' ? $user : null;
        });
        $wordPressData->method('isInstanceOfuser')->willReturnCallback('is_object');
        $wordPressData->method('getUserProperty')->willReturnCallback(function ($wpUser, $property) {
            return $wpUser->$property;
        });
        $wordPressData->method('getUserRoles')->willReturn(['administrator']);

        $revokedTokens = $this->createStub(RevokedTokenRepositoryInterface::class);
        $revokedTokens->method('existsForUser')->willReturn($revoked);

        $result = (new InspectJwtAbility($this->makeSettings($wordPressData), $revokedTokens))->execute(['jwt' => $jwt]);

        $this->assertSame($stage, $result['stage']);
        $this->assertSame($valid, $result['valid']);
        $this->assertSame($errorCode, $result['error_code']);
        $this->assertStringNotContainsString(self::SIGNING_KEY, (string) json_encode($result));
        if ($valid) {
            $this->assertSame(['administrator'], $result['user']['roles']);
            $this->assertSame('HS256', $result['expected_algorithm']);
        }
    }

    public function testDiagnoseReportsEndpointsRolesAndWarnings()
    {
        $stored = $this->storedSettings([
            'general'      => ['decryption_key' => '', 'request_jwt_url' => 1],
            'register'     => ['enabled' => true, 'auth_code' => false, 'new_user_profile' => 'administrator'],
            'integrations' => ['3rdparty' => ['two_factor' => ['enabled' => true]]],
        ]);
        $wordPressData = $this->makeWordPressDataStub($stored);
        $wordPressData->method('getSiteUrl')->willReturn('https://example.com/');
        $wordPressData->method('getWordPressVersion')->willReturn('6.9');
        $wordPressData->method('getRoleNames')->willReturn(['administrator' => 'Administrator', 'editor' => 'Editor']);
        $bridge = $this->createStub(TwoFactorBridge::class);
        $bridge->method('isAvailable')->willReturn(false);

        $result = (new DiagnoseAbility($this->makeSettings($wordPressData), $bridge))->execute([]);

        $this->assertSame('https://example.com/wp-json/simple-jwt-login/v1/', $result['rest']->base_url);
        $this->assertSame('https://example.com/?rest_route=/simple-jwt-login/v1/', $result['rest']->plain_url);
        $register = array_values(array_filter($result['endpoints'], function ($endpoint) {
            return $endpoint['method'] === 'POST' && substr($endpoint['url'], -5) === 'users';
        }));
        $this->assertTrue($register[0]['enabled']);
        $this->assertFalse($register[0]['auth_code_required']);
        $this->assertSame(['administrator' => 'Administrator', 'editor' => 'Editor'], (array) $result['roles']);

        $codes = array_column($result['warnings'], 'code');
        foreach (['signing_key_missing', 'register_creates_admins', 'register_without_auth_code', 'jwt_in_url', 'two_factor_plugin_missing'] as $expected) {
            $this->assertContains($expected, $codes);
        }
        foreach ($result['warnings'] as $warning) {
            foreach ($warning['settings'] as $path) {
                $this->assertTrue(SettingsPath::exists($stored, $path), $path);
            }
        }
        $this->assertStringNotContainsString(self::SIGNING_KEY, (string) json_encode($result));
    }

    public function testDiagnoseHasNoWarningsForSafeDefaults()
    {
        $stored = $this->storedSettings([
            'general'   => ['request_jwt_url' => 0],
            'register'    => ['enabled' => false],
            'delete_user' => ['enabled' => false],
            'audit_log'   => ['enabled' => true],
            'cors'      => ['enabled' => false],
        ]);
        $wordPressData = $this->makeWordPressDataStub($stored);
        $wordPressData->method('getRoleNames')->willReturn([]);

        $result = (new DiagnoseAbility($this->makeSettings($wordPressData), $this->createStub(TwoFactorBridge::class)))
            ->execute(['warnings_only' => true]);

        $this->assertSame(['warnings' => []], $result);
    }

    public function testWebhookLogsMaskQueryValuesAndTruncateBodies()
    {
        $repository = $this->createMock(WebhookLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findPaginated')
            ->with(['status' => 'failure'], 1, 20)
            ->willReturn([
                'items' => [(object) [
                    'id' => '3', 'webhook_url' => 'https://hooks.example.com/in?token=abc&flag',
                    'event' => 'login', 'method' => 'POST', 'status_code' => null,
                    'response_body' => str_repeat('x', 1500), 'created_at' => '2026-11-08 10:00:00',
                ]],
                'total' => '1',
            ]);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        $result   = (new GetWebhookLogsAbility($settings, $repository))->execute(['status' => 'failure']);

        $item = $result['items'][0];
        $this->assertSame('https://hooks.example.com/in?token=********&flag', $item['url']);
        $this->assertNull($item['status_code']);
        $this->assertSame(1003, strlen($item['response_body']));
        $this->assertSame(1, $result['total']);
    }
}
