<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * A JWT matched by a JWT Rule is mapped to a WordPress user with that rule's "login by" settings,
 * on every endpoint - never with the global ones. Otherwise an external IdP claim such as
 * "id": 1 is read as WordPress user 1 when the site logs in by user ID.
 */
class JwtRuleLoginByTest extends FeatureTestCase
{
    private const SITE_KEY = 'rule-login-by-site-secret';

    private const RULE_KEY = 'rule-login-by-rule-secret';

    private const RULE_ISSUER = 'external-idp';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        $settings = array_replace_recursive($fixture, [
            'general'       => [
                'decryption_key'     => self::SITE_KEY,
                'request_jwt_header' => 1,
                'api_middleware'     => ['enabled' => true],
            ],
            'register'      => ['enabled' => true, 'new_user_profile' => 'subscriber'],
            'authorization' => ['enabled' => '1', 'validate_token_enabled' => '1'],
            // Global: log in by WordPress user ID from the "id" claim.
            'login'         => [
                'login_by'           => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
                'login_by_parameter' => 'id',
            ],
            'cors'          => ['enabled' => 0],
        ]);
        // The external IdP rule: log in by email from the "email" claim.
        $settings['jwt_rules'] = ['rules' => [[
            'condition_type'        => 'iss',
            'condition_key'         => 'iss',
            'condition_operator'    => 'equals',
            'condition_value'       => self::RULE_ISSUER,
            'iss'                   => self::RULE_ISSUER,
            'algorithm'             => 'HS256',
            'decryption_key'        => self::RULE_KEY,
            'decryption_key_base64' => false,
            'login_by'              => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'login_by_parameter'    => 'email',
        ]]];
        self::updateSimpleJWTOption($settings);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function endpointProvider(): array
    {
        return [
            'validate token endpoint' => ['validate'],
            'API middleware'          => ['middleware'],
        ];
    }

    #[DataProvider('endpointProvider')]
    #[TestDox('A JWT Rule token is mapped with the rule login-by settings on the $endpoint')]
    public function testRuleTokenIsMappedWithTheRuleLoginBy(string $endpoint): void
    {
        [$email, , $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $userId = $this->lookupUserId($email);
        // "id": 1 is the IdP's own subject number; WordPress user 1 is the administrator.
        $jwt = JWT::encode(
            ['iss' => self::RULE_ISSUER, 'id' => 1, 'email' => $email, 'exp' => time() + 600],
            self::RULE_KEY,
            'HS256'
        );

        $this->assertSame($userId, $this->resolvedUserId($endpoint, $jwt));
    }

    #[DataProvider('endpointProvider')]
    #[TestDox('A plugin token is still mapped with the global login-by settings on the $endpoint')]
    public function testPluginTokenUsesTheGlobalLoginBy(string $endpoint): void
    {
        [$email, , $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $userId = $this->lookupUserId($email);
        $jwt    = JWT::encode(['id' => $userId, 'exp' => time() + 600], self::SITE_KEY, 'HS256');

        $this->assertSame($userId, $this->resolvedUserId($endpoint, $jwt));
    }

    private function resolvedUserId(string $endpoint, string $jwt): int
    {
        if ($endpoint === 'validate') {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', ['JWT' => $jwt]);
            $body     = (array) json_decode((string) $response->getBody(), true);
            $this->assertSame(200, $response->getStatusCode(), (string) json_encode($body));

            return (int) $body['data']['user']['ID'];
        }
        $response = $this->client->request('GET', self::API_URL . '/?rest_route=/wp/v2/users/me', [
            'http_errors' => false,
            'headers'     => $this->authHeader($jwt),
        ]);
        $body = (array) json_decode((string) $response->getBody(), true);
        $this->assertSame(200, $response->getStatusCode(), (string) json_encode($body));

        return (int) $body['id'];
    }
}
