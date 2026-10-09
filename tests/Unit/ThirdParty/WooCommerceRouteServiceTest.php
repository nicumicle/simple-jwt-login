<?php

namespace SimpleJwtLoginTests\Unit\ThirdParty;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use wpdb;

class WooCommerceRouteServiceTest extends TestCase
{
    protected function setUp(): void
    {
        global $wpdb;
        $wpdb = new wpdb();
        $_REQUEST = [];
        $_COOKIE = [];

        require_once __DIR__ . '/../../../simple-jwt-login/3rd-party/woocommerce.php';
    }

    /**
     * The WooCommerce Store API integration resolves the user from the JWT, which
     * runs the revoked-token check. The route service built here must have the
     * revoked token repository wired, otherwise every WooCommerce REST request
     * carrying a Bearer JWT ends in a fatal error.
     */
    public function testGetUserFromJwtHasRevokedTokenRepositoryWired()
    {
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $settings = [
            'decryption_key'         => '123',
            'jwt_login_by'           => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter' => 'user',
        ];
        $wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $wordPressDataMock
            ->method('isInstanceOfuser')
            ->willReturn(true);
        $wordPressDataMock
            ->method('getUserDetailsByEmail')
            ->willReturn('123');
        $wordPressDataMock
            ->method('getUserProperty')
            ->willReturn(2);

        $routeService = simpleJwtLoginWooCommerceRouteService(
            new SimpleJWTLoginSettings($wordPressDataMock),
            new RevokedTokenRepository($GLOBALS['wpdb'])
        );

        $jwt = JWT::encode(['user' => 'test'], '123');

        $this->assertSame('123', $routeService->getUserFromJwt($jwt));
    }
}
