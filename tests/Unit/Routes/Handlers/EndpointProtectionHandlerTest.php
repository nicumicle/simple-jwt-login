<?php

namespace SimpleJwtLoginTests\Unit\Routes\Handlers;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Routes\Handlers\EndpointProtectionHandler;
use SimpleJWTLogin\Services\RouteService;

class EndpointProtectionHandlerTest extends TestCase
{
    /**
     * "rest_pre_dispatch" can fire several times per REST request (e.g. _embed
     * sub-requests). Once access is granted for the current request, later
     * calls must not re-run the access check.
     */
    public function testAccessIsCheckedOncePerRequest()
    {
        if (!defined('REST_REQUEST')) {
            define('REST_REQUEST', true);
        }

        $wordPressData = $this->createMock(WordPressDataInterface::class);
        $wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled'        => true,
                    'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                    'rules_url'      => ['/wp/v2/users'],
                    'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PROTECTED],
                    'rules_roles'    => [''],
                ],
            ]));
        $wordPressData->method('isUserLoggedIn')->willReturn(false);
        $wordPressData->method('getSiteUrl')->willReturn('http://test.com');
        $wordPressData->method('getAdminUrl')->willReturn('http://test.com/wp-admin/');
        $wordPressData->expects($this->once())
            ->method('parseUrl')
            ->willReturn(['path' => '/wp-json/wp/v2/posts']);

        $jwtSettings = new SimpleJWTLoginSettings($wordPressData);
        $serverHelper = new ServerHelper([
            'HTTP_HOST'      => 'test.com',
            'REQUEST_URI'    => '/wp-json/wp/v2/posts',
            'REQUEST_METHOD' => 'GET',
        ]);
        $routeService = (new RouteService())
            ->withSettings($jwtSettings)
            ->withServerHelper($serverHelper);

        $handler = (new EndpointProtectionHandler($routeService, $jwtSettings, $serverHelper, [], '/var/www/html'))
            ->withRevokedTokenRepository($this->createStub(RevokedTokenRepository::class));

        $this->assertNull($handler(null));
        $this->assertNull($handler(null));
    }
}
