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
        $isEmail = isset($request['email']) && $request['email'] !== '---';
        $this->wordPressDataMock->method('isEmail')->willReturn($isEmail);
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
                'exceptionMessage' => 'Auth Code is required.'
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
            'invalid_email_for_reset_password' => [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 0,
                ],
                'request'   => [
                    'email' => '---',
                ],
                'exceptionMessage' => 'Invalid email parameter.'
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
        $this->wordPressDataMock->method('isEmail')->willReturn(true);
        $this->wordPressDataMock
            ->method('getUserDetailsByEmail')
            ->willReturn(['User']);
        $this->wordPressDataMock
            ->method('applyFilters')
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
        $isEmail = isset($request['email']) && $request['email'] !== '---';
        $this->wordPressDataMock->method('isEmail')->willReturn($isEmail);
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
                'exceptionMessage' => 'Auth Code is required.'
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
            'missing_code_or_jwt' => [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 0,
                    'reset_password_jwt'                => 1,
                ],
                'request'   => [
                    'email'        => 'email@email.com',
                    'new_password' => 'abc',
                ],
                'exceptionMessage' => 'Missing code or jwt parameter.'
            ],
            'invalid_email_format_change_password' => [
                'settings'  => [
                    'allow_reset_password'              => 1,
                    'reset_password_requires_auth_code' => 0,
                ],
                'request'   => [
                    'email'        => '---',
                    'code'         => '123',
                    'new_password' => 'abc',
                ],
                'exceptionMessage' => 'Invalid email parameter.'
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
        $this->wordPressDataMock->method('isEmail')->willReturn(true);
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
        $mock->method('isEmail')->willReturn(true);
        $mock->method('checkPasswordResetKeyByEmail')->willReturn(['User']);
        $mock->method('createResponse')->willReturn(true);
        $mock->method('wpSlash')->willReturnCallback('addslashes');
        $mock->expects($this->once())
            ->method('resetPassword')
            ->with($this->anything(), addslashes($specialPassword));
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
        $mock->method('isEmail')->willReturn(true);
        $mock->method('checkPasswordResetKeyByEmail')->willReturn(['User']);
        $mock->method('createResponse')->willReturn(true);
        $mock->method('wpSlash')->willReturnCallback('addslashes');
        $mock->expects($this->once())
            ->method('resetPassword')
            ->with($this->anything(), addslashes($plainPassword));
        $resetService = (new ResetPasswordService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'PUT']))
            ->withSettings(new SimpleJWTLoginSettings($mock));
        $resetService->makeAction();
    }

    #[DataProvider('specialCharsPasswordProvider')]
    public function testSpecialCharsPasswordIsSlashedBeforeResetPassword(string $rawPassword): void
    {
        $mock = $this->createMock(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_reset_password'              => 1,
                'reset_password_requires_auth_code' => 0,
            ]));
        $mock->method('isEmail')->willReturn(true);
        $mock->method('checkPasswordResetKeyByEmail')->willReturn(['User']);
        $mock->method('createResponse')->willReturn(true);
        $mock->method('wpSlash')->willReturnCallback('addslashes');
        $mock->expects($this->once())
            ->method('resetPassword')
            ->with(
                $this->anything(),
                $this->identicalTo(addslashes($rawPassword))
            );

        $resetService = (new ResetPasswordService())
            ->withRequest([
                'email'        => 'test@test.com',
                'code'         => '123',
                'new_password' => $rawPassword,
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'PUT']))
            ->withSettings(new SimpleJWTLoginSettings($mock));

        $resetService->makeAction();
    }

    public static function specialCharsPasswordProvider(): array
    {
        return [
            'double_quote'  => ['"hello"'],
            'single_quote'  => ["'hello'"],
            'backslash'     => ['back\\slash'],
            'null_byte'     => ["nul\x00byte"],
            'mixed_special' => ["!@#\$%^&*\"'\\"],
        ];
    }

    #[DataProvider('passwordChangedNotificationProvider')]
    public function testChangePasswordSendsNotificationWhenEnabled(bool $notificationEnabled, int $expectedCalls): void
    {
        $mock = $this->createMock(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_reset_password'              => 1,
                'reset_password_requires_auth_code' => 0,
                'reset_password_send_changed_email' => $notificationEnabled ? 1 : 0,
            ]));
        $mock->method('isEmail')->willReturn(true);
        $mock->method('checkPasswordResetKeyByEmail')->willReturn(['User']);
        $mock->method('createResponse')->willReturn(true);
        $mock->expects($this->exactly($expectedCalls))
            ->method('sendPasswordChangedNotification');

        $resetService = (new ResetPasswordService())
            ->withRequest([
                'email'        => 'test@test.com',
                'code'         => '123',
                'new_password' => 'newpass',
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['REQUEST_METHOD' => 'PUT']))
            ->withSettings(new SimpleJWTLoginSettings($mock));

        $resetService->makeAction();
    }

    public static function passwordChangedNotificationProvider(): array
    {
        return [
            'notification_enabled'  => [true, 1],
            'notification_disabled' => [false, 0],
        ];
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
