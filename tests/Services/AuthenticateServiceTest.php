<?php
namespace SimpleJWTLoginTests\Services;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\AuthenticateService;

class AuthenticateServiceTest extends TestCase
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
     * @param array $settings
     * @param array $request
     * @param string $exceptionMessage
     *
     * @throws Exception
     */
    public function testValidation($settings, $request, $exceptionMessage)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $authService = (new AuthenticateService())
            ->withRequest($request)
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authService->makeAction();
    }

    /**
     * @return array[]
     */
    public function validationProvider()
    {
        return [
            [
                'settings' => [],
                'request' => [],
                'expectedMessage' => 'Authentication is not enabled.',
            ],
            [
                'settings' => [
                    'allow_authentication' => '0',
                ],
                'request' => [],
                'expectedMessage' => 'Authentication is not enabled.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                ],
                'request' => [],
                'expectedMessage' => 'The email or username parameter is missing from request.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                ],
                'request' => [
                    'email' => '',
                ],
                'expectedMessage' => 'The password parameter is missing from request.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                ],
                'request' => [
                    'username' => '',
                ],
                'expectedMessage' => 'The password parameter is missing from request.'
            ],
        ];
    }

    public function testIpLimitation()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You are not allowed to Authenticate from this IP:');
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(
                [
                    'allow_authentication' => 1,
                    'auth_ip' => '127.0.0.1',
                ]
            ));
        $authService = (new AuthenticateService())
            ->withRequest([
                'email' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper(['HTTP_CLIENT_IP' => '127.0.0.2']))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authService->makeAction();
    }

    public function testUserNotFoundWithEmail()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Wrong user credentials.');
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
            ]));
        $this->wordPressDataMock->method('getUserDetailsByEmail')
            ->willReturn(null);
        $authService = (new AuthenticateService())
            ->withRequest([
                'email' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authService->makeAction();
    }

    public function testUserNotFoundWithUsername()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Wrong user credentials.');
        $this->wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
            ]));
        $this->wordPressDataMock->method('getUserByUserLogin')
                                ->willReturn(null);
        $authService = (new AuthenticateService())
            ->withRequest([
                'username' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authService->makeAction();
    }

    public function testWrongUserCredentials()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Wrong user credentials.');

        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
            ]));
        $this->wordPressDataMock
            ->method('getUserByUserLogin')
            ->willReturn('user');
        $this->wordPressDataMock
            ->method('getUserPassword')
            ->willReturn('1234');
        $this->wordPressDataMock
            ->method('checkPassword')
            ->willReturn(false);
        $authService = (new AuthenticateService())
            ->withRequest([
                'username' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authService->makeAction();
    }

    public function testMissingAuthCodes()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid Auth Code ( AUTH_KEY ) provided.');

        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
                'auth_requires_auth_code' => true,
            ]));

        $authService = (new AuthenticateService())
            ->withRequest([
                'username' => 'test@test.com',
                'password' => '123'
            ])
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $authService->makeAction();
    }

    public function testSuccessFlowWithFullPayload()
    {
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
                'auth_requires_auth_code' => true,
                'jwt_payload' => [
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_ID,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_SITE,
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME
                ],
                'enabled_hooks' => [
                    SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME
                ],
                'auth_codes' => [
                    [
                        'code' => '123',
                        'role' => '',
                        'expiration_date' => '',
                    ]
                ]
            ]));
        $this->wordPressDataMock
            ->method('getUserByUserLogin')
            ->willReturn('user');
        $this->wordPressDataMock
            ->method('getUserPassword')
            ->willReturn('1234');
        $this->wordPressDataMock
            ->method('checkPassword')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);
        $authService = (new AuthenticateService())
            ->withRequest(
                [
                    'username' => 'test@test.com',
                    'password' => '123',
                    'AUTH_KEY' => '123',
                ]
            )
            ->withCookies([])
            ->withServerHelper(new ServerHelper([]))
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock));
        $result = $authService->makeAction();
        $this->assertTrue($result);
    }
}
