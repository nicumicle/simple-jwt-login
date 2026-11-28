<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use SimpleJWTLogin\Cli\SimpleJWTLoginCommand;
use SimpleJWTLogin\Cli\TokenService;
use SimpleJWTLogin\Repositories\Wordpress\Repository;
use WP_CLI;

/**
 * wp jwt generate / revoke-all, and the user lookup they share.
 */
class UserTokenCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WP_CLI::reset();
        RecordingAuditLog::$records = [];
    }

    #[DataProvider('findUserProvider')]
    public function testUserIsFoundByIdLoginOrEmail(string $identifier, string $expectedLookup): void
    {
        $tokenService = $this->createMock(TokenService::class);
        $tokenService->expects($this->once())->method('issueToken')->willReturn(['jwt' => 'a.b.c']);
        $command = $this->makeCommand($tokenService, $expectedLookup);

        $command->generate([$identifier], []);

        $this->assertSame([], WP_CLI::$errors);
        $this->assertSame(['a.b.c'], WP_CLI::$lines);
    }

    public static function findUserProvider(): array
    {
        return [
            'id'    => ['42', 'getUserDetailsById'],
            'login' => ['admin', 'getUserByUserLogin'],
            'email' => ['admin@example.com', 'getUserDetailsByEmail'],
        ];
    }

    public function testGenerateFailsForUnknownUser(): void
    {
        $tokenService = $this->createMock(TokenService::class);
        $tokenService->expects($this->never())->method('issueToken');

        $this->makeCommand($tokenService, 'none')->generate(['nobody'], []);

        $this->assertSame(['User not found: nobody'], WP_CLI::$errors);
    }

    #[DataProvider('generateProvider')]
    public function testGenerate(array $assocArgs, array $tokens, ?int $expectedTtl, array $expectedLines, array $expectedErrors): void
    {
        $tokenService = $this->createMock(TokenService::class);
        $tokenService->expects($expectedErrors === [] ? $this->once() : $this->never())
            ->method('issueToken')
            ->with($this->anything(), $expectedTtl)
            ->willReturn($tokens);

        $this->makeCommand($tokenService, 'getUserByUserLogin')->generate(['admin'], $assocArgs);

        $this->assertSame($expectedLines, WP_CLI::$lines);
        $this->assertSame($expectedErrors, WP_CLI::$errors);
        $expectedAudit = $expectedErrors === [] ? [['command' => 'generate', 'status' => 'success', 'details' => ['user_id' => 7]]] : [];
        $this->assertSame($expectedAudit, RecordingAuditLog::$records);
    }

    public static function generateProvider(): array
    {
        return [
            'jwt only'         => [[], ['jwt' => 'a.b.c'], null, ['a.b.c'], []],
            'custom ttl'       => [['ttl' => '10'], ['jwt' => 'a.b.c'], 10, ['a.b.c'], []],
            'with refresh'     => [[], ['jwt' => 'a.b.c', 'refresh_token' => 'r1'], null, [
                'jwt                  a.b.c',
                'refresh_token        r1',
            ], []],
            'json'             => [['format' => 'json'], ['jwt' => 'a.b.c'], null, ['{"jwt":"a.b.c"}'], []],
            'invalid ttl'      => [['ttl' => '0'], [], null, [], ['--ttl must be a number of minutes, at least 1.']],
        ];
    }

    public function testRevokeAllRevokesAfterConfirmation(): void
    {
        $tokenService = $this->createMock(TokenService::class);
        $tokenService->expects($this->once())->method('revokeAll')->with(7);

        $this->makeCommand($tokenService, 'getUserByUserLogin')->revokeAll(['admin'], ['yes' => true]);

        $this->assertSame(['confirm:Revoke every token of admin?'], WP_CLI::$lines);
        $this->assertSame(['All tokens of the user have been revoked.'], WP_CLI::$successes);
        $this->assertSame(['revoke-all'], array_column(RecordingAuditLog::$records, 'command'));
    }

    public function testRevokeAllFailureIsAudited(): void
    {
        $tokenService = $this->createMock(TokenService::class);
        $tokenService->expects($this->once())->method('revokeAll')->willThrowException(new Exception('DB down'));

        $this->makeCommand($tokenService, 'getUserByUserLogin')->revokeAll(['admin'], ['yes' => true]);

        $this->assertSame(['DB down'], WP_CLI::$errors);
        $this->assertSame([], WP_CLI::$successes);
        $this->assertSame(
            [['command' => 'revoke-all', 'status' => 'failure', 'details' => ['user_id' => 7, 'error' => 'DB down']]],
            RecordingAuditLog::$records
        );
    }

    /**
     * @param string $lookup The Repository method that finds the user, or 'none'.
     */
    private function makeCommand(TokenService $tokenService, string $lookup): SimpleJWTLoginCommand
    {
        $user = new stdClass();
        $wordPressData = $this->createStub(Repository::class);
        foreach (['getUserDetailsById', 'getUserByUserLogin', 'getUserDetailsByEmail'] as $method) {
            $wordPressData->method($method)->willReturn($method === $lookup ? $user : null);
        }
        $wordPressData->method('isInstanceOfuser')->willReturnCallback(function ($candidate) use ($user) {
            return $candidate === $user;
        });
        $wordPressData->method('getUserProperty')->willReturnMap([
            [$user, 'ID', 7],
            [$user, 'user_login', 'admin'],
        ]);

        return new class ($wordPressData, $tokenService) extends SimpleJWTLoginCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var mixed */
            private $wordPressData;
            /** @var mixed */
            private $tokenService;

            public function __construct($wordPressData, $tokenService)
            {
                $this->wordPressData = $wordPressData;
                $this->tokenService = $tokenService;
            }

            protected function createWordPressData()
            {
                return $this->wordPressData;
            }

            protected function createTokenService(): TokenService
            {
                return $this->tokenService;
            }
        };
    }
}
