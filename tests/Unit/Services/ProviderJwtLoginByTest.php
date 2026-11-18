<?php

namespace SimpleJwtLoginTests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;
use SimpleJWTLogin\Services\RouteService;
use stdClass;

/**
 * A Google ID token accepted on all endpoints is mapped by its verified email, even when a
 * JWT Rule matches its issuer: the rule's lookup mode applies to the rule's own claim only.
 */
class ProviderJwtLoginByTest extends TestCase
{
    const CLIENT_ID = 'site-client-id';
    const EMAIL     = '1attacker@example.com';

    public function tearDown(): void
    {
        unset($GLOBALS['sjl_test_http']);
        parent::tearDown();
    }

    public function testGoogleTokenIsLookedUpByEmailWhenAnIdRuleMatchesItsIssuer()
    {
        $GLOBALS['sjl_test_http'] = function () {
            return ['response' => ['code' => 200], 'body' => json_encode([
                'iss'            => GoogleOauth::IIS,
                'aud'            => self::CLIENT_ID,
                'email'          => self::EMAIL,
                'email_verified' => 'true',
            ])];
        };
        $emailUser     = new stdClass();
        $emailUser->ID = 42;
        $adminUser     = new stdClass();
        $adminUser->ID = 1;

        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('getOptionFromDatabase')->willReturn(json_encode([
            '_schema_version' => 2,
            'general'         => ['decryption_key' => '123'],
            'login'           => ['jwt_login_by' => LoginSettings::JWT_LOGIN_BY_EMAIL, 'jwt_login_by_parameter' => 'email'],
            'integrations'    => ['oauth' => ['google' => [
                'enabled'                => 1,
                'client_id'              => self::CLIENT_ID,
                'allow_on_all_endpoints' => true,
            ]]],
            'jwt_rules'       => ['rules' => [[
                'condition_type'     => 'iss',
                'condition_value'    => GoogleOauth::IIS,
                'algorithm'          => 'HS256',
                'login_by'           => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
                'login_by_parameter' => 'id',
            ]]],
        ]));
        $wordPressData->method('getUserDetailsByEmail')->willReturnMap([[self::EMAIL, $emailUser]]);
        $wordPressData->method('getUserDetailsById')->willReturnMap([[1, $adminUser]]);
        $wordPressData->method('isInstanceOfuser')->willReturnCallback('is_object');

        $service = (new RouteService())
            ->withSettings(new SimpleJWTLoginSettings($wordPressData))
            ->withServerHelper(new ServerHelper([]))
            ->withRevokedTokenRepository($this->createStub(RevokedTokenRepository::class));
        $jwt = JWT::encode(['iss' => GoogleOauth::IIS, 'email' => self::EMAIL, 'id' => 1], 'unused');

        $this->assertSame($emailUser, $service->getUserFromJwt($jwt));
    }
}
