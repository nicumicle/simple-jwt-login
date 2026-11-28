<?php

namespace SimpleJWTLogin\Cli;

use SimpleJWTLogin\Modules\Abilities\AuditLog\AnalyzeAuditLogAbility;
use SimpleJWTLogin\Modules\Abilities\AuditLog\GetAuditLogAbility;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;

/**
 * Read the activity (audit) log.
 */
class LogCommand extends AbstractCommand
{
    /**
     * List activity log entries, newest first.
     *
     * ## OPTIONS
     *
     * [--event-type=<event>]
     * : For example auth.login.failed.
     *
     * [--status=<status>]
     * : success or failure.
     * ---
     * options:
     *   - success
     *   - failure
     * ---
     *
     * [--user-id=<id>]
     * : Entries of one user.
     *
     * [--page=<page>]
     * : Page number. Default: 1.
     *
     * [--per-page=<count>]
     * : Entries per page, up to 100. Default: 20.
     *
     * [--fields=<fields>]
     * : Comma separated fields to show.
     *
     * [--format=<format>]
     * : table, csv, json, yaml or count. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp jwt log list --status=failure --per-page=50
     *     wp jwt log list --event-type=cli.command.success --format=json
     *
     * @subcommand list
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function listEntries(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $input = array();
        foreach (array('event-type', 'status', 'user-id', 'page', 'per-page') as $option) {
            if (isset($assocArgs[$option])) {
                $input[str_replace('-', '_', $option)] = $assocArgs[$option];
            }
        }

        $result = $this->runAbility(new GetAuditLogAbility($this->createSettings(), $this->createAuditLogRepository()), $input);
        $this->outputItems(
            $result['items'],
            $assocArgs,
            array('id', 'created_at', 'event_type', 'status', 'user_id', 'ip_address', 'message')
        );
    }

    /**
     * Summarize the activity log: failure rates, top failing IPs, users and API keys, and findings.
     *
     * ## OPTIONS
     *
     * [--hours=<hours>]
     * : Time window in hours. Default: 24.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt log analyze --hours=168
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function analyze(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $format = $this->getFormat($assocArgs);
        $input = isset($assocArgs['hours']) ? array('hours' => (int) $assocArgs['hours']) : array();
        $report = $this->runAbility(new AnalyzeAuditLogAbility($this->createSettings(), $this->createAuditLogRepository()), $input);
        if ($format === self::FORMAT_JSON) {
            \WP_CLI::line((string) json_encode($report));
            return;
        }

        $this->outputKeyValue(array_merge($report['window'], $report['totals']), $format);
        foreach (array('by_event', 'top_failing_ips', 'top_failing_users', 'findings') as $section) {
            if (empty($report[$section])) {
                continue;
            }
            \WP_CLI::line('');
            \WP_CLI::line($section);
            $this->outputItems($report[$section], array(), array_keys($report[$section][0]));
        }
    }

    /**
     * @return AuditLogRepository
     */
    protected function createAuditLogRepository()
    {
        global $wpdb;

        return new AuditLogRepository($wpdb);
    }
}
