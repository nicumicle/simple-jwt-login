<?php

namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\RegisterUserService;

class RegisterUserServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this
            ->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
        $this->wordPressDataMock->method('sanitizeTextField')
            ->willReturnCallback(
                function ($parameter) {
                    return $parameter;
                }
            );
    }

    /**
     * @dataProvider validationProvider
     * @param mixed $request
     * @param array $settings
     * @param string $exceptionMessage
     * @throws Exception
     */
    public function testValidation($request, $settings, $exceptionMessage)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));

        $isEmail = isset($request['email']) && $request['email'] !== '---';
        $this->wordPressDataMock->method('isEmail')
            ->willReturn($isEmail);

        $service = (new RegisterUserService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'HTTP_CLIENT_IP' => '127.0.0.1'
            ]))
            ->withSession([])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        $service->makeAction();
    }

    public static function validationProvider()
    {
        return[
            'test_empty_settings' => [
                'request' => [],
                'settings' => [],
                'exception' => 'Register is not allowed.',
            ],
            'test_register_is_not_allowed' => [
                'request' => [],
                'settings' => [
                    'allow_register' => false,
                ],
                'exception' => 'Register is not allowed.',
            ],
            'test_default_auth_key_is_required' => [
                'request' => [],
                'settings' => [
                    'allow_register' => true,
                ],
                'exception' => 'Invalid Auth Code ( AUTH_KEY ) provided.',
            ],
            'test_with_invalid_auth_code' => [
                'request' => [
                    'AUTH_KEY' => 1233
                ],
                'settings' => [
                    'allow_register' => true,
                    'auth_codes' => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'exception' => 'Invalid Auth Code ( AUTH_KEY ) provided.',
            ],
            'test_without_email_and_password' => [
                'request' => [
                    'AUTH_KEY' => 123
                ],
                'settings' => [
                    'allow_register' => true,
                    'auth_codes' => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'exception' => 'Missing email or password.',
            ],
            'test_only_with_email' => [
                'request' => [
                    'AUTH_KEY' => 123,
                    'email' => 'test@test.com',
                ],
                'settings' => [
                    'allow_register' => true,
                    'auth_codes' => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'exception' => 'Missing email or password.',
            ],
            'test_only_with_password' => [
                'request' => [
                    'AUTH_KEY' => 123,
                    'password' => 'test',
                ],
                'settings' => [
                    'allow_register' => true,
                    'auth_codes' => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'exception' => 'Missing email or password.',
            ],
            'test_with_invalid_email' => [
                'request' => [
                    'AUTH_KEY' => 123,
                    'email' => '---',
                    'password' => 'test',
                ],
                'settings' => [
                    'allow_register' => true,
                    'auth_codes' => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'exception' => 'Invalid email address.',
            ],
            'test_register_domain' => [
                'request' => [
                    'AUTH_KEY' => 123,
                    'email' => 'test@simplejwtlogin.com',
                    'password' => 'test',
                ],
                'settings' => [
                    'allow_register' => true,
                    'auth_codes' => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ],
                    'register_domain' => 'google.com,test.com',
                ],
                'exception' => 'This website does not allows users from this domain.',
            ],
            'test_register_ip' => [
                'request' => [
                    'AUTH_KEY' => 123,
                    'email' => 'test@test.com',
                    'password' => 'test',
                ],
                'settings' => [
                    'allow_register' => true,
                    'auth_codes' => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ],
                    'register_ip' => '127.0.1.1',
                    'register_domain' => 'google.com,test.com',
                ],
                'exception' => 'This IP[127.0.0.1] is not allowed to register users.',
            ],

        ];
    }

    public function testRegisteredUserAlreadyExists()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User already exists.');
        $this->expectExceptionCode(ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS);

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_register' => true,
                'require_register_auth' => false,
            ]));

        $this->wordPressDataMock->method('isEmail')
            ->willReturn(true);
        $this->wordPressDataMock->method('checkUserExistsByUsernameAndEmail')
            ->willReturn(true);

        $service = (new RegisterUserService())
            ->withRequest([
                'email' => 'test@test.com',
                'password' => 123,
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'HTTP_CLIENT_IP' => '127.0.0.1'
            ]))
            ->withSession([])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        $service->makeAction();
    }

    public function testWithRandomPasswordAndRedirectAfterRegister()
    {
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_register' => true,
                'require_register_auth' => false,
                'random_password' => true,
                'auth_codes' => [
                    [
                        'code'            => '123',
                        'role'            => 'admin',
                        'expiration_date' => '',
                    ]
                ],
                'allowed_user_meta' => 'test,test2',
                'enabled_hooks' => [
                    SimpleJWTLoginHooks::REGISTER_ACTION_NAME,
                    SimpleJWTLoginHooks::LOGIN_ACTION_NAME
                ],
                'allow_autologin' => true,
                'register_force_login' => true,
            ]));

        $this->wordPressDataMock->method('isEmail')
            ->willReturn(true);
        $this->wordPressDataMock->method('checkUserExistsByUsernameAndEmail')
            ->willReturn(false);

        $this->wordPressDataMock->method('createUser')
            ->willReturn(true);
        $this->wordPressDataMock->method('getUserIdFromUser')
            ->willReturn(1);

        $this->wordPressDataMock->method('getAdminUrl')
            ->willReturn('https://admin.com');
        $service = (new RegisterUserService())
            ->withRequest([
                'email' => 'test@test.com',
                'password' => 123,
                'user_meta' => json_encode(['test' => 123]),
                'AUTH_KEY' => '123',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'HTTP_CLIENT_IP' => '127.0.0.1'
            ]))
            ->withSession([])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        $result = $service->makeAction();
        $this->assertSame(null, $result);
    }

    public function testRegisterSuccessWithJwtFromAuth()
    {
        $this->wordPressDataMock->method('isEmail')
            ->willReturn(true);
        $this->wordPressDataMock->method('checkUserExistsByUsernameAndEmail')
            ->willReturn(false);
        $this->wordPressDataMock->method('getSiteUrl')
            ->willReturn("http://test.com");

        $authSettings = new AuthenticationSettings();
        $authSettings->withWordPressData($this->wordPressDataMock);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => true,
                'allow_register' => true,
                'require_register_auth' => false,
                'random_password' => false,
                'decryption_key' => '123',
                'register_jwt' => true,
                'jwt_payload' => $authSettings->getJwtPayloadParameters(),
            ]));


        $this->wordPressDataMock->method('createUser')
            ->willReturn([]);
        $this->wordPressDataMock->method('getUserIdFromUser')
            ->willReturn(1);
        $this->wordPressDataMock->method('wordpressUserToArray')
            ->willReturn([
                'user_pass' => '123',
                'email' => 'user@test.com',
                'some_param' => 'test',
            ]);
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($result) {
                return $result;
            });

        $service = (new RegisterUserService())
            ->withRequest([
                'email' => 'test@test.com',
                'password' => 123,
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'HTTP_CLIENT_IP' => '127.0.0.1'
            ]))
            ->withSession([])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        /** @var array $result */
        $result = $service->makeAction();

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('jwt', $result);
    }

    public function testRegisterWithoutAuthPayload()
    {
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => false,
                'allow_register' => true,
                'require_register_auth' => false,
                'random_password' => false,
                'decryption_key' => '123',
                'register_jwt' => true,
            ]));

        $this->wordPressDataMock->method('isEmail')
            ->willReturn(true);
        $this->wordPressDataMock->method('checkUserExistsByUsernameAndEmail')
            ->willReturn(false);
        $this->wordPressDataMock->method('getSiteUrl')
            ->willReturn("http://test.com");
        $this->wordPressDataMock->method('createUser')
            ->willReturn([]);
        $this->wordPressDataMock->method('getUserIdFromUser')
            ->willReturn(1);
        $this->wordPressDataMock->method('getUserProperty')
            ->willReturn('test');
        $this->wordPressDataMock->method('wordpressUserToArray')
            ->willReturn([
                'user_pass' => '123',
                'email' => 'user@test.com',
                'some_param' => 'test',
            ]);
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($result) {
                return $result;
            });

        $service = (new RegisterUserService())
            ->withRequest([
                'email' => 'test@test.com',
                'password' => 123,
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'HTTP_CLIENT_IP' => '127.0.0.1'
            ]))
            ->withSession([])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        /** @var array $result */
        $result = $service->makeAction();

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('jwt', $result);
    }
}
