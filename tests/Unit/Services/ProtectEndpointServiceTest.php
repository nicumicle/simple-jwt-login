<?php

namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Services\ProtectEndpointService;
use SimpleJWTLogin\Services\RouteService;
use stdClass;

class ProtectEndpointServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressData
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();

        $this->wordPressData = $this->createStub(WordPressRepository::class);
        $this->wordPressData->method('getSiteUrl')
            ->willReturn('http://test.com');
        $this->wordPressData->method('getAdminUrl')
            ->willReturn('http://test.com/wp-admin/');
    }


    #[DataProvider('accessProvider')]
    /**
     * @param bool $expectedResult
     * @param string $requestMethod
     * @param string $currentUrl
     * @param string $documentRoot
     * @param array $request
     * @param array $settings
     */
    public function testHasAccess($expectedResult, $requestMethod, $currentUrl, $documentRoot, $request, $settings)
    {
        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                ProtectEndpointSettings::PROPERTY_GROUP => $settings
            ]));
        $this->wordPressData->method('isUserLoggedIn')
            ->willReturn(false);

        $routeServiceMock = $this->createStub(RouteService::class);
        $routeServiceMock->method('getUserFromJwt')
            ->willThrowException(new Exception());

        $routeService = (new RouteService())
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressData));
        $service = (new ProtectEndpointService())
            ->withRequest($request)
            ->withRequestMethod($requestMethod)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withRouteService($routeService)
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressData
                )
            )
            ->withSession([]);

        $result = $service->hasAccess($currentUrl, $documentRoot);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array[]
     */
    public static function accessProvider()
    {
        return [
            'test-not-enabled' => [
                'expectedResult' => true,
                'requestMethod' => 'GET',
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
                'requestMethod' => 'GET',
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
            'test-enabled-all-endpoints-with-method' => [
                'expectedResult' => true,
                'requestMethod' => 'GET',
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
                    ],
                    'whitelist_method' => [
                        'GET',
                    ]
                ]
            ],
            'test-enabled-all-endpoints-with-no-whitelist' => [
                'expectedResult' => false,
                'requestMethod' => 'GET',
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
                'requestMethod' => 'GET',
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
            'test-enabled-specific-endpoints-with-method' => [
                'expectedResult' => false,
                'requestMethod' => 'GET',
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
                    ],
                    'protect_method' => [
                        'GET',
                    ]
                ]
            ],
            'test-enabled-specific-endpoints-with-method-all' => [
                'expectedResult' => false,
                'requestMethod' => 'GET',
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
                    ],
                    'protect_method' => [
                        'ALL',
                    ]
                ]
            ],
            'test-enabled-specific-endpoints-with-different-method' => [
                'expectedResult' => true,
                'requestMethod' => 'GET',
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
                    ],
                    'protect_method' => [
                        'POST',
                    ]
                ]
            ],
            'test-enabled-specific-endpoints-2' => [
                'expectedResult' => false,
                'requestMethod' => 'GET',
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
                'requestMethod' => 'GET',
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
            'test_allow_all_with_no_matching_rule' => [
                'expectedResult' => true,
                'requestMethod' => 'GET',
                'currentUrl' => '/wp-json/wp/v2/other',
                'documentRoot' => '/var/www/html',
                'request' => [
                    'rest_route' => '/wp/v2/other'
                ],
                'settings' => [
                    'enabled'        => true,
                    'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                    'rules_url'      => ['/wp/v2/posts'],
                    'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PROTECTED],
                ]
            ],
            'test_empty_endpoint' => [
                'expectedResult' => false,
                'requestMethod' => 'GET',
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
            ->withRequestMethod('GET')
            ->withServerHelper(new ServerHelper([]))
            ->withRouteService($routeService)
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressData
                )
            )
            ->withSession([]);

        $result = $service->hasAccess('/wp-json/v2/posts', '/var/www/html');
        $this->assertFalse($result);
    }

    public function testApiKeyGrantsAccessWhenJwtMissing()
    {
        $rawKey = 'test-api-key-value';
        $keyHash = hash('sha256', $rawKey);

        $keyRow = (object) [
            'id'          => 1,
            'user_id'     => 42,
            'permissions' => json_encode(['read']),
            'key_hash'    => $keyHash,
        ];

        $apiKeyRepo = $this->createStub(ApiKeyRepositoryInterface::class);
        $apiKeyRepo->method('getByKeyHash')->willReturn($keyRow);
        $apiKeyRepo->method('touchLastUsed')->willReturn(null);

        $settings = [
            'decryption_key' => 'test',
            ProtectEndpointSettings::PROPERTY_GROUP => [
                'enabled' => true,
                'action'  => ProtectEndpointSettings::ALL_ENDPOINTS,
                'whitelist' => [],
            ],
            'api_keys' => [
                'enabled'     => true,
                'header_name' => 'x-api-key',
            ],
        ];

        $userStub = new stdClass();
        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressData->method('isUserLoggedIn')
            ->willReturn(false);
        $this->wordPressData->method('getUserDetailsById')
            ->willReturn($userStub);
        $this->wordPressData->method('setCurrentUser')
            ->willReturn(null);

        $routeService = (new RouteService())
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressData))
            ->withRequest([])
            ->withSession([])
            ->withCookies([]);

        $serverHelper = new ServerHelper([
            'HTTP_X_API_KEY' => $rawKey,
            'REQUEST_METHOD' => 'GET',
        ]);

        $service = (new ProtectEndpointService())
            ->withRequest([])
            ->withCookies([])
            ->withRequestMethod('GET')
            ->withServerHelper($serverHelper)
            ->withRouteService($routeService)
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressData))
            ->withSession([])
            ->withApiKeyRepository($apiKeyRepo);

        $result = $service->hasAccess('/wp-json/wp/v2/posts', '/var/www/html');
        $this->assertTrue($result);
    }

    public function testApiKeyAuthSkippedWhenRepositoryNotSet()
    {
        $settings = [
            'decryption_key' => 'test',
            ProtectEndpointSettings::PROPERTY_GROUP => [
                'enabled' => true,
                'action'  => ProtectEndpointSettings::ALL_ENDPOINTS,
                'whitelist' => [],
            ],
            'api_keys' => [
                'enabled'     => true,
                'header_name' => 'x-api-key',
            ],
        ];

        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressData->method('isUserLoggedIn')
            ->willReturn(false);

        $routeService = (new RouteService())
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressData))
            ->withRequest([])
            ->withSession([])
            ->withCookies([]);

        $serverHelper = new ServerHelper([
            'HTTP_X_API_KEY' => 'some-key',
            'REQUEST_METHOD' => 'GET',
        ]);

        $service = (new ProtectEndpointService())
            ->withRequest([])
            ->withCookies([])
            ->withRequestMethod('GET')
            ->withServerHelper($serverHelper)
            ->withRouteService($routeService)
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressData))
            ->withSession([]);

        $result = $service->hasAccess('/wp-json/wp/v2/posts', '/var/www/html');
        $this->assertFalse($result);
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
            ->withRequestMethod('GET')
            ->withServerHelper(new ServerHelper([]))
            ->withRouteService($routeService)
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressData
                )
            )
            ->withSession([]);

        $result = $service->hasAccess('/wp-json/simple-jwt-login/v1/auth', '/var/www/html');
        $this->assertTrue($result);
    }

    public function testRoleCheckPassesWhenUserHasRequiredRole()
    {
        $user = new stdClass();
        $user->ID = 5;
        $user->roles = ['editor'];

        $settings = [
            ProtectEndpointSettings::PROPERTY_GROUP => [
                'enabled'        => true,
                'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                'rules_url'      => ['/wp/v2/posts'],
                'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES],
                'rules_roles'    => ['administrator, editor'],
            ],
        ];

        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressData->method('isUserLoggedIn')->willReturn(false);
        $this->wordPressData->method('getUserProperty')->willReturn(5);
        $this->wordPressData->method('getUserMeta')->willReturn([]);
        $this->wordPressData->method('getUserRoles')->willReturn(['editor']);
        $this->wordPressData->method('setCurrentUser')->willReturn(null);

        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressData);

        $routeServiceMock = $this->createStub(RouteService::class);
        $routeServiceMock->method('getUserFromJwt')->willReturn($user);

        $service = (new ProtectEndpointService())
            ->withRequest(['rest_route' => '/wp/v2/posts', 'JWT' => 'fake-jwt'])
            ->withCookies([])
            ->withRequestMethod('GET')
            ->withServerHelper(new ServerHelper(['HTTP_AUTHORIZATION' => 'Bearer fake-jwt']))
            ->withRouteService($routeServiceMock)
            ->withSettings($jwtSettings)
            ->withSession([]);

        $result = $service->hasAccess('/wp-json/wp/v2/posts', '/var/www/html');
        $this->assertTrue($result);
    }

    public function testRoleCheckThrowsWhenUserLacksRequiredRole()
    {
        $user = new stdClass();
        $user->ID = 7;
        $user->roles = ['subscriber'];

        $settings = [
            ProtectEndpointSettings::PROPERTY_GROUP => [
                'enabled'        => true,
                'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                'rules_url'      => ['/wp/v2/posts'],
                'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES],
                'rules_roles'    => ['administrator'],
            ],
        ];

        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressData->method('isUserLoggedIn')->willReturn(false);
        $this->wordPressData->method('getUserProperty')->willReturn(7);
        $this->wordPressData->method('getUserMeta')->willReturn([]);
        $this->wordPressData->method('getUserRoles')->willReturn(['subscriber']);
        $this->wordPressData->method('setCurrentUser')->willReturn(null);

        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressData);

        $routeServiceMock = $this->createStub(RouteService::class);
        $routeServiceMock->method('getUserFromJwt')->willReturn($user);

        $service = (new ProtectEndpointService())
            ->withRequest(['rest_route' => '/wp/v2/posts', 'JWT' => 'fake-jwt'])
            ->withCookies([])
            ->withRequestMethod('GET')
            ->withServerHelper(new ServerHelper(['HTTP_AUTHORIZATION' => 'Bearer fake-jwt']))
            ->withRouteService($routeServiceMock)
            ->withSettings($jwtSettings)
            ->withSession([]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You do not have the required role to access this endpoint.');
        $service->hasAccess('/wp-json/wp/v2/posts', '/var/www/html');
    }

    public function testNoRolesRequiredAllowsAnyAuthenticatedUser()
    {
        $user = new stdClass();
        $user->ID = 9;
        $user->roles = ['subscriber'];

        $settings = [
            ProtectEndpointSettings::PROPERTY_GROUP => [
                'enabled'        => true,
                'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                'rules_url'      => ['/wp/v2/posts'],
                'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PROTECTED],
                'rules_roles'    => [''],
            ],
        ];

        $this->wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressData->method('isUserLoggedIn')->willReturn(false);
        $this->wordPressData->method('getUserProperty')->willReturn(9);
        $this->wordPressData->method('getUserMeta')->willReturn([]);
        $this->wordPressData->method('getUserRoles')->willReturn(['subscriber']);
        $this->wordPressData->method('setCurrentUser')->willReturn(null);

        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressData);

        $routeServiceMock = $this->createStub(RouteService::class);
        $routeServiceMock->method('getUserFromJwt')->willReturn($user);

        $service = (new ProtectEndpointService())
            ->withRequest(['rest_route' => '/wp/v2/posts', 'JWT' => 'fake-jwt'])
            ->withCookies([])
            ->withRequestMethod('GET')
            ->withServerHelper(new ServerHelper(['HTTP_AUTHORIZATION' => 'Bearer fake-jwt']))
            ->withRouteService($routeServiceMock)
            ->withSettings($jwtSettings)
            ->withSession([]);

        $result = $service->hasAccess('/wp-json/wp/v2/posts', '/var/www/html');
        $this->assertTrue($result);
    }
}
