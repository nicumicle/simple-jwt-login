<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\SecurityChecks;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\SecurityReportAbility;
use SimpleJWTLogin\Modules\Abilities\SettingsPath;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

#[RunTestsInSeparateProcesses]
class SecurityReportAbilityTest extends AbilityTestCase
{
    /**
     * Settings that trigger no finding at all.
     *
     * @return array
     */
    private function hardenedOverrides()
    {
        return [
            'general'       => [
                'decryption_key'  => str_repeat('k', 32),
                'request_jwt_url' => 0,
                'security'        => ['safe_redirect' => true],
            ],
            'register'      => ['enabled' => false],
            'delete_user'   => ['enabled' => false],
            'audit_log'     => ['enabled' => true],
            'cors'          => ['enabled' => false],
            'authorization' => ['revoke_token_enabled' => '1'],
        ];
    }

    /**
     * @return array<string, array{0: array, 1: string}>
     */
    public static function findingProvider()
    {
        return [
            'short HS256 key'       => [['general' => ['decryption_key' => 'short']], 'weak_signing_key'],
            'short base64 key'      => [['general' => ['decryption_key' => base64_encode(str_repeat('k', 20)), 'decryption_key_base64' => true]], 'weak_signing_key'],
            'HS512 needs 64 bytes'  => [['general' => ['jwt_algorithm' => 'HS512']], 'weak_signing_key'],
            'ttl over a day'        => [['authorization' => ['ttl' => '1441']], 'long_jwt_ttl'],
            'open autologin'        => [['login' => ['enabled' => true]], 'autologin_unrestricted'],
            'autologin no safe redirect' => [['login' => ['enabled' => true, 'auth_code' => true], 'general' => ['security' => ['safe_redirect' => false]]], 'unsafe_redirect'],
            'admin auth code'       => [['auth_codes' => ['codes' => [['code' => 'x', 'role' => 'administrator', 'expiration_date' => '']]]], 'auth_code_creates_admins'],
            'revoke endpoint off'   => [['authorization' => ['revoke_token_enabled' => '0']], 'revoke_endpoint_disabled'],
            'ai sees secrets'       => [['integrations' => ['3rdparty' => ['ai_abilities' => ['enabled' => true, 'allow_view_secrets' => true]]]], 'ai_can_view_secrets'],
        ];
    }

    #[DataProvider('findingProvider')]
    public function testSecurityChecksFindRisks(array $overrides, $expectedCode)
    {
        $stored   = array_replace_recursive($this->storedSettings($this->hardenedOverrides()), $overrides);
        $settings = $this->makeSettings($this->makeWordPressDataStub($stored));

        $findings = (new SecurityChecks($settings))->run();

        $this->assertSame([$expectedCode], array_column($findings, 'code'));
        foreach ($findings[0]['settings'] as $path) {
            $this->assertTrue(SettingsPath::exists($stored, $path), $path);
        }
    }

    /**
     * @return array<string, array{0: string[], 1: int, 2: string}>
     */
    public static function scoreProvider()
    {
        return [
            'clean'                 => [[], 100, 'A'],
            'one info'              => [['info'], 97, 'A'],
            'one warning'           => [['warning'], 90, 'A'],
            'one critical'          => [['critical'], 75, 'B'],
            'mixed'                 => [['critical', 'warning', 'info'], 62, 'C'],
            'two criticals'         => [['critical', 'critical', 'warning'], 40, 'D'],
            'floored at zero'       => [array_fill(0, 5, 'critical'), 0, 'F'],
        ];
    }

    #[DataProvider('scoreProvider')]
    public function testSummarizeScoresAndSortsBySeverity(array $severities, $score, $grade)
    {
        $findings = array_map(function ($severity) {
            return ['severity' => $severity, 'code' => $severity];
        }, array_reverse($severities));

        $report = SecurityReportAbility::summarize($findings);

        $this->assertSame($score, $report['score']);
        $this->assertSame($grade, $report['grade']);
        $this->assertSame(count(array_keys($severities, 'critical', true)), $report['counts']->critical);
        $sorted = array_column($report['findings'], 'severity');
        $this->assertSame($severities, $sorted);
    }

    /**
     * @return array<string, array{0: string, 1: string[]}>
     */
    public static function minSeverityProvider()
    {
        return [
            'info lists all'          => ['info', ['critical', 'warning', 'info']],
            'warning hides info'      => ['warning', ['critical', 'warning']],
            'critical only'           => ['critical', ['critical']],
            'unknown level lists all' => ['bogus', ['critical', 'warning', 'info']],
        ];
    }

    #[DataProvider('minSeverityProvider')]
    public function testMinSeverityFiltersTheListButNotTheScore($minSeverity, array $expected)
    {
        $findings = [
            ['severity' => 'info', 'code' => 'i'],
            ['severity' => 'warning', 'code' => 'w'],
            ['severity' => 'critical', 'code' => 'c'],
        ];

        $report = SecurityReportAbility::summarize($findings, $minSeverity);

        $this->assertSame($expected, array_column($report['findings'], 'severity'));
        $this->assertSame(62, $report['score']);
    }

    public function testReportCombinesMisconfigurationsAndHardeningChecks()
    {
        $stored = $this->storedSettings(['general' => ['decryption_key' => 'fixture-secret']]);
        $bridge = $this->createStub(TwoFactorBridge::class);

        $report = (new SecurityReportAbility($this->makeSettings($this->makeWordPressDataStub($stored)), $bridge))
            ->execute([]);

        $codes = array_column($report['findings'], 'code');
        $this->assertContains('delete_without_auth_code', $codes);
        $this->assertContains('weak_signing_key', $codes);
        $this->assertSame('critical', $report['findings'][0]['severity']);
        $this->assertLessThan(100, $report['score']);
        $this->assertStringNotContainsString('fixture-secret', (string) json_encode($report));
    }

    public function testHardenedSetupScoresFullMarks()
    {
        $stored = $this->storedSettings($this->hardenedOverrides());
        $bridge = $this->createStub(TwoFactorBridge::class);

        $report = (new SecurityReportAbility($this->makeSettings($this->makeWordPressDataStub($stored)), $bridge))
            ->execute([]);

        $this->assertSame([], $report['findings']);
        $this->assertSame(100, $report['score']);
    }
}
