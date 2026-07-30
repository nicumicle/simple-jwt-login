<?php
namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('validationProvider')]
    /**
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
    public static function validationProvider()
    {
        return [
            [
                'settings' => [],
                'request' => [],
                'exceptionMessage' => 'Authentication is not enabled.',
            ],
            [
                'settings' => [
                    'allow_authentication' => '0',
                ],
                'request' => [],
                'exceptionMessage' => 'Authentication is not enabled.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                ],
                'request' => [],
                'exceptionMessage' => 'The email, username, or login parameter is missing from the request.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                ],
                'request' => [
                    'email' => '',
                ],
                'exceptionMessage' => 'The password or password_hash parameter is missing from request.'
            ],
            [
                'settings' => [
                    'allow_authentication' => '1',
                ],
                'request' => [
                    'username' => '',
                ],
                'exceptionMessage' => 'The password or password_hash parameter is missing from request.'
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

    public function testWrongUserCredentialsWithHash()
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
                'password_hash' => '123'
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

    public function testGeneratePayloadDoesNotLeakAttackerSuppliedEmailClaim()
    {
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
                // Admin only allows "exp" to be present in the JWT payload.
                'jwt_payload' => [
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP,
                ],
            ]));
        $this->wordPressDataMock
            ->method('getUserProperty')
            ->willReturn('subscriber@test.com');

        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressDataMock);

        // Attacker-controlled payload sent to /auth, impersonating an admin.
        $attackerPayload = [
            'email' => 'admin@test.com',
        ];

        $payload = AuthenticateService::generatePayload(
            $attackerPayload,
            $this->wordPressDataMock,
            $jwtSettings,
            'subscriber-user'
        );

        $this->assertArrayNotHasKey(
            'email',
            $payload,
        );
    }

    /**
     * @param string $loginByParameter The admin-configured jwt_login_by_parameter.
     * @param array $attackerPayload The claims an attacker POSTs to /auth.
     * @param array $leafPath Dot-exploded path whose leaf must be absent after stripping.
     */
    #[DataProvider('jwtLoginByParameterLeakProvider')]
    public function testGeneratePayloadDoesNotLeakAttackerSuppliedJwtLoginByParameterClaim(
        $loginByParameter,
        $attackerPayload,
        $leafPath
    ) {
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                'allow_authentication' => 1,
                'jwt_payload' => [
                    AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP,
                ],
                // Admin configured autologin to resolve users by this claim.
                'jwt_login_by_parameter' => $loginByParameter,
            ]));
        $this->wordPressDataMock
            ->method('getUserProperty')
            ->willReturn('subscriber-uid');

        $jwtSettings = new SimpleJWTLoginSettings($this->wordPressDataMock);

        $payload = AuthenticateService::generatePayload(
            $attackerPayload,
            $this->wordPressDataMock,
            $jwtSettings,
            'subscriber-user'
        );

        // Walk to the parent of the leaf; the leaf key must have been stripped.
        $leaf = array_pop($leafPath);
        $container = $payload;
        foreach ($leafPath as $key) {
            $container = isset($container[$key]) ? $container[$key] : [];
        }

        $this->assertArrayNotHasKey(
            $leaf,
            (array)$container,
            'The login-by claim must be stripped so it cannot survive into the signed JWT.'
        );
    }

    /**
     * @return array<string, array>
     */
    public static function jwtLoginByParameterLeakProvider()
    {
        return [
            'flat claim' => [
                'custom_uid',
                ['custom_uid' => 'admin-uid'],
                ['custom_uid'],
            ],
            'nested claim (2 levels)' => [
                'data.id',
                ['data' => ['id' => 1]],
                ['data', 'id'],
            ],
            'deeply nested claim (3 levels)' => [
                'data.something.id',
                ['data' => ['something' => ['id' => 1]]],
                ['data', 'something', 'id'],
            ],
            // The strip must not fail (no warning/exception) when the claim is absent.
            'flat claim absent from payload' => [
                'custom_uid',
                ['unrelated' => 'value'],
                ['custom_uid'],
            ],
            'nested claim entirely absent' => [
                'data.id',
                ['unrelated' => 'value'],
                ['data', 'id'],
            ],
            'nested leaf absent but parent present' => [
                'data.id',
                ['data' => ['other' => 'value']],
                ['data', 'id'],
            ],
            'nested intermediate is a scalar not array' => [
                'data.id',
                ['data' => 'scalar'],
                ['data', 'id'],
            ],
            'deep path missing intermediate level' => [
                'data.something.id',
                ['data' => ['other' => 'value']],
                ['data', 'something', 'id'],
            ],
        ];
    }

    public function testSuccessFlowWithFullPayloadAndPasshash()
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
                    'password_hash' => '123',
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
