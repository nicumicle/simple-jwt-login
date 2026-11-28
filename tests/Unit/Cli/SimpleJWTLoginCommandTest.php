<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Cli\SimpleJWTLoginCommand;
use WP_CLI;
use WP_REST_Response;

class SimpleJWTLoginCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WP_CLI::reset();
    }

    /** @return Repository&\PHPUnit\Framework\MockObject\Stub */
    private function makeWordPressDataMock()
    {
        return $this->createStub(Repository::class);
    }

    // -------------------------------------------------------------------------
    // generateToken tests
    // -------------------------------------------------------------------------

    public function testGenerateTokenReturnsJwtFromService(): void
    {
        $expectedJwt = 'header.payload.signature';

        $wordPressDataMock = $this->makeWordPressDataMock();

        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['allow_authentication' => 1]));
        $wordPressDataMock->method('getUserByUserLogin')
            ->willReturn('user');
        $wordPressDataMock->method('getUserPassword')
            ->willReturn('hash');
        $wordPressDataMock->method('checkPassword')
            ->willReturn(true);
        $wordPressDataMock->method('createResponse')
            ->willReturn(new WP_REST_Response([
                'success' => true,
                'data'    => ['jwt' => $expectedJwt],
            ]));

        $jwtSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $command     = $this->makeGenerateTokenCommand($jwtSettings);

        $jwt = $command->callGenerateToken(['username' => 'admin', 'password' => 'secret']);

        $this->assertSame($expectedJwt, $jwt);
    }

    public function testGenerateTokenPropagatesException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Wrong user credentials.');

        $wordPressDataMock = $this->makeWordPressDataMock();

        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['allow_authentication' => 1]));
        $wordPressDataMock->method('getUserByUserLogin')
            ->willReturn(null);

        $jwtSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $command     = $this->makeGenerateTokenCommand($jwtSettings);

        $command->callGenerateToken(['username' => 'admin', 'password' => 'wrong']);
    }

    public function testGenerateTokenRejectsTwoFactorChallenge(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('This user must complete two-factor authentication.');

        $service = $this->getMockBuilder(AuthenticateService::class)
            ->onlyMethods(['authenticateUser'])
            ->getMock();
        $service->expects($this->once())->method('authenticateUser')->willReturn(new WP_REST_Response([
            'data' => ['jwt' => 'interim.jwt.token', 'two_factor_required' => true],
        ]));

        $wordPressDataMock = $this->makeWordPressDataMock();
        $wordPressDataMock->method('getOptionFromDatabase')->willReturn(json_encode(['allow_authentication' => 1]));

        $command = $this->makeGenerateTokenCommand(new SimpleJWTLoginSettings($wordPressDataMock), $service);
        $command->callGenerateToken(['username' => 'admin', 'password' => 'secret']);
    }

    public function testGenerateTokenRequiresEnabledAuthentication(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Authentication is not enabled.');

        // Mocked, because SimpleJWTLoginSettings caches the sections of the previous tests.
        $authentication = $this->createStub(AuthenticationSettings::class);
        $authentication->method('isAuthenticationEnabled')->willReturn(false);
        $settings = $this->createStub(SimpleJWTLoginSettings::class);
        $settings->method('getAuthenticationSettings')->willReturn($authentication);

        $command = $this->makeGenerateTokenCommand($settings);
        $command->callGenerateToken(['username' => 'admin', 'password' => 'secret']);
    }

    // -------------------------------------------------------------------------
    // login command tests
    // -------------------------------------------------------------------------

    /**
     * @param array<string,string> $assocArgs
     * @param string[]             $expectedLines
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('loginSuccessProvider')]
    public function testLoginSuccess(array $assocArgs, array $expectedLines): void
    {
        $command = $this->makeTestableCommand('my.jwt.token');
        $command->login([], $assocArgs);
        $this->assertSame($expectedLines, WP_CLI::$lines);
        $this->assertEmpty(WP_CLI::$errors);
    }

    /** @return array<string,array{array<string,string>,string[]}> */
    public static function loginSuccessProvider(): array
    {
        return [
            'text output via username' => [
                ['username' => 'admin', 'password' => 'secret'],
                ['my.jwt.token'],
            ],
            'json output via username' => [
                ['username' => 'admin', 'password' => 'secret', 'format' => 'json'],
                ['{"jwt":"my.jwt.token"}'],
            ],
            'text output via email' => [
                ['email' => 'admin@example.com', 'password' => 'secret'],
                ['my.jwt.token'],
            ],
            'text output via login' => [
                ['login' => 'admin', 'password' => 'secret'],
                ['my.jwt.token'],
            ],
        ];
    }

    /**
     * @param array<string,string> $assocArgs
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('loginErrorProvider')]
    public function testLoginOutputsError(array $assocArgs, string $expectedError, ?Exception $exception = null): void
    {
        $command = $this->makeTestableCommand('irrelevant', $exception);
        $command->login([], $assocArgs);
        $this->assertContains($expectedError, WP_CLI::$errors);
        $this->assertEmpty(WP_CLI::$lines);
    }

    /** @return array<string,array{array<string,string>,string,Exception|null}> */
    public static function loginErrorProvider(): array
    {
        return [
            'invalid output format'  => [
                ['username' => 'admin', 'password' => 'secret', 'format' => 'xml'],
                'Invalid format. Accepted values: text, json',
                null,
            ],
            'missing identifier'     => [
                ['password' => 'secret'],
                'Please provide --username, --email, or --login',
                null,
            ],
            'missing password'       => [
                ['username' => 'admin'],
                'Please provide --password',
                null,
            ],
            'service throws exception' => [
                ['username' => 'admin', 'password' => 'wrong'],
                'Wrong user credentials.',
                new Exception('Wrong user credentials.'),
            ],
        ];
    }

    public function testLoginReadsThePasswordWhenNotPassed(): void
    {
        $command = $this->makeTestableCommand('my.jwt.token');
        $command->typedPassword = 'typed secret';
        $command->login([], ['username' => 'admin']);

        $this->assertSame(['username' => 'admin', 'password' => 'typed secret'], $command->lastRequest);
        $this->assertSame(['my.jwt.token'], WP_CLI::$lines);
    }

    // -------------------------------------------------------------------------
    // decodeJwtPayload tests
    // -------------------------------------------------------------------------

    /**
     * @param array<string,mixed>|null $expectedPayload
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('decodeJwtPayloadProvider')]
    public function testDecodeJwtPayload(string $token, ?array $expectedPayload): void
    {
        $command = $this->makePayloadDecoderCommand();
        $this->assertSame($expectedPayload, $command->callDecodeJwtPayload($token));
    }

    /** @return array<string,array{string,array<string,mixed>|null}> */
    public static function decodeJwtPayloadProvider(): array
    {
        $make = static function (array $payload): string {
            return 'h.' . rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=') . '.s';
        };

        return [
            'valid payload'              => [
                $make(['sub' => '1', 'username' => 'admin']),
                ['sub' => '1', 'username' => 'admin'],
            ],
            'two parts only'             => ['header.payload', null],
            'four parts'                 => ['a.b.c.d', null],
            'no dots at all'             => ['nodots', null],
            'invalid base64 payload'     => ['h.!!!.s', null],
            'payload is a scalar string' => [
                'h.' . rtrim(strtr(base64_encode('"just-a-string"'), '+/', '-_'), '=') . '.s',
                null,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // decode command tests
    // -------------------------------------------------------------------------

    /**
     * @param string[]             $args
     * @param array<string,string> $assocArgs
     * @param string[]             $expectedLines
     * @param string[]             $expectedErrors
     * @param string[]             $expectedWarnings
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('decodeProvider')]
    public function testDecode(
        array $args,
        array $assocArgs,
        array $expectedLines,
        array $expectedErrors,
        array $expectedWarnings
    ): void {
        $command = $this->makeDecodeCommand();
        $command->decode($args, $assocArgs);
        $this->assertSame($expectedLines, WP_CLI::$lines);
        $this->assertSame($expectedErrors, WP_CLI::$errors);
        $this->assertSame($expectedWarnings, WP_CLI::$warnings);
    }

    /** @return array<string,array{string[],array<string,string>,string[],string[],string[]}> */
    public static function decodeProvider(): array
    {
        $payload = ['sub' => '1', 'username' => 'admin'];
        $token   = 'h.' . rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=') . '.s';
        $warning = 'The token signature was NOT verified. Use `wp jwt validate` to verify authenticity.';

        return [
            'text output' => [
                [$token], [],
                ['sub                  1', 'username             admin'],
                [],
                [$warning],
            ],
            'json output' => [
                [$token], ['format' => 'json'],
                ['{"sub":"1","username":"admin"}'],
                [],
                [$warning],
            ],
            'missing token arg' => [
                [], [],
                [],
                ['Please provide a JWT token: wp jwt decode <token>'],
                [],
            ],
            'invalid jwt structure' => [
                ['not-a-jwt'], [],
                [],
                ['Could not decode token. Ensure it has a valid JWT structure (header.payload.signature).'],
                [],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // validate command tests
    // -------------------------------------------------------------------------

    /**
     * @param string[]             $args
     * @param array<string,string> $assocArgs
     * @param string[]             $expectedLines
     * @param string[]             $expectedErrors
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('validateProvider')]
    public function testValidate(
        array $args,
        array $assocArgs,
        array $expectedLines,
        array $expectedErrors,
        ?Exception $exception = null
    ): void {
        $command = $this->makeValidateCommand($exception);
        $command->validate($args, $assocArgs);
        $this->assertSame($expectedLines, WP_CLI::$lines);
        $this->assertSame($expectedErrors, WP_CLI::$errors);
        // Scripts can rely on the exit code: 1 when the token is not valid.
        $this->assertSame($exception === null ? null : 1, WP_CLI::$exitCode);
    }

    /** @return array<string,array{string[],array<string,string>,string[],string[],Exception|null}> */
    public static function validateProvider(): array
    {
        return [
            'valid token text' => [
                ['my.jwt.token'], [],
                ['valid                yes', 'message              Token is valid.'],
                [],
                null,
            ],
            'valid token json' => [
                ['my.jwt.token'], ['format' => 'json'],
                ['{"valid":true,"message":"Token is valid."}'],
                [],
                null,
            ],
            'invalid token text' => [
                ['bad.jwt.token'], [],
                ['valid                no', 'message              Token expired.'],
                [],
                new Exception('Token expired.'),
            ],
            'invalid token json' => [
                ['bad.jwt.token'], ['format' => 'json'],
                ['{"valid":false,"message":"Token expired."}'],
                [],
                new Exception('Token expired.'),
            ],
            'missing token arg' => [
                [], [],
                [],
                ['Please provide a JWT token: wp jwt validate <token>'],
                null,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // revoke command tests
    // -------------------------------------------------------------------------

    /**
     * @param string[]             $args
     * @param array<string,string> $assocArgs
     * @param string[]             $expectedLines
     * @param string[]             $expectedErrors
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('revokeProvider')]
    public function testRevoke(
        array $args,
        array $assocArgs,
        array $expectedLines,
        array $expectedErrors,
        ?Exception $exception = null
    ): void {
        $command = $this->makeRevokeCommand($exception);
        $command->revoke($args, $assocArgs);
        $this->assertSame($expectedLines, WP_CLI::$lines);
        $this->assertSame($expectedErrors, WP_CLI::$errors);
    }

    /** @return array<string,array{string[],array<string,string>,string[],string[],Exception|null}> */
    public static function revokeProvider(): array
    {
        return [
            'success text' => [
                ['my.jwt.token'], [],
                ['success              yes', 'message              Token has been revoked.'],
                [],
                null,
            ],
            'success json' => [
                ['my.jwt.token'], ['format' => 'json'],
                ['{"success":true,"message":"Token has been revoked."}'],
                [],
                null,
            ],
            'missing token arg' => [
                [], [],
                [],
                ['Please provide a JWT token: wp jwt revoke <token>'],
                null,
            ],
            'doRevokeToken throws exception' => [
                ['my.jwt.token'], [],
                [],
                ['Token has already been revoked.'],
                new Exception('Token has already been revoked.'),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Subclass that injects a pre-built settings object so no WordPress
     * environment is needed. Exposes callGenerateToken() as a public proxy.
     */
    private function makeGenerateTokenCommand(
        SimpleJWTLoginSettings $settings,
        ?AuthenticateService $service = null
    ): object {
        return new class ($settings, $service) extends SimpleJWTLoginCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var SimpleJWTLoginSettings */
            private $settings;
            /** @var AuthenticateService|null */
            private $service;

            public function __construct(SimpleJWTLoginSettings $settings, ?AuthenticateService $service)
            {
                $this->settings = $settings;
                $this->service = $service;
            }

            protected function createSettings(): SimpleJWTLoginSettings
            {
                return $this->settings;
            }

            protected function createAuthenticateService()
            {
                return $this->service !== null ? $this->service : parent::createAuthenticateService();
            }

            /** @param array<string,string> $request */
            public function callGenerateToken(array $request): string
            {
                return $this->generateToken($request);
            }
        };
    }

    /**
     * Subclass that stubs generateToken() so no real WordPress environment is
     * required for login orchestration tests.
     */
    private function makeTestableCommand(string $jwt, ?Exception $exception = null): SimpleJWTLoginCommand
    {
        return new class ($jwt, $exception) extends SimpleJWTLoginCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var string */
            private $mockJwt;
            /** @var array<string,string> */
            public $lastRequest = [];
            /** @var string What readPassword() returns: the prompt or STDIN. */
            public $typedPassword = '';
            /** @var Exception|null */
            private $mockException;

            public function __construct(string $jwt, ?Exception $exception = null)
            {
                $this->mockJwt       = $jwt;
                $this->mockException = $exception;
            }

            protected function readPassword(): string
            {
                return $this->typedPassword;
            }

            protected function generateToken(array $request): string
            {
                $this->lastRequest = $request;
                if ($this->mockException !== null) {
                    throw $this->mockException;
                }
                return $this->mockJwt;
            }
        };
    }

    /**
     * Concrete subclass for testing decode() - decodeJwtPayload() is pure PHP
     * so no WordPress environment is needed.
     */
    private function makeDecodeCommand(): SimpleJWTLoginCommand
    {
        return new class extends SimpleJWTLoginCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }
        };
    }

    /**
     * Subclass that stubs doValidateToken() for validate() orchestration tests.
     */
    private function makeValidateCommand(?Exception $exception = null): SimpleJWTLoginCommand
    {
        return new class ($exception) extends SimpleJWTLoginCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var Exception|null */
            private $mockException;
            /** @var string */
            public $lastJwt = '';

            public function __construct(?Exception $exception)
            {
                $this->mockException = $exception;
            }

            protected function doValidateToken($jwt): void
            {
                $this->lastJwt = $jwt;
                if ($this->mockException !== null) {
                    throw $this->mockException;
                }
            }
        };
    }

    /**
     * Subclass that stubs doRevokeToken() for revoke() orchestration tests.
     */
    private function makeRevokeCommand(?Exception $exception = null): SimpleJWTLoginCommand
    {
        return new class ($exception) extends SimpleJWTLoginCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var Exception|null */
            private $mockException;
            /** @var string */
            public $lastJwt = '';

            public function __construct(?Exception $exception)
            {
                $this->mockException = $exception;
            }

            protected function doRevokeToken($jwt): void
            {
                $this->lastJwt = $jwt;
                if ($this->mockException !== null) {
                    throw $this->mockException;
                }
            }
        };
    }

    /**
     * Subclass that exposes decodeJwtPayload() as a public proxy.
     */
    private function makePayloadDecoderCommand(): object
    {
        return new class extends SimpleJWTLoginCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            public function callDecodeJwtPayload(string $jwt): ?array
            {
                return $this->decodeJwtPayload($jwt);
            }
        };
    }
}
