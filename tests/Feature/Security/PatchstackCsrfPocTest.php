<?php

namespace SimpleJwtLoginTests\Feature\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Replays the exact request of the Patchstack retest of 4.1.0
 * (form POST /?rest_route=/wp/v2/users, attacker Origin + Referer,
 * `simple-jwt-login-token` cookie holding an admin JWT) against every
 * settings combination that can read a JWT on a core route, with the JWT in
 * the cookie (the email) and in the Authorization header.
 */
class PatchstackCsrfPocTest extends FeatureTestCase
{
    private const SECRET = 'patchstack-csrf-poc-secret-key';

    private const ATTACKER = 'http://attacker.example';

    /**
     * @return array<string,array<string,mixed>>
     */
    private static function settingsCombinations(): array
    {
        $protectAll = [
            'enabled'        => 1,
            'default_action' => ProtectEndpointSettings::DEFAULT_PROTECT_ALL,
            'rules_url'      => [],
            'rules_method'   => [],
            'rules_match'    => [],
            'rules_type'     => [],
            'rules_roles'    => [],
        ];
        $allSources = ['request_jwt_cookie' => 1, 'request_jwt_header' => 1, 'request_jwt_session' => 1];

        return [
            'default settings' => [],
            'all JWT sources on, middleware off' => ['general' => $allSources],
            'all JWT sources on, middleware on (JWT on all endpoints)' => [
                'general' => $allSources + ['api_middleware' => ['enabled' => true]],
            ],
            'all JWT sources on, protect all endpoints' => [
                'general'          => $allSources,
                'protect_endpoint' => $protectAll,
            ],
            'all JWT sources on, middleware on, protect all endpoints' => [
                'general'          => $allSources + ['api_middleware' => ['enabled' => true]],
                'protect_endpoint' => $protectAll,
            ],
            'middleware on, CORS "*"' => [
                'general' => $allSources + ['api_middleware' => ['enabled' => true]],
                'cors'    => ['enabled' => 1, 'allow_origin_enabled' => true, 'allow_origin' => '*'],
                'trusts'  => ['header'],
            ],
            'middleware on, CORS lists the attacker origin' => [
                'general' => $allSources + ['api_middleware' => ['enabled' => true]],
                'cors'    => ['enabled' => 1, 'allow_origin_enabled' => true, 'allow_origin' => self::ATTACKER],
                'trusts'  => ['cookie', 'header'],
            ],
        ];
    }

    /**
     * Settings x JWT credential. `trusts` lists the credentials an admin
     * explicitly opened to the attacker origin through the CORS settings.
     *
     * @return array<string,array{array<string,mixed>, string, bool}>
     */
    public static function pocProvider(): array
    {
        $cases = [];
        foreach (self::settingsCombinations() as $name => $settings) {
            $trusts = isset($settings['trusts']) ? $settings['trusts'] : [];
            unset($settings['trusts']);
            foreach (['cookie', 'header'] as $credential) {
                $cases[$name . ' | JWT in ' . $credential] = [
                    $settings,
                    $credential,
                    in_array($credential, $trusts, true),
                ];
            }
        }

        return $cases;
    }

    /**
     * @param array<string,mixed> $overrides
     */
    #[DataProvider('pocProvider')]
    #[TestDox('The Patchstack PoC from the email ($_dataName)')]
    public function testPatchstackPoc(array $overrides, string $credential, bool $originTrusted): void
    {
        $this->applySettings($overrides);
        [$marker, $status] = $this->replayPoc($credential, [
            'Origin'  => self::ATTACKER,
            'Referer' => self::ATTACKER . '/csrf.html',
        ]);

        if ($originTrusted) {
            $this->assertTrue($this->userCreated($marker), 'Origin listed in CORS was refused, status ' . $status);
            return;
        }
        $this->assertFalse($this->userCreated($marker), 'PoC created an administrator, status ' . $status);
        $this->assertNotSame(201, $status);
    }

    /**
     * The same request from this site must still work: proves the PoC reaches
     * the JWT code path, so the cross-site result above is the fix at work.
     */
    #[DataProvider('credentialProvider')]
    #[TestDox('Control: the PoC request sent from this site creates the administrator (JWT in $_dataName)')]
    public function testPocRequestFromThisSiteWorks(string $credential): void
    {
        $this->applySettings(['general' => [
            'request_jwt_cookie' => 1,
            'request_jwt_header' => 1,
            'api_middleware'     => ['enabled' => true],
        ]]);
        [$marker, $status] = $this->replayPoc($credential, [
            'Origin'  => self::API_URL,
            'Referer' => self::API_URL . '/wp-admin/',
        ]);

        $this->assertSame(201, $status);
        $this->assertTrue($this->userCreated($marker));
    }

    /**
     * @return array<string,array{string}>
     */
    public static function credentialProvider(): array
    {
        return ['cookie' => ['cookie'], 'header' => ['header']];
    }

    /**
     * @param array<string,mixed> $overrides
     */
    private function applySettings(array $overrides): void
    {
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'general'       => ['decryption_key' => self::SECRET],
            'authorization' => ['enabled' => '1'],
            'register'      => ['enabled' => true, 'new_user_profile' => 'subscriber'],
            'cors'          => ['enabled' => 0],
        ], $overrides));
    }

    /**
     * The exact request from the email: form POST /?rest_route=/wp/v2/users.
     *
     * @param array<string,string> $provenance
     * @return array{string, int}
     */
    private function replayPoc(string $credential, array $provenance): array
    {
        [$email, $password] = $this->createAdminUser();
        $adminJwt = (string) $this->getJWTForUser($email, $password);
        $marker   = 'wpvfy_csrf_' . bin2hex(random_bytes(5));
        $jwtHeader = $credential === 'header'
            ? ['Authorization' => 'Bearer ' . $adminJwt]
            : ['Cookie' => 'simple-jwt-login-token=' . $adminJwt];

        $response = $this->client->request('POST', self::API_URL . '/?rest_route=/wp/v2/users', [
            'http_errors' => false,
            'headers'     => array_merge($provenance, $jwtHeader),
            'form_params' => [
                'username' => $marker,
                'email'    => $marker . '@example.invalid',
                'roles'    => 'administrator',
                'password' => bin2hex(random_bytes(8)),
            ],
        ]);

        return [$marker, $response->getStatusCode()];
    }

    private function userCreated(string $marker): bool
    {
        $table  = self::getTablePrefix() . 'users';
        $result = self::$dbCon->query("SELECT ID FROM `{$table}` WHERE user_login = '{$marker}' LIMIT 1");

        return $result->num_rows > 0;
    }
}
