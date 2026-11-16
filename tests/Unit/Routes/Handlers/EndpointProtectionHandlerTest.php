<?php

namespace SimpleJwtLoginTests\Unit\Routes\Handlers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Routes\Handlers\EndpointProtectionHandler;
use SimpleJWTLogin\Services\RouteService;
use WP_REST_Request;
use WP_REST_Response;

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
        $wordPressData->expects($this->once())
            ->method('getAdminUrl')
            ->willReturn('http://test.com/wp-admin/');

        $jwtSettings = new SimpleJWTLoginSettings($wordPressData);
        $serverHelper = new ServerHelper([
            'HTTP_HOST'      => 'test.com',
            'REQUEST_URI'    => '/wp-json/wp/v2/posts',
            'REQUEST_METHOD' => 'GET',
        ]);
        $routeService = (new RouteService())
            ->withSettings($jwtSettings)
            ->withServerHelper($serverHelper);

        $handler = (new EndpointProtectionHandler($routeService, $jwtSettings, $serverHelper, []))
            ->withRevokedTokenRepository($this->createStub(RevokedTokenRepository::class));
        $request = new WP_REST_Request('GET', '/wp/v2/posts');

        $this->assertNull($handler(null, null, $request));
        $this->assertNull($handler(null, null, $request));
    }

    /**
     * /batch/v1 sends every sub-request through rest_pre_dispatch with the same handler.
     * Access granted to the outer batch request must not open its sub-requests.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function batchSubRequestProvider()
    {
        return [
            'protected route inside a public batch' => [
                ProtectEndpointSettings::REQUEST_METHOD_ALL,
                'DELETE',
                '/wp/v2/posts/1',
            ],
            'sub-request judged by its own method, not the outer POST' => [
                ProtectEndpointSettings::REQUEST_METHOD_DELETE,
                'DELETE',
                '/wp/v2/posts/1',
            ],
        ];
    }

    #[DataProvider('batchSubRequestProvider')]
    public function testBatchSubRequestsAreCheckedOneByOne($ruleMethod, $subMethod, $subRoute)
    {
        if (!defined('REST_REQUEST')) {
            define('REST_REQUEST', true);
        }

        $handler = $this->buildHandler(
            [
                'enabled'        => true,
                'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                'rules_url'      => ['/wp/v2/posts'],
                'rules_method'   => [$ruleMethod],
                'rules_match'    => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PROTECTED],
                'rules_roles'    => [''],
            ],
            'POST'
        );

        $this->assertNull($handler(null, null, new WP_REST_Request('POST', '/batch/v1')));
        $this->assertInstanceOf(
            WP_REST_Response::class,
            $handler(null, null, new WP_REST_Request($subMethod, $subRoute))
        );
    }

    /**
     * @param array $protectSettings
     * @param string $outerMethod
     * @return EndpointProtectionHandler
     */
    private function buildHandler($protectSettings, $outerMethod)
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode([ProtectEndpointSettings::PROPERTY_GROUP => $protectSettings]));
        $wordPressData->method('isUserLoggedIn')->willReturn(false);
        $wordPressData->method('getSiteUrl')->willReturn('http://test.com');
        $wordPressData->method('getAdminUrl')->willReturn('http://test.com/wp-admin/');

        $jwtSettings = new SimpleJWTLoginSettings($wordPressData);
        $serverHelper = new ServerHelper([
            'HTTP_HOST'      => 'test.com',
            'REQUEST_URI'    => '/wp-json/batch/v1',
            'REQUEST_METHOD' => $outerMethod,
        ]);
        $routeService = (new RouteService())
            ->withSettings($jwtSettings)
            ->withServerHelper($serverHelper);

        return (new EndpointProtectionHandler($routeService, $jwtSettings, $serverHelper, []))
            ->withRevokedTokenRepository($this->createStub(RevokedTokenRepository::class));
    }

    /**
     * The route comes from the request WordPress dispatches, not from the URL:
     * /index.php/wp-json/wp/v2/users reaches /wp/v2/users too.
     */
    public function testUsesTheDispatchedRoute()
    {
        if (!defined('REST_REQUEST')) {
            define('REST_REQUEST', true);
        }

        $wordPressData = $this->createStub(WordPressDataInterface::class);
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
        $wordPressData->method('getSiteUrl')->willReturn('http://test.com');
        $wordPressData->method('getAdminUrl')->willReturn('http://test.com/wp-admin/');

        $jwtSettings = new SimpleJWTLoginSettings($wordPressData);
        $serverHelper = new ServerHelper([
            'HTTP_HOST'      => 'test.com',
            'REQUEST_URI'    => '/index.php/wp-json/wp/v2/users',
            'REQUEST_METHOD' => 'GET',
        ]);
        $routeService = (new RouteService())
            ->withSettings($jwtSettings)
            ->withServerHelper($serverHelper);

        $handler = (new EndpointProtectionHandler($routeService, $jwtSettings, $serverHelper, []))
            ->withRevokedTokenRepository($this->createStub(RevokedTokenRepository::class));

        $this->assertInstanceOf(
            WP_REST_Response::class,
            $handler(null, null, new WP_REST_Request('GET', '/wp/v2/users'))
        );
    }
}
