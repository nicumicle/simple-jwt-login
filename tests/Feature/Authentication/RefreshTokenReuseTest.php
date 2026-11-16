<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use GuzzleHttp\Promise\Utils;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * A refresh token is single-use, even when the same token is sent by parallel requests.
 */
class RefreshTokenReuseTest extends FeatureTestCase
{
    private const PARALLEL_REQUESTS = 8;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'register'      => ['enabled' => true, 'new_user_profile' => 'subscriber'],
            'authorization' => ['enabled' => '1', 'refresh_token_enabled' => '1'],
        ]));
    }

    #[TestDox('Parallel refreshes with the same refresh token issue only one new token')]
    public function testParallelRefreshesWithTheSameTokenSucceedOnce(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        [$authStatus, $authBody] = $this->authUser($email, $password);
        $this->assertSame(200, $authStatus, 'auth failed');
        $refreshToken = json_decode((string) $authBody, true)['data']['refresh_token'];

        $promises = [];
        for ($request = 0; $request < self::PARALLEL_REQUESTS; $request++) {
            $promises[] = $this->client->postAsync(self::API_URL . '/?rest_route=/simple-jwt-login/v1/auth/refresh', [
                'http_errors' => false,
                'headers'     => ['Content-Type' => 'application/json'],
                'body'        => json_encode(['refresh_token' => $refreshToken]),
            ]);
        }
        $statuses = [];
        foreach (Utils::settle($promises)->wait() as $result) {
            $this->assertSame('fulfilled', $result['state']);
            $statuses[] = $result['value']->getStatusCode();
        }

        $this->assertSame(1, count(array_keys($statuses, 200, true)), 'Statuses: ' . implode(', ', $statuses));
    }

    #[TestDox('A refresh token can not be used a second time')]
    public function testRefreshTokenIsSingleUse(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        [, $authBody] = $this->authUser($email, $password);
        $refreshToken = json_decode((string) $authBody, true)['data']['refresh_token'];

        $first  = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', ['refresh_token' => $refreshToken]);
        $second = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', ['refresh_token' => $refreshToken]);

        $this->assertSame(200, $first->getStatusCode(), (string) $first->getBody());
        $this->assertNotSame(200, $second->getStatusCode(), (string) $second->getBody());
    }
}
