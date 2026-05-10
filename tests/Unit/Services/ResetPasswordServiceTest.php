<?php

namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\ResetPasswordService;

class ResetPasswordServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this
            ->createStub(WordPressDataInterface::class);
    }

    #[DataProvider('sendUserPasswordProvider')]
    /**
     * @param mixed $settings
     * @param array $request
     * @param string $exceptionMessage
     *
     * @throws \Exception
     */
    public function testValidationSendUserPassword($settings, $request, $exceptionMessage)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $resetService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'POST']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $resetService->makeAction();
    }

    public static function sendUserPasswordProvider()
    {
        return [
            [
                'settings'  => [],
                'request'   => [],
                'exceptionMessage' => 'Reset Password is not allowed.'
            ],
            [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                ],
                'request'   => [],
                'exceptionMessage' => 'Invalid Auth Code ( AUTH_KEY ) provided.'
            ],
            [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                    'auth_codes'                        => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'request'   => [
                    'AUTH_KEY' => 123
                ],
                'exceptionMessage' => 'Missing email parameter.'
            ],
            [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                    'auth_codes'                        => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'request'   => [
                    'AUTH_KEY' => 123,
                    'email'    => 'userdoesnotexst@test.com'
                ],
                'exceptionMessage' => 'Wrong user.'
            ],
        ];
    }

    #[DataProvider('flowTypeProvider')]
    /**
     * @param int $flowType
     *
     * @throws Exception
     */
    public function testSendUserPasswordSuccess($flowType)
    {
        $request  = [
            'AUTH_KEY' => 123,
            'email'    => 'test@test.com',
            'code'     => '123'
        ];
        $settings = [
            'allow_reset_password'              => 1,
            'reset_password_requires_auth_code' => 1,
            'auth_codes'                        => [
                [
                    'code'            => 123,
                    'role'            => '',
                    'expiration_date' => '',
                ]
            ],
            'enabled_hooks' => [
                SimpleJWTLoginHooks::RESET_PASSWORD_CUSTOM_EMAIL_TEMPLATE,
            ],
            'jwt_reset_password_flow'           => $flowType,
        ];
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock
            ->method('getUserDetailsByEmail')
            ->willReturn(['User']);
        $this->wordPressDataMock
            ->method('triggerFilter')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);
        $resetService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'POST']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $result = $resetService->makeAction();
        $this->assertTrue($result);
    }

    public static function flowTypeProvider()
    {
        return [
            [
                ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB,
            ],
            [
                ResetPasswordSettings::FLOW_SEND_DEFAULT_WP_EMAIL,
            ],
            [
                ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL
            ]
        ];
    }

    #[DataProvider('changePasswordValidationProvider')]
    /**
     * @param array $settings
     * @param array $request
     * @param string $exceptionMessage
     *
     * @throws Exception
     */
    public function testValidationChangePassword($settings, $request, $exceptionMessage)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $resetService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'PUT']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $resetService->makeAction();
    }

    public static function changePasswordValidationProvider()
    {
        return [
            'empty_settings' => [
                'settings'  => [],
                'request'   => [],
                'exceptionMessage' => 'Reset Password is not allowed.'
            ],
            'empty_auth_key' => [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                ],
                'request'   => [],
                'exceptionMessage' => 'Invalid Auth Code ( AUTH_KEY ) provided.'
            ],
            'missing_email' => [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                    'auth_codes'                        => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'request'   => [
                    'AUTH_KEY' => 123
                ],
                'exceptionMessage' => 'Missing email parameter.'
            ],
            'missing_code' => [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                    'auth_codes'                        => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ],
                ],
                'request'   => [
                    'AUTH_KEY' => 123,
                    'email'    => 'email@email.com',
                    'new_password' => '123',
                ],
                'exceptionMessage' => 'Missing code parameter.'
            ],
            'missing_password' => [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                    'auth_codes'                        => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'request'   => [
                    'AUTH_KEY' => 123,
                    'email'    => 'email@email.com',
                    'code'     => '123',
                ],
                'exceptionMessage' => 'Missing new_password parameter.'
            ],
            'invalid_code' => [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                    'auth_codes'                        => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ]
                ],
                'request'   => [
                    'AUTH_KEY'     => 123,
                    'email'        => 'email@email.com',
                    'code'         => '123',
                    'new_password' => '123',
                ],
                'exceptionMessage' => 'Invalid code provided.'
            ],
            'jwt_with_invalid_email' => [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                    'auth_codes'                        => [
                        [
                            'code'            => 123,
                            'role'            => '',
                            'expiration_date' => '',
                        ]
                    ],
                    'reset_password_jwt' => 1,
                    'decryption_key' => 'test',
                    'jwt_login_by_parameter' => 'email',
                ],
                'request'   => [
                    'AUTH_KEY'     => 123,
                    'email'        => 'email@email.com',
                    'jwt'          => JWT::encode(['email' => 'test@test.com'], 'test', 'HS256'),
                    'new_password' => '123',
                ],
                'exceptionMessage' => 'This JWT can not change your password.'
            ],
        ];
    }

    public function testChangePasswordSuccess()
    {
        $settings = [
            'allow_reset_password'              => 1,
            'reset_password_requires_auth_code' => 1,
            'auth_codes'                        => [
                [
                    'code'            => 123,
                    'role'            => '',
                    'expiration_date' => '',
                ]
            ]
        ];
        $request  = [
            'AUTH_KEY'     => 123,
            'email'        => 'email@email.com',
            'code'         => '123',
            'new_password' => 123,
        ];
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock
            ->method('checkPasswordResetKeyByEmail')
            ->willReturn(['User']);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);
        $resetService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'PUT']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $result = $resetService->makeAction();
        $this->assertTrue($result);
    }

    public function testChangePasswordWithSpecialCharacters()
    {
        $specialPassword = 'P@$$w0rd!#&*()';
        $mock = $this->createMock(WordPressDataInterface::class);
        $settings = [
            'allow_reset_password'              => 1,
            'reset_password_requires_auth_code' => 0,
        ];
        $request  = [
            'email'        => 'email@email.com',
            'code'         => '123',
            'new_password' => $specialPassword,
        ];
        $mock->method('getOptionFromDatabase')->willReturn(json_encode($settings));
        $mock->method('checkPasswordResetKeyByEmail')->willReturn(['User']);
        $mock->method('createResponse')->willReturn(true);
        $mock->expects($this->once())
            ->method('resetPassword')
            ->with($this->anything(), $specialPassword);
        $resetService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'PUT']))
            ->withSettings(new SimpleJWTLoginSettings($mock));
        $resetService->makeAction();
    }

    public function testChangePasswordWithBase64Encoding()
    {
        $plainPassword   = 'P@$$w0rd!#&*()';
        $encodedPassword = base64_encode($plainPassword);
        $mock = $this->createMock(WordPressDataInterface::class);
        $settings = [
            'allow_reset_password'              => 1,
            'reset_password_requires_auth_code' => 0,
            'auth_password_base64'              => 1,
        ];
        $request  = [
            'email'        => 'email@email.com',
            'code'         => '123',
            'new_password' => $encodedPassword,
        ];
        $mock->method('getOptionFromDatabase')->willReturn(json_encode($settings));
        $mock->method('checkPasswordResetKeyByEmail')->willReturn(['User']);
        $mock->method('createResponse')->willReturn(true);
        $mock->expects($this->once())
            ->method('resetPassword')
            ->with($this->anything(), $plainPassword);
        $resetService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'PUT']))
            ->withSettings(new SimpleJWTLoginSettings($mock));
        $resetService->makeAction();
    }

    public function testInvalidRouteMethod()
    {
        $settings = [
            'allow_reset_password'              => 1,
            'reset_password_requires_auth_code' => 0,
        ];
        $request  = [
            'email'        => 'email@email.com',
            'code'         => '123',
            'new_password' => 123,
        ];
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Route called with invalid request method.');
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $resetService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'OPTIONS']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $resetService->makeAction();
    }
}
