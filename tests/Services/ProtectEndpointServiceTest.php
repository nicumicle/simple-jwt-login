<?php

namespace SimpleJwtLoginTests\Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;
use SimpleJWTLogin\Services\ProtectEndpointService;
use SimpleJWTLogin\Services\RouteService;

class ProtectEndpointServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressData
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();

        $this->wordPressData = $this->getMockBuilder(WordPressData::class)
            ->getMock();
        $this->wordPressData->method('getSiteUrl')
            ->willReturn('http://test.com');
        $this->wordPressData->method('getAdminUrl')
            ->willReturn('http://test.com/wp-admin/');
    }

    /**
     * @param bool $expectedResult
     * @param string $currentUrl
     * @param string $documentRoot
     * @param array $request
     * @param array $settings
     *
     * @dataProvider accessProvider
     */
    public function testHasAccess($expectedResult, $currentUrl, $documentRoot, $request, $settings)
    {
        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                ProtectEndpointSettings::PROPERTY_GROUP => $settings
            ]));
        $this->wordPressData->method('isUserLoggedIn')
            ->willReturn(false);

        $routeServiceMock = $this->getMockBuilder(RouteService::class)
            ->getMock();
        $routeServiceMock->method('getUserFromJwt')
            ->willThrowException(new \Exception());

        $routeService = (new RouteService())
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressData));
        $service = (new ProtectEndpointService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withRouteService($routeService)
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressData
                )
            )
            ->withSession([]);

        $result = $service->hasAccess($currentUrl, $documentRoot, $request);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array[]
     */
    public function accessProvider()
    {
        return [
            'test-not-enabled' => [
                'expectedResult' => true,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => 'test/'
                ],
                'settings' => [
                    'enabled' => false,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'whitelist' => [
                    ],
                    'protect' => [
                        '/wp-json/v2/posts'
                    ],
                ]
            ],
            'test-enabled-all-endpoints' => [
                'expectedResult' => true,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/v2/posts/'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'whitelist' => [
                        '/wp-json/v2/posts'
                    ]
                ]
            ],
            'test-enabled-all-endpoints-with-no-whitelist' => [
                'expectedResult' => false,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/v2/posts/'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'whitelist' => [
                    ]
                ]
            ],
            'test-enabled-specific-endpoints' => [
                'expectedResult' => false,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/wp/v2/posts/'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => [
                        '/wp-json/wp/v2/posts'
                    ]
                ]
            ],
            'test-enabled-specific-endpoints-2' => [
                'expectedResult' => false,
                'currentUrl' => '/wp-json/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/wp/v2/posts/'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => [
                        '/wp/v2/posts',
                    ]
                ]
            ],
            'test-enabled-all-endpoints_on_wp_admin' => [
                'expectedResult' => true,
                'currentUrl' => '/wp-admin/something',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/wp-admin/something'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'whitelist' => [
                        '/wp-json/v2/posts',
                        '',
                    ]
                ]
            ],
            'test_invalid_action' => [
                'expectedResult' => false,
                'currentUrl' => '/wp-json/wp/v2/posts',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/wp-json/wp/v2/posts'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => -1, //invalid action
                    'whitelist' => [
                        'wp-json/wp/v2/posts',
                        '',
                    ]
                ]
            ],
            'test_empty_endpoint' => [
                'expectedResult' => false,
                'currentUrl' => 'wp-json',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => 'wp-json'
                ],
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'whitelist' => [
                        'wp-json/v2/posts',
                        '',
                    ]
                ]
            ],
        ];
    }

    public function testCallProtectedEndpointWithInvalidJWT()
    {
        $settings = [
            'decryption_key' => 'test',
            ProtectEndpointSettings::PROPERTY_GROUP => [
                'enabled' => true,
                'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                'whitelist' => [
                ],
                'protect' => [
                    '/wp-json/v2/posts'
                ],
            ]
        ];

        $request = [
            'rest_route' => '/wp/v2/posts/',
            'JWT' => JWT::encode(['user' => 1], 'test', 'HS256'),
        ];

        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressData->method('isInstanceOfuser')
            ->willReturn(false);

        $routeService = (new RouteService())
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressData))
            ->withRequest($request)
            ->withSession([])
            ->withCookies([]);

        $service = (new ProtectEndpointService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withRouteService($routeService)
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressData
                )
            )
            ->withSession([]);

        $result = $service->hasAccess('/wp-json/v2/posts', '/var/www/html', $request);
        $this->assertSame(false, $result);
    }

    public function testSimpleJwtLoginEndpointsAreNotProtected()
    {
        $settings = [
            'decryption_key' => 'test',
            ProtectEndpointSettings::PROPERTY_GROUP => [
                'enabled' => true,
                'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                'whitelist' => [
                ],
                'protect' => [
                ],
            ]
        ];

        $request = [
            'rest_route' => '/simple-jwt-login/v1/auth',
            'JWT' => JWT::encode(['user' => 1], 'test', 'HS256'),
        ];

        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressData->method('isInstanceOfuser')
            ->willReturn(true);

        $routeService = (new RouteService())
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressData))
            ->withRequest($request)
            ->withSession([])
            ->withCookies([]);

        $service = (new ProtectEndpointService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withRouteService($routeService)
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressData
                )
            )
            ->withSession([]);

        $result = $service->hasAccess('/wp-json/simple-jwt-login/v1/auth', '/var/www/html', $request);
        $this->assertTrue($result);
    }
}
