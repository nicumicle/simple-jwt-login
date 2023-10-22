<?php

namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\DeleteUserSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\DeleteUserService;

class DeleteUserServiceTest extends TestCase
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
     * @dataProvider validationProvider
     * @param array $request
     * @param array $settings
     * @param string $exceptionMessage
     *
     * @throws Exception
     */
    public function testValidations($request, $settings, $exceptionMessage)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->wordPressDataMock->method('getOptionFromDatabase')
                                ->willReturn(json_encode($settings));
        $deleteUserService = (new DeleteUserService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([
                'HTTP_CLIENT_IP' => '127.0.0.1',
            ]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $deleteUserService->makeAction();
    }

    public function testUserNotFoundFromJWT()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found.');
        $settings = [
            'allow_delete' => true,
            'require_delete_auth' => false,
            'delete_user_by' => DeleteUserSettings::DELETE_USER_BY_ID,
            'decryption_key' => 'test',
            'jwt_delete_by_parameter' => 'id',
        ];

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));

        $this->wordPressDataMock->method('getUserDetailsById')
            ->willReturn(false);
        $deleteUserService = (new DeleteUserService())
            ->withRequest([
                'JWT' => JWT::encode(['id' => 1], $settings['decryption_key'], 'HS256')
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $deleteUserService->makeAction();
    }

    public function testUnableToDeleteUser()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found.');

        $settings = [
            'allow_delete' => true,
            'require_delete_auth' => false,
            'delete_user_by' => DeleteUserSettings::DELETE_USER_BY_ID,
            'decryption_key' => 'test',
            'jwt_delete_by_parameter' => 'id',
        ];

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $useMock = $this->getMockBuilder(\WP_User::class)
            ->getMock();
        $this->wordPressDataMock->method('getUserDetailsById')
            ->willReturn($useMock);
        $this->wordPressDataMock->method('deleteUser')
            ->willReturn(false);
        $deleteUserService = (new DeleteUserService())
            ->withRequest([
                'JWT' => JWT::encode(['id' => 1], $settings['decryption_key'], 'HS256')
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $deleteUserService->makeAction();
    }

    /**
     * @dataProvider deleteByProvider
     * @param int $deleteBy
     * @throws Exception
     */
    public function testSuccessResponse($deleteBy)
    {
        $settings = [
            'allow_delete' => true,
            'require_delete_auth' => false,
            'delete_user_by' => $deleteBy,
            'decryption_key' => 'test',
            'jwt_delete_by_parameter' => 'id',
            'enabled_hooks' => [
                SimpleJWTLoginHooks::DELETE_USER_ACTION_NAME,
            ]
        ];

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $useMock = $this->getMockBuilder(\WP_User::class)
            ->getMock();
        $this->wordPressDataMock->method('getUserDetailsById')
            ->willReturn($useMock);
        $this->wordPressDataMock->method('getUserDetailsByEmail')
            ->willReturn($useMock);
        $this->wordPressDataMock->method('getUserByUserLogin')
            ->willReturn($useMock);
        $this->wordPressDataMock->method('deleteUser')
            ->willReturn(true);
        $this->wordPressDataMock->method('triggerAction')
            ->willReturn(true);
        $this->wordPressDataMock->method('createResponse')
            ->willReturn(true);
        $deleteUserService = (new DeleteUserService())
            ->withRequest([
                'JWT' => JWT::encode(['id' => 1], $settings['decryption_key'], 'HS256')
            ])
            ->withCookies([])
            ->withSession([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        $response = $deleteUserService->makeAction();
        $this->assertTrue($response);
    }

    public function testDeleteByInvalidAction()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found.');
        $this->expectExceptionCode(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND);

        $settings = [
            'allow_delete' => true,
            'require_delete_auth' => false,
            'delete_user_by' => -1,
            'decryption_key' => 'test',
            'jwt_delete_by_parameter' => 'id',
            'enabled_hooks' => [
                SimpleJWTLoginHooks::DELETE_USER_ACTION_NAME,
            ]
        ];

        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));

        $deleteUserService = (new DeleteUserService())
            ->withRequest([
                'JWT' => JWT::encode(['id' => 1], $settings['decryption_key'], 'HS256')
            ])
            ->withCookies([])
            ->withSession([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        $deleteUserService->makeAction();
    }

    /**
     * @return array[]
     */
    public static function deleteByProvider()
    {
        return [
            'delete-by-email' => [
                DeleteUserSettings::DELETE_USER_BY_EMAIL,
            ],
            'delete-by-id' => [
                DeleteUserSettings::DELETE_USER_BY_ID,
            ],
            'delete-by-username' => [
                DeleteUserSettings::DELETE_USER_BY_USER_LOGIN
            ]

        ];
    }


    public static function validationProvider()
    {
        return [
            'test_empty_settings_and_request' => [
                'request' => [],
                'settings' => [],
                'expectedException' => 'Delete is not enabled.',
            ],
            'test_missing_jwt_parameter' => [
                'request' => [],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'The `jwt` parameter is missing.',
            ],
            'test_empty_jwt' => [
                'request' => [
                    'jwt' => ''
                ],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'The `jwt` parameter is missing.',
            ],
            'empty_upper_case_jwt' => [
                'request' => [
                    'JWT' => ''
                ],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'The `jwt` parameter is missing.',
            ],
            'test_missing_auth_code' => [
                'request' => [
                    'jwt' => '123.123.123',

                ],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'Missing AUTH KEY ( AUTH_KEY ).',
            ],
            'test_empty_auth_code' => [
                'request' => [
                    'jwt' => '123.123.123',
                    'AUTH_KEY' => '',
                ],
                'settings' => [
                    'allow_delete' => true,
                ],
                'expectedException' => 'Missing AUTH KEY ( AUTH_KEY ).',
            ],
            'test_ip_not_allowed' => [
                'request' => [
                    'jwt' => '123.123.123',
                    'AUTH_KEY' => '123',
                ],
                'settings' => [
                    'allow_delete' => true,
                    'require_delete_auth' => false,
                    'delete_ip' => '127.1.1.1, 127.2.2.2',
                ],
                'expectedException' => 'You are not allowed to delete users from this IP:',
            ],

        ];
    }
}
