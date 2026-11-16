<?php

namespace SimpleJwtLoginTests\Feature\Security;

use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJwtLoginTests\Feature\FeatureTestCase;
use SimpleJwtLoginTests\Feature\MysqliWpdb;

/**
 * WordPress /batch/v1 dispatches every sub-request through rest_pre_dispatch. Neither
 * Protect Endpoints nor API key permissions may be skipped by wrapping a call in a batch.
 */
class BatchRequestTest extends FeatureTestCase
{
    const API_KEY_HEADER = 'X-API-Key';

    /**
     * @return array<string,mixed>
     */
    private static function baseSettings()
    {
        return [
            'api_keys' => [
                'enabled'     => true,
                'header_name' => self::API_KEY_HEADER,
            ],
            'allow_register'        => true,
            'new_user_profile'      => 'subscriber',
            'require_register_auth' => false,
            'register_ip'           => '',
            'register_domain'       => '',
            'decryption_key'        => 'test-secret',
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::baseSettings());
        self::ensureApiKeyTable();
    }

    public static function tearDownAfterClass(): void
    {
        self::updateSimpleJWTOption(self::baseSettings());
        parent::tearDownAfterClass();
    }

    public function testProtectedRouteInsideABatchStillNeedsAJwt()
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'protect_endpoints' => [
                'enabled'        => true,
                'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                'protect'        => ['/wp/v2/posts'],
                'protect_method' => ['ALL'],
                'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                'whitelist'      => [],
            ],
        ]));

        $response = $this->batch([['method' => 'POST', 'path' => '/wp/v2/posts', 'body' => ['title' => 'x']]]);
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(207, $response->getStatusCode(), json_encode($body));
        $subResponse = $body['responses'][0];
        $this->assertSame(401, $subResponse['status'], json_encode($subResponse));
        // Our guard answered, not WordPress' own capability check further down.
        $this->assertSame(ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT, $subResponse['body']['data']['error_code']);
    }

    public function testCreateOnlyApiKeyCannotBatch()
    {
        self::updateSimpleJWTOption(self::baseSettings());
        $rawKey = $this->createAdminApiKey(['create']);

        $response = $this->batch(
            [['method' => 'POST', 'path' => '/wp/v2/posts', 'body' => ['title' => 'x']]],
            [self::API_KEY_HEADER => $rawKey]
        );
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(401, $response->getStatusCode(), json_encode($body));
        $this->assertSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $body['data']['error_code']);
    }

    public function testWriteApiKeyCanBatch()
    {
        self::updateSimpleJWTOption(self::baseSettings());
        $rawKey = $this->createAdminApiKey(['create', 'update', 'delete']);

        $response = $this->batch(
            [['method' => 'POST', 'path' => '/wp/v2/posts', 'body' => ['title' => 'batch test']]],
            [self::API_KEY_HEADER => $rawKey]
        );
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertSame(207, $response->getStatusCode(), json_encode($body));
        $this->assertSame(201, $body['responses'][0]['status'], json_encode($body));
    }

    /**
     * @param array<int, array<string, mixed>> $requests
     * @param array<string, string> $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function batch($requests, $headers = [])
    {
        return $this->jsonRequest('POST', '/batch/v1', ['requests' => $requests], $headers);
    }

    /**
     * @param string[] $permissions
     * @return string The raw key.
     */
    private function createAdminApiKey($permissions)
    {
        [, , $userId] = $this->createAdminUser();
        $rawKey = 'sjl_' . bin2hex(openssl_random_pseudo_bytes(16));
        $keyId = (new ApiKeyRepository(new MysqliWpdb(self::$dbCon, self::getTablePrefix())))->insert(
            $userId,
            'batch-test-key',
            hash('sha256', $rawKey),
            substr($rawKey, 0, 8),
            (string) json_encode($permissions),
            null,
            gmdate('Y-m-d H:i:s')
        );
        $this->assertNotFalse($keyId);

        return $rawKey;
    }
}
