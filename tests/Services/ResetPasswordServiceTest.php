<?php


namespace Services;


use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
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
            ->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
    }

    /**
     * @dataProvider sendUserPasswordProvider
     *
     * @param array $request
     *
     * @throws \Exception
     */
    public function testValidationSendUserPassword($settings, $request, $expectedExceptionMessage)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $authenticationService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'POST']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authenticationService->makeAction();
    }

    public function sendUserPasswordProvider()
    {
        return [
            [
                'settings'  => [],
                'request'   => [],
                'exception' => 'Reset Password is not allowed.'
            ],
            [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                ],
                'request'   => [],
                'exception' => 'Invalid Auth Code ( AUTH_KEY ) provided.'
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
                'exception' => 'Missing email parameter.'
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
                'exception' => 'Wrong user.'
            ],
        ];
    }

    /**
     * @dataProvider flowTypeProvider
     *
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
            ->withAnyParameters()
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);
        $authenticationService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'POST']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $result                = $authenticationService->makeAction();
        $this->assertSame(true, $result);
    }

    public function flowTypeProvider()
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

    /**
     * @dataProvider changePasswordValidationProvider
     *
     * @param array $settings
     * @param array $request
     * @param string $expectedExceptionMessage
     *
     * @throws Exception
     */
    public function testValidationChangePassword($settings, $request, $expectedExceptionMessage)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $authenticationService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'PUT']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authenticationService->makeAction();
    }

    public function changePasswordValidationProvider()
    {
        return [
            [
                'settings'  => [],
                'request'   => [],
                'exception' => 'Reset Password is not allowed.'
            ],
            [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 1,
                ],
                'request'   => [],
                'exception' => 'Invalid Auth Code ( AUTH_KEY ) provided.'
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
                'exception' => 'Missing email parameter.'
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
                    'email'    => 'email@email.com',
                ],
                'exception' => 'Missing code parameter.'
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
                    'email'    => 'email@email.com',
                    'code'     => '123',
                ],
                'exception' => 'Missing new_password parameter.'
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
                    'AUTH_KEY'     => 123,
                    'email'        => 'email@email.com',
                    'code'         => '123',
                    'new_password' => '123',
                ],
                'exception' => 'Invalid code provided.'
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
            ->method('checkPasswordResetKey')
            ->willReturn(['User']);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);
        $authenticationService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'PUT']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $result                = $authenticationService->makeAction();
        $this->assertSame(true, $result);
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
        $authenticationService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'OPTIONS']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authenticationService->makeAction();
    }
}
