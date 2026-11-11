<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SimpleJWTLogin\Modules\Abilities\AuditLog\GetAuditLogAbility;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepositoryInterface;

#[RunTestsInSeparateProcesses]
class GetAuditLogAbilityTest extends AbilityTestCase
{
    /**
     * @return array<string, array{0: array, 1: array, 2: int, 3: int}>
     */
    public static function queryProvider()
    {
        return [
            'defaults' => [[], [], 1, 20],
            'filters forwarded, empty ones dropped' => [
                ['event_type' => 'auth.login.failed', 'status' => 'failure', 'user_email' => '', 'date_from' => '2026-11-01'],
                ['event_type' => 'auth.login.failed', 'status' => 'failure', 'date_from' => '2026-11-01'],
                1,
                20,
            ],
            'per page clamped' => [['page' => 3, 'per_page' => 500], [], 3, 100],
        ];
    }

    #[DataProvider('queryProvider')]
    public function testQueryIsForwardedToRepository($input, $expectedFilters, $expectedPage, $expectedPerPage)
    {
        $repository = $this->createMock(AuditLogRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findPaginated')
            ->with($expectedFilters, $expectedPage, $expectedPerPage)
            ->willReturn(['items' => [], 'total' => 0]);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        $result   = (new GetAuditLogAbility($settings, $repository))->execute($input);

        $this->assertSame(['items' => [], 'total' => 0, 'page' => $expectedPage, 'per_page' => $expectedPerPage], $result);
    }

    public function testEntriesAreFormatted()
    {
        $repository = $this->createStub(AuditLogRepositoryInterface::class);
        $repository->method('findPaginated')->willReturn([
            'items' => [(object) [
                'id' => '9', 'event_type' => 'auth.login.success', 'user_id' => '4', 'ip_address' => '10.0.0.1',
                'status' => 'success', 'message' => null, 'api_key_id' => null,
                'created_at' => '2026-11-08 10:00:00', 'user_email' => 'a@b.c',
            ]],
            'total' => '1',
        ]);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        $result   = (new GetAuditLogAbility($settings, $repository))->execute([]);

        $this->assertSame(1, $result['total']);
        $this->assertSame([
            'id'         => 9,
            'event_type' => 'auth.login.success',
            'status'     => 'success',
            'user_id'    => 4,
            'user_email' => 'a@b.c',
            'ip_address' => '10.0.0.1',
            'message'    => null,
            'api_key_id' => null,
            'created_at' => '2026-11-08 10:00:00',
        ], $result['items'][0]);
    }

    /**
     * Settings-save entries written before secrets were redacted in the diff
     * still hold plain-text auth codes / JWT rule keys / webhook headers.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function legacyMessageProvider()
    {
        $codesFrom = json_encode([['code' => 'old-c0de', 'role' => 'editor', 'expiration_date' => '']]);
        $codesTo   = json_encode([['code' => 'new-c0de', 'role' => 'editor', 'expiration_date' => '']]);
        $hooks     = json_encode([['url' => 'https://h.example.com/?t=tok', 'headers' => [['key' => 'X', 'value' => 'hv']]]]);

        return [
            'admin save diff' => [
                (string) json_encode([
                    'changed' => [
                        'auth_codes.codes' => ['from' => $codesFrom, 'to' => $codesTo],
                        'general.decryption_key' => ['from' => 'plain-old', 'to' => 'plain-new'],
                        'login.enabled' => ['from' => '0', 'to' => '1'],
                    ],
                    'added' => ['login.redirect'],
                ]),
                (string) json_encode([
                    'changed' => [
                        'auth_codes.codes' => [
                            'from' => json_encode([['code' => '********', 'role' => 'editor', 'expiration_date' => '']]),
                            'to'   => json_encode([['code' => '********', 'role' => 'editor', 'expiration_date' => '']]),
                        ],
                        'general.decryption_key' => ['from' => '********', 'to' => '********'],
                        'login.enabled' => ['from' => '0', 'to' => '1'],
                    ],
                    'added' => ['login.redirect'],
                ]),
            ],
            'ability diff with webhooks' => [
                (string) json_encode([
                    'source' => 'ability',
                    'diff'   => ['changed' => ['webhooks.items' => ['from' => '[]', 'to' => $hooks]]],
                ]),
                (string) json_encode([
                    'source' => 'ability',
                    'diff'   => ['changed' => ['webhooks.items' => [
                        'from' => '[]',
                        'to'   => json_encode([[
                            'url'     => 'https://h.example.com/?t=********',
                            'headers' => [['key' => 'X', 'value' => '********']],
                        ]]),
                    ]]],
                ]),
            ],
            'plain text message untouched' => ['Invalid credentials', 'Invalid credentials'],
        ];
    }

    public function testMessagesStayMaskedEvenWithViewSecretsPermission()
    {
        $message    = (string) json_encode(['changed' => ['general.decryption_key' => ['from' => 'plain-old', 'to' => 'plain-new']]]);
        $repository = $this->createStub(AuditLogRepositoryInterface::class);
        $repository->method('findPaginated')->willReturn(['items' => [(object) ['id' => 1, 'message' => $message]], 'total' => 1]);

        $stored = $this->storedSettings();
        $stored['integrations']['3rdparty']['ai_abilities'] = ['enabled' => true, 'access' => 'read', 'allow_view_secrets' => true];
        $result = (new GetAuditLogAbility($this->makeSettings($this->makeWordPressDataStub($stored)), $repository))->execute([]);

        $this->assertStringNotContainsString('plain-', (string) $result['items'][0]['message']);
    }

    #[DataProvider('legacyMessageProvider')]
    public function testSecretsInStoredMessagesAreRedacted($message, $expected)
    {
        $repository = $this->createStub(AuditLogRepositoryInterface::class);
        $repository->method('findPaginated')->willReturn([
            'items' => [(object) ['id' => 1, 'message' => $message]],
            'total' => 1,
        ]);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        $result   = (new GetAuditLogAbility($settings, $repository))->execute([]);

        $this->assertSame($expected, $result['items'][0]['message']);
    }
}
