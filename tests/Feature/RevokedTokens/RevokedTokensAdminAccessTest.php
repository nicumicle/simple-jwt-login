<?php

namespace SimpleJwtLoginTests\Feature\RevokedTokens;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * The revoked-token endpoints are internal (Security > Revoked Tokens admin tab):
 * only administrators may list or delete revoked-token records.
 */
class RevokedTokensAdminAccessTest extends FeatureTestCase
{
    private const BASE = '/simple-jwt-login/v1/revoked-tokens';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption([
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => 'test-secret',
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'require_register_auth'   => false,
            'register_ip'             => '',
            'register_domain'         => '',
            'jwt_login_by'            => 0,
            'jwt_login_by_parameter'  => 'email',
        ]);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function endpointProvider(): array
    {
        return [
            'list revoked tokens'  => ['GET', false],
            'delete revoked token' => ['DELETE', true],
        ];
    }

    #[DataProvider('endpointProvider')]
    #[TestDox('Anonymous $method call is rejected with 401 rest_forbidden')]
    public function testAnonymousIsRejected(string $method, bool $withId): void
    {
        $recordId = $this->insertRevokedToken();

        $response = $this->request($method, $this->route($withId, $recordId));

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertSame('rest_forbidden', $body['code']);
        $this->assertTrue($this->revokedTokenExists($recordId));
    }

    #[DataProvider('endpointProvider')]
    #[TestDox('Subscriber $method call is rejected with 403')]
    public function testSubscriberIsRejected(string $method, bool $withId): void
    {
        $recordId = $this->insertRevokedToken();
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'pre-condition: register must succeed');
        $jwt = $this->getJWTForUser($email, $password);

        $response = $this->request($method, $this->route($withId, $recordId), [], $this->authHeader($jwt));

        $this->assertSame(403, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN_UNAUTHORIZED, $body['data']['error_code']);
        $this->assertTrue($this->revokedTokenExists($recordId));
    }

    #[TestDox('Administrator can list revoked tokens')]
    public function testAdministratorCanList(): void
    {
        $recordId = $this->insertRevokedToken();
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $response = $this->request('GET', self::BASE . '&per_page=100', [], $this->authHeader($jwt));

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertContains($recordId, array_column($body['data']['items'], 'id'));
    }

    #[TestDox('Administrator can delete a revoked token by its path id')]
    public function testAdministratorCanDelete(): void
    {
        $recordId = $this->insertRevokedToken();
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $response = $this->request('DELETE', $this->route(true, $recordId), [], $this->authHeader($jwt));

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertFalse($this->revokedTokenExists($recordId));
    }

    private function route(bool $withId, int $recordId): string
    {
        return $withId ? self::BASE . '/' . $recordId : self::BASE;
    }

    private function insertRevokedToken(): int
    {
        $table = self::getTablePrefix() . RevokedTokenRepository::TABLE_SUFFIX;
        $hash  = hash('sha256', uniqid('sjl-admin-access-', true));
        $now   = gmdate('Y-m-d H:i:s');
        $exp   = gmdate('Y-m-d H:i:s', time() + 3600);
        self::$dbCon->query(
            "INSERT INTO `{$table}` (user_id, token_hash, expires_at, revoked_at)
             VALUES (1, '{$hash}', '{$exp}', '{$now}')"
        );
        $this->assertGreaterThan(0, self::$dbCon->insert_id, 'pre-condition: revoked token row inserted');

        return (int) self::$dbCon->insert_id;
    }

    private function revokedTokenExists(int $recordId): bool
    {
        $table  = self::getTablePrefix() . RevokedTokenRepository::TABLE_SUFFIX;
        $result = self::$dbCon->query("SELECT 1 FROM `{$table}` WHERE id = {$recordId} LIMIT 1");

        return $result->num_rows > 0;
    }
}
