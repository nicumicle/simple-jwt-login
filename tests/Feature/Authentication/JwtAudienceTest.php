<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * An optional expected audience ("aud") binds JWTs to this site, or to one application per JWT Rule,
 * so a JWT minted for another application with the same key or issuer is rejected.
 */
class JwtAudienceTest extends FeatureTestCase
{
    private const SITE_KEY = 'audience-site-secret';

    private const RULE_KEY = 'audience-rule-secret';

    private const RULE_ISSUER = 'ext-issuer';

    /**
     * @var string
     */
    private static $email = '';

    public function setUp(): void
    {
        parent::setUp();
        if (self::$email === '') {
            self::applySettings('', '');
            [$email, , $status] = $this->createUser();
            $this->assertSame(200, $status, 'register failed');
            self::$email = $email;
        }
    }

    /**
     * @return array<string, array{string, string, array<string, mixed>, bool}>
     */
    public static function audienceProvider(): array
    {
        return [
            'site audience: other audience'       => ['site-a', '', ['aud' => 'site-b'], false],
            'site audience: no audience'          => ['site-a', '', [], false],
            'site audience: matching audience'    => ['site-a', '', ['aud' => 'site-a'], true],
            'site audience: audience in a list'   => ['site-a', '', ['aud' => ['x', 'site-a']], true],
            'rule audience: other audience'       => ['', 'app-1', ['iss' => self::RULE_ISSUER, 'aud' => 'app-2'], false],
            'rule audience: no audience'          => ['', 'app-1', ['iss' => self::RULE_ISSUER], false],
            'rule audience: matching audience'    => ['', 'app-1', ['iss' => self::RULE_ISSUER, 'aud' => 'app-1'], true],
            'rule audience: audience in a list'   => ['', 'app-1', ['iss' => self::RULE_ISSUER, 'aud' => ['x', 'app-1']], true],
            'rule audience is not the site one'   => ['site-a', 'app-1', ['iss' => self::RULE_ISSUER, 'aud' => 'site-a'], false],
            'no audience configured: any aud'     => ['', '', ['aud' => 'anything'], true],
            'no audience configured: rule no aud' => ['', '', ['iss' => self::RULE_ISSUER], true],
        ];
    }

    /**
     * @param array<string, mixed> $claims
     */
    #[DataProvider('audienceProvider')]
    #[TestDox('JWT audience check: $_dataName')]
    public function testJwtAudienceIsChecked(string $siteAudience, string $ruleAudience, array $claims, bool $accepted): void
    {
        self::applySettings($siteAudience, $ruleAudience);
        $key = isset($claims['iss']) ? self::RULE_KEY : self::SITE_KEY;
        $jwt = JWT::encode($claims + ['email' => self::$email, 'exp' => time() + 600], $key, 'HS256');

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', ['JWT' => $jwt]);
        $body     = (array) json_decode((string) $response->getBody(), true);

        if ($accepted) {
            $this->assertSame(200, $response->getStatusCode(), (string) json_encode($body));

            return;
        }
        $this->assertSame(401, $response->getStatusCode(), (string) json_encode($body));
        $this->assertSame(ErrorCodes::ERR_JWT_AUDIENCE_MISMATCH, $body['data']['error_code']);
    }

    #[TestDox('With a site audience, the JWT from /auth carries it and validates')]
    public function testIssuedJwtCarriesTheSiteAudience(): void
    {
        self::applySettings('site-a', '');
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $jwt     = (string) $this->getJWTForUser($email, $password);
        $payload = json_decode((string) base64_decode(strtr(explode('.', $jwt)[1], '-_', '+/')), true);
        $this->assertSame('site-a', $payload['aud']);

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', ['JWT' => $jwt]);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[TestDox('With a site audience, a client can not choose the aud of the JWT from /auth')]
    public function testClientCanNotChooseTheAudience(): void
    {
        self::applySettings('site-a', '');
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => $password,
            'payload'  => json_encode(['aud' => 'site-b']),
        ]);
        $jwt     = json_decode((string) $response->getBody(), true)['data']['jwt'];
        $payload = json_decode((string) base64_decode(strtr(explode('.', $jwt)[1], '-_', '+/')), true);

        $this->assertSame('site-a', $payload['aud']);
    }

    private static function applySettings(string $siteAudience, string $ruleAudience): void
    {
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        $settings = array_replace_recursive($fixture, [
            'general'       => ['decryption_key' => self::SITE_KEY],
            'register'      => ['enabled' => true, 'new_user_profile' => 'subscriber'],
            'authorization' => ['enabled' => '1', 'validate_token_enabled' => '1', 'aud' => $siteAudience],
        ]);
        $settings['jwt_rules'] = ['rules' => [[
            'condition_type'        => 'iss',
            'condition_key'         => 'iss',
            'condition_operator'    => 'equals',
            'condition_value'       => self::RULE_ISSUER,
            'iss'                   => self::RULE_ISSUER,
            'algorithm'             => 'HS256',
            'decryption_key'        => self::RULE_KEY,
            'decryption_key_base64' => false,
            'login_by'              => 0,
            'login_by_parameter'    => 'email',
            'audience'              => $ruleAudience,
        ]]];
        self::updateSimpleJWTOption($settings);
    }
}
