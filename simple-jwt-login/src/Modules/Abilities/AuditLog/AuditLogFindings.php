<?php

namespace SimpleJWTLogin\Modules\Abilities\AuditLog;

use SimpleJWTLogin\Modules\AuditEvents;

/**
 * Threshold-based findings over the aggregated audit log. Each finding has
 * the same shape as the diagnose warnings: {severity, code, message, settings}.
 */
class AuditLogFindings
{
    const BRUTE_FORCE_FAILURES   = 20;
    const BRUTE_FORCE_CRITICAL   = 100;
    const STUFFING_DISTINCT_USERS = 5;
    const TARGETED_USER_FAILURES = 10;
    const API_KEY_FAILURES       = 10;
    const FAILURE_RATE_MIN_TOTAL = 20;
    const FAILURE_RATE_THRESHOLD = 0.5;
    const SPIKE_MIN_FAILURES     = 10;
    const SPIKE_FACTOR           = 3;

    /**
     * Events whose absence hides attacks.
     *
     * @var string[]
     */
    private static $securityEvents = array(
        'auth.login.failed',
        'auth.2fa.verify_failed',
        'auth.refresh_token.failed',
        'api_key.used',
        'ai.ability.failed',
    );

    /**
     * @param array $logging
     * @param array $byEvent
     * @param array $timeline
     * @param array $ips
     * @param array $users
     * @param array $apiKeys
     * @return array
     */
    public function run(array $logging, array $byEvent, array $timeline, array $ips, array $users, array $apiKeys)
    {
        // Entries recorded before the log was disabled are still analysed.
        $coverage = [$this->finding(
            'warning',
            'logging_disabled',
            __('The audit log is disabled: new events are not recorded, so the results only cover entries saved while it was enabled.', 'simple-jwt-login'),
            ['audit_log.enabled']
        )];
        if ($logging['enabled']) {
            $coverage = $this->checkCoverage($logging);
        }

        return array_merge(
            $coverage,
            $this->checkIps($ips),
            $this->checkUsers($users),
            $this->checkApiKeys($apiKeys),
            $this->checkEvents($byEvent),
            $this->checkSpikes($timeline)
        );
    }

    /**
     * @param array $logging
     * @return array
     */
    protected function checkCoverage(array $logging)
    {
        $missing = array_values(array_intersect(self::$securityEvents, $logging['not_recorded']));
        if (empty($missing)) {
            return [];
        }

        return [$this->finding(
            'info',
            'security_events_not_recorded',
            /* translators: %s: comma separated audit event names */
            sprintf(__('These security events are not recorded, so related attacks are invisible: %s.', 'simple-jwt-login'), implode(', ', $missing)),
            ['audit_log.enabled_events']
        )];
    }

    /**
     * @param array $ips
     * @return array
     */
    protected function checkIps(array $ips)
    {
        $findings = [];
        foreach ($ips as $ip) {
            if ($ip['distinct_users'] >= self::STUFFING_DISTINCT_USERS) {
                $findings[] = $this->finding(
                    'critical',
                    'credential_stuffing',
                    /* translators: 1: IP address, 2: number of users, 3: number of failures */
                    sprintf(__('IP %1$s failed for %2$d different users (%3$d failures): likely credential stuffing.', 'simple-jwt-login'), $ip['ip_address'], $ip['distinct_users'], $ip['failures']),
                    []
                );
                continue;
            }
            if ($ip['failures'] >= self::BRUTE_FORCE_FAILURES) {
                $findings[] = $this->finding(
                    $ip['failures'] >= self::BRUTE_FORCE_CRITICAL ? 'critical' : 'warning',
                    'brute_force_ip',
                    /* translators: 1: IP address, 2: number of failures */
                    sprintf(__('IP %1$s has %2$d failures: possible brute force. Consider blocking it or adding rate limiting.', 'simple-jwt-login'), $ip['ip_address'], $ip['failures']),
                    []
                );
            }
        }

        return $findings;
    }

    /**
     * @param array $users
     * @return array
     */
    protected function checkUsers(array $users)
    {
        $findings = [];
        foreach ($users as $user) {
            if ($user['failures'] < self::TARGETED_USER_FAILURES) {
                continue;
            }
            $findings[] = $this->finding(
                'warning',
                'targeted_user',
                /* translators: 1: user id, 2: number of failures */
                sprintf(__('User #%1$d has %2$d failures: the account may be targeted.', 'simple-jwt-login'), $user['user_id'], $user['failures']),
                []
            );
        }

        return $findings;
    }

