<?php

declare(strict_types=1);

namespace SimpleJwtLoginTests\Feature\ProtectEndpoints;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJwtLoginTests\Feature\TestBase;

class MethodsTest extends TestBase
{
    const JWT_SECRET_KEY = 'test';

    /**
     * @var $defaultOptions <string, mixed>
     */
    private array $defaultOptions = [
        'allow_authentication' => true,
        'jwt_payload' => ["email","exp","id","iss","site","username"],
        'jwt_auth_ttl' => 60,
        'jwt_auth_refresh_ttl' => "20160",
        'auth_ip' => "",
        'auth_requires_auth_code' => false,
        'auth_password_base64' => false,
        'jwt_auth_iss' => "tests",
        'decryption_key' => self::JWT_SECRET_KEY,
        // Register user
        'allow_register' => true,
        'new_user_profile' => "subscriber",
        'register_ip' => "",
        'register_domain' => "",
        'require_register_auth' => false,
        // Delete user
        'allow_delete' => true,
        'require_delete_auth' => false,
        'delete_ip' => "",
        'delete_user_by' => 0,
        'jwt_delete_by_parameter' => "email",
        // Autologin: We need this for refresh token
        'jwt_login_by' => 0,
        'jwt_login_by_parameter' => "email",
        // Protect endpoints
        'protect_endpoints' => [],
    ];


    /**
     * @return array<string,array<string,mixed>>
     */
    public static function protectProvider(): array
    {
        return [
            'should be able to call protected endpoint with different method' => [
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => [
                        '/wp/v2/users'
                    ],
                    'protect_method' => [
                        'POST',
                    ],
                    'whitelist' => [],
                ],
                'useJWT' => false,
                'endpoint' => '/wp/v2/users',
                'method' => 'GET',
                'expectedStatusCode' => 200,
            ],
            'should not be able to call protected endpoint without JWT' => [
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => [
                        '/wp/v2/users'
                    ],
                    'protect_method' => [
                        'GET',
                    ],
                    'whitelist' => [],
                ],
                'useJWT' => false,
                'endpoint' => '/wp/v2/users',
                'method' => 'GET',
                'expectedStatusCode' => 403,
            ],
            'should be able to call protected endpoint with JWT' => [
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => [
                        '/wp/v2/users',
                    ],
                    'protect_method' => [
                        'GET',
                    ],
                    'whitelist' => [],
                ],
                'useJWT' => true,
                'endpoint' => '/wp/v2/users',
                'method' => 'GET',
                'expectedStatusCode' => 200,
            ],
            'should be able to call an exact protected endpoint with JWT' => [
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect' => [
                        '/wp/v2/users',
                        '/wp/v2/', // This was an issue before
                    ],
                    'protect_method' => [
                        'GET',
                        'ALL',
                    ],
                    'whitelist' => [],
                ],
                'useJWT' => true,
                'endpoint' => '/wp/v2/users',
                'method' => 'GET',
                'expectedStatusCode' => 200,
            ],
            'should be able to call an whitelisted endpoint without JWT' => [
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect' => [],
                    'protect_method' => [],
                    'whitelist' => [
                        '/wp/v2/users',
                        '/wp/v2/', // This was an issue before
                    ],
                    'whitelist_method' => [
                        'GET',
                        'ALL',
                    ]
                ],
                'useJWT' => false,
                'endpoint' => '/wp/v2/users',
                'method' => 'GET',
                'expectedStatusCode' => 200,
            ],
            'should be able to call an whitelisted endpoint with JWT' => [
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect' => [],
                    'protect_method' => [],
                    'whitelist' => [
                        '/wp/v2/users',
                        '/wp/v2/',
                    ],
                    'whitelist_method' => [
                        'GET',
                        'ALL',
                    ]
                ],
                'useJWT' => true,
                'endpoint' => '/wp/v2/users',
                'method' => 'GET',
                'expectedStatusCode' => 200,
            ],
            'should not be able to call an non-whitelisted endpoint without JWT' => [
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect' => [],
                    'protect_method' => [],
                    'whitelist' => [
                        '/wp/v2/users',
                        '/wp/v2/',
                    ],
                    'whitelist_method' => [
                        'GET',
                        'ALL',
                    ]
                ],
                'useJWT' => false,
                'endpoint' => '/wp/v2/posts',
                'method' => 'GET',
                'expectedStatusCode' => 403,
            ],
            'should able to call an non-whitelisted endpoint with JWT' => [
                'settings' => [
                    'enabled' => true,
                    'action' => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect' => [],
                    'protect_method' => [],
                    'whitelist' => [
                        '/wp/v2/users',
                        '/wp/v2/',
                    ],
                    'whitelist_method' => [
                        'GET',
                        'ALL',
                    ]
                ],
                'useJWT' => true,
                'endpoint' => '/wp/v2/posts',
                'method' => 'GET',
                'expectedStatusCode' => 200,
            ],
        ];
    }
   
    #[DataProvider('protectProvider')]
    #[TestDox("Validate Method call on Protect Endpoints")]
    /**
     * @param array $settings
     * @param bool $useJWT
     * @param string $endpoint
     * @param string $method
     * @param int $expectedStatusCode
     * @return void
     */
    public function testEndpoints($settings, $useJWT, $endpoint, $method, $expectedStatusCode): void
    {
        // Init WP options
        $options = $this->defaultOptions;
        $options['protect_endpoints'] = $settings;
        self::updateSimpleJWTOption($options);

        // Init request
        $url = sprintf("%s?rest_route=%s", self::API_URL, $endpoint);
        if ($useJWT) {
             // Register random user
            list ($email, $password, $statusCode) = $this->registerRandomUser();
            $this->assertSame(200, $statusCode, 'Unable to register user');
            // Get a new JWT
            $jwt = $this->getJWTForUser($email, $password);
            $url .= '&JWT=' . $jwt;
        }
        // Call
        $result = $this->client->request($method, $url);
        // Validate
        $this->assertSame($expectedStatusCode, $result->getStatusCode());
    }
}
