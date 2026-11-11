<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SimpleJWTLogin\Modules\Abilities\AuditLog\AnalyzeAuditLogAbility;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepositoryInterface;

#[RunTestsInSeparateProcesses]
class AnalyzeAuditLogAbilityTest extends AbilityTestCase
{
    /**
     * @param string      $bucket
     * @param string      $status
     * @param int         $total
     * @param int         $users
     * @param string|null $email
     * @return object
     */
    private static function row($bucket, $status, $total, $users = 1, $email = null)
    {
        return (object) [
            'bucket'     => $bucket,
            'status'     => $status,
            'total'      => (string) $total,
            'users'      => (string) $users,
            'last_seen'  => '2026-11-08 09:00:00',
            'user_email' => $email,
        ];
    }

    /**
     * @param array<string, object[]> $rowsByDimension
     * @param array                   $auditLog
     * @param array                   $input
     * @return array
     */
    private function analyze(array $rowsByDimension, array $auditLog = [], array $input = [])
    {
        $repository = $this->createStub(AuditLogRepositoryInterface::class);
        $repository->method('aggregate')->willReturnCallback(function ($dimension) use ($rowsByDimension) {
            return isset($rowsByDimension[$dimension]) ? $rowsByDimension[$dimension] : [];
        });
        $stored = $this->storedSettings(['audit_log' => array_merge(
            ['enabled' => true, 'enabled_events' => AuditEvents::all(), 'retention_days' => 30],
            $auditLog
        )]);

        return (new AnalyzeAuditLogAbility($this->makeSettings($this->makeWordPressDataStub($stored)), $repository))
            ->execute($input);
    }

    public function testAggregatesCountsTimelineAndTopFailures()
    {
        $result = $this->analyze([
            'event'           => [
                self::row(AuditEvents::AUTH_LOGIN_SUCCESS, 'success', 6),
                self::row(AuditEvents::AUTH_LOGIN_FAILED, 'failure', 3),
                self::row(AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS, 'success', 1),
            ],
            'hour'            => [
                self::row('2026-11-08 09', 'failure', 2),
                self::row('2026-11-08 08', 'success', 6),
                self::row('2026-11-08 09', 'success', 1),
                self::row('2026-11-08 08', 'failure', 1),
            ],
            'failure_ip'      => [self::row('10.0.0.5', 'failure', 3, 2)],
            'failure_user'    => [self::row('7', 'failure', 2, 1, 'bob@example.com')],
            'failure_message' => [self::row('Wrong user credentials.', 'failure', 3)],
        ]);

        $this->assertSame(['hours' => 24, 'bucket' => 'hour'], $result['window']);
        $this->assertSame(['enabled' => true, 'retention_days' => 30, 'not_recorded' => []], $result['logging']);
        $this->assertSame(['events' => 10, 'failures' => 3, 'failure_rate' => 0.3], $result['totals']);
        $this->assertSame(
            ['event_type' => AuditEvents::AUTH_LOGIN_SUCCESS, 'total' => 6, 'success' => 6, 'failure' => 0, 'failure_rate' => 0.0],
            $result['by_event'][0]
        );
        $this->assertSame(
            [
                ['bucket' => '2026-11-08 08', 'success' => 6, 'failure' => 1],
                ['bucket' => '2026-11-08 09', 'success' => 1, 'failure' => 2],
            ],
            $result['timeline']
        );
        $this->assertSame(
            [['ip_address' => '10.0.0.5', 'failures' => 3, 'distinct_users' => 2, 'last_seen' => '2026-11-08 09:00:00']],
            $result['top_failing_ips']
        );
        $this->assertSame('bob@example.com', $result['top_failing_users'][0]['user_email']);
        $this->assertSame(7, $result['top_failing_users'][0]['user_id']);
        $this->assertSame([], $result['top_failing_api_keys']);
        $this->assertSame('Wrong user credentials.', $result['top_failure_messages'][0]['message']);
        $this->assertSame([], $result['findings']);
    }

    /**
     * @return array<string, array{0: array<string, object[]>, 1: array, 2: string[]}>
     */
    public static function findingsProvider()
    {
        return [
            'logging disabled'        => [
                ['failure_ip' => [self::row('10.0.0.5', 'failure', 500)]],
                ['enabled' => false],
                ['logging_disabled', 'brute_force_ip'],
            ],
            'security events skipped' => [
                [],
                ['enabled_events' => [AuditEvents::AUTH_LOGIN_SUCCESS]],
                ['security_events_not_recorded'],
            ],
            'brute force ip'          => [['failure_ip' => [self::row('10.0.0.5', 'failure', 25)]], [], ['brute_force_ip']],
            'credential stuffing'     => [['failure_ip' => [self::row('10.0.0.5', 'failure', 8, 6)]], [], ['credential_stuffing']],
            'below ip thresholds'     => [['failure_ip' => [self::row('10.0.0.5', 'failure', 19, 4)]], [], []],
            'targeted user'           => [['failure_user' => [self::row('3', 'failure', 12)]], [], ['targeted_user']],
            'api key failures'        => [['failure_api_key' => [self::row('4', 'failure', 10)]], [], ['api_key_failures']],
            'high failure rate'       => [
                ['event' => [
                    self::row(AuditEvents::AUTH_REFRESH_TOKEN_FAILED, 'failure', 15),
                    self::row(AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS, 'success', 5),
                ]],
                [],
                ['high_failure_rate'],
            ],
            'failure rate too few events' => [
                ['event' => [self::row(AuditEvents::AUTH_LOGIN_FAILED, 'failure', 19)]],
                [],
                [],
            ],
            'ai ability failures'     => [['event' => [self::row(AuditEvents::AI_ABILITY_FAILED, 'failure', 2)]], [], ['ai_ability_failures']],
            'failure spike'           => [
                ['hour' => [
                    self::row('2026-11-08 06', 'failure', 1),
                    self::row('2026-11-08 07', 'failure', 1),
                    self::row('2026-11-08 08', 'failure', 1),
                    self::row('2026-11-08 09', 'failure', 30),
                ]],
                [],
                ['failure_spike'],
            ],
        ];
    }

    #[DataProvider('findingsProvider')]
    public function testFindings($rows, $auditLog, $expectedCodes)
    {
        $result = $this->analyze($rows, $auditLog);

        $this->assertSame($expectedCodes, array_column($result['findings'], 'code'));
    }

    public function testLongWindowsAreClampedAndBucketedByDay()
    {
        $repository = $this->createMock(AuditLogRepositoryInterface::class);
        $calls      = [];
        $repository->expects($this->exactly(6))->method('aggregate')->willReturnCallback(
            function ($dimension, $hours, $limit) use (&$calls) {
                $calls[$dimension] = [$hours, $limit];

                return [];
            }
        );
        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));

        $result = (new AnalyzeAuditLogAbility($settings, $repository))->execute(['hours' => 99999, 'limit' => 500]);

        $this->assertSame(['hours' => AnalyzeAuditLogAbility::MAX_HOURS, 'bucket' => 'day'], $result['window']);
        $this->assertSame([AnalyzeAuditLogAbility::MAX_HOURS, AnalyzeAuditLogAbility::MAX_LIMIT], $calls['failure_ip']);
        $this->assertArrayHasKey('day', $calls);
        $this->assertArrayNotHasKey('hour', $calls);
    }
}