    /**
     * @param array $apiKeys
     * @return array
     */
    protected function checkApiKeys(array $apiKeys)
    {
        $findings = [];
        foreach ($apiKeys as $apiKey) {
            if ($apiKey['failures'] < self::API_KEY_FAILURES) {
                continue;
            }
            $findings[] = $this->finding(
                'warning',
                'api_key_failures',
                /* translators: 1: API key id, 2: number of failures */
                sprintf(__('API key #%1$d has %2$d failures: it may be leaked, expired or missing permissions.', 'simple-jwt-login'), $apiKey['api_key_id'], $apiKey['failures']),
                []
            );
        }

        return $findings;
    }

    /**
     * @param array $byEvent
     * @return array
     */
    protected function checkEvents(array $byEvent)
    {
        $findings = [];
        foreach ($byEvent as $event) {
            if ($event['event_type'] !== AuditEvents::AI_ABILITY_FAILED) {
                continue;
            }
            $findings[] = $this->finding(
                'info',
                'ai_ability_failures',
                /* translators: 1: number of failed calls, 2: audit event name */
                sprintf(__('%1$d AI ability calls failed. Use get-audit-log with event_type %2$s for the reasons.', 'simple-jwt-login'), $event['total'], AuditEvents::AI_ABILITY_FAILED),
                ['integrations.3rdparty.ai_abilities']
            );
        }

        foreach ($this->actions($byEvent) as $action => $counts) {
            $rate = $counts['failure'] / $counts['total'];
            if ($counts['total'] < self::FAILURE_RATE_MIN_TOTAL || $rate < self::FAILURE_RATE_THRESHOLD) {
                continue;
            }
            $findings[] = $this->finding(
                'warning',
                'high_failure_rate',
                /* translators: 1: action name, 2: failure percentage, 3: failures, 4: total attempts */
                sprintf(__('%1$s fails %2$d%% of the time (%3$d of %4$d). Check top_failure_messages and explain-error.', 'simple-jwt-login'), $action, (int) round($rate * 100), $counts['failure'], $counts['total']),
                []
            );
        }

        return $findings;
    }

    /**
     * Success and failure are separate event types (auth.login.success /
     * auth.login.failed), so failure rates are computed per action.
     *
     * @param array $byEvent
     * @return array<string, array{total: int, failure: int}>
     */
    protected function actions(array $byEvent)
    {
        $actions = [];
        foreach ($byEvent as $event) {
            $action = preg_replace('/[._](success|failed|request|challenge_issued)$/', '', $event['event_type']);
            if ($action === 'ai.ability') {
                continue;
            }
            if (!isset($actions[$action])) {
                $actions[$action] = ['total' => 0, 'failure' => 0];
            }
            $actions[$action]['total']   += $event['total'];
            $actions[$action]['failure'] += $event['failure'];
        }

        return $actions;
    }

    /**
     * @param array $timeline
     * @return array
     */
    protected function checkSpikes(array $timeline)
    {
        if (count($timeline) < 2) {
            return [];
        }
        $average  = array_sum(array_column($timeline, 'failure')) / count($timeline);
        $findings = [];
        foreach ($timeline as $bucket) {
            if ($bucket['failure'] < self::SPIKE_MIN_FAILURES || $bucket['failure'] < $average * self::SPIKE_FACTOR) {
                continue;
            }
            $findings[] = $this->finding(
                'warning',
                'failure_spike',
                /* translators: 1: hour or day, 2: number of failures, 3: average failures */
                sprintf(__('Failure spike at %1$s: %2$d failures (average %3$.1f).', 'simple-jwt-login'), $bucket['bucket'], $bucket['failure'], $average),
                []
            );
        }

        return $findings;
    }

    /**
     * @param string   $severity
     * @param string   $code
     * @param string   $message
     * @param string[] $settings
     * @return array
     */
    private function finding($severity, $code, $message, array $settings)
    {
        return ['severity' => $severity, 'code' => $code, 'message' => $message, 'settings' => $settings];
    }
}
