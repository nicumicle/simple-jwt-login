<?php

namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\ValidateTokenService;

class ValidateTokenServiceTest extends TestCase
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
     *
     * @param mixed $settings
     * @param string $expectedMessage
     * @param array $request
     *
     * @throws Exception
     */
    public function testValidation($settings, $expectedMessage, $request = [])
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $validateTokenService = (new ValidateTokenService())
            ->withSession([])
            ->withCookies([])
            ->withRequest($request)
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        $validateTokenService->makeAction();
    }

    public static function validationProvider()
    {
        return [
            [
                'settings' => [],
                'message'  => 'Authentication is not enabled',
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                    'request_jwt_header'   => '0',
                    'request_jwt_url'      => '1',
                ],
                'message'  => 'The `jwt` parameter is missing.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                    'request_jwt_header'   => '0',
                    'request_jwt_url'      => '1',
                    'decryption_key'       => '123',
                    'request_keys'         => [
                        'url' => 'JWT'
                    ]
                ],
                'message'  => 'Wrong number of segments',
                'request'  => [
                    'JWT' => '123'
                ]
            ]
        ];
    }

    public function testUserNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found');
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(
                json_encode(
                    [
                        'allow_authentication' => '1',
                        'request_jwt_header'   => '0',
                        'request_jwt_url'      => '1',
                        'decryption_key'       => '123',
                        'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
                        'jwt_login_by_parameter' => 'id',
                        'request_keys'         => [
                            'url' => 'JWT'
                        ],
                    ]
                )
            );
        $validateTokenService = (new ValidateTokenService())
            ->withSession([])
            ->withCookies([])
            ->withRequest(['JWT' => JWT::encode(['id' => 123], '123', 'HS256')])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        $validateTokenService->makeAction();
    }

    public function testSuccessFlow()
    {
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(
                json_encode(
                    [
                        'allow_authentication' => '1',
                        'request_jwt_header'   => '0',
                        'request_jwt_url'      => '1',
                        'decryption_key'       => '123',
                        'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
                        'jwt_login_by_parameter' => 'id',
                        'request_keys'         => [
                            'url' => 'JWT'
                        ],
                        'enabled_hooks' => [
                            SimpleJWTLoginHooks::HOOK_RESPONSE_VALIDATE_TOKEN,
                        ],
                    ]
                )
            );

        $this->wordPressDataMock
            ->method('getUserDetailsById')
            ->withAnyParameters()
            ->willReturn([]);
        $this->wordPressDataMock
            ->method('isInstanceOfUser')
            ->withAnyParameters()
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('wordpressUserToArray')
            ->willReturn(
                [
                    'user_pass' => 123,
                    'test' => 123,
                ]
            );
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(['success' => true]);
        $this->wordPressDataMock
            ->method('triggerFilter')
            ->willReturn([]);
        $validateTokenService = (new ValidateTokenService())
            ->withSession([])
            ->withCookies([])
            ->withRequest(['JWT' => JWT::encode(['id' => 123, 'exp' => strtotime('+1Year')], '123', 'HS256')])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));

        $result  = $validateTokenService->makeAction();
        $this->assertSame(['success' => true], $result);
    }
}
