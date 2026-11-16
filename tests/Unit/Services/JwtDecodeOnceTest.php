<?php

namespace SimpleJwtLoginTests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Jwt\JwtWrapper;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\RouteService;
use stdClass;

/**
 * Every authenticated request validates its JWT: the header and payload are split and
 * decoded once, not once per check (rule lookup, signature check, revocation check).
 */
class JwtDecodeOnceTest extends TestCase
{
    public function testJwtPartsAreDecodedOncePerJwt()
    {
        $user = new stdClass();
        $user->ID = 13;
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('getOptionFromDatabase')->willReturn(json_encode([
            'decryption_key'         => '123',
            'jwt_login_by'           => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'jwt_login_by_parameter' => 'id',
        ]));
        $wordPressData->method('getUserDetailsById')->willReturn($user);
        $wordPressData->method('isInstanceOfuser')->willReturn(true);
        $wordPressData->method('getUserProperty')->willReturn(13);

        $wrapper = new class extends JwtWrapper {
            /** @var integer */
            public $extractCalls = 0;

            public function extractDataFromJwt($jwt)
            {
                $this->extractCalls++;

                return parent::extractDataFromJwt($jwt);
            }
        };
        $service = new class ($wrapper) extends RouteService {
            public function __construct($wrapper)
            {
                $this->jwtWrapper = $wrapper;
            }
        };
        $service->withSettings(new SimpleJWTLoginSettings($wordPressData))
            ->withServerHelper(new ServerHelper([]))
            ->withRevokedTokenRepository($this->createStub(RevokedTokenRepository::class));

        $this->assertSame($user, $service->getUserFromJwt(JWT::encode(['id' => 13], '123')));
        $this->assertSame(1, $wrapper->extractCalls);
    }
}
