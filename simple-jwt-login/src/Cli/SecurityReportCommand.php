<?php

namespace SimpleJWTLogin\Cli;

use SimpleJWTLogin\Modules\Abilities\Diagnostics\SecurityReportAbility;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

/**
 * Score the security of the configuration.
 */
class SecurityReportCommand extends AbstractCommand
{
    /**
     * Score the security of the configuration (0-100, grade A-F) and list the findings.
     *
     * ## OPTIONS
     *
     * [--min-severity=<severity>]
     * : Least severe findings to list: info, warning or critical. Default: info.
     *
     * [--fields=<fields>]
     * : Comma separated finding fields to show.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt security-report
     *     wp jwt security-report --min-severity=warning --format=json
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function __invoke(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $format = $this->getFormat($assocArgs);
        $minSeverity = isset($assocArgs['min-severity']) ? $assocArgs['min-severity'] : 'info';
        if (!in_array($minSeverity, array('info', 'warning', 'critical'), true)) {
            \WP_CLI::error(__('--min-severity must be info, warning or critical.', 'simple-jwt-login'));
            return;
        }

        $report = $this->runAbility(
            new SecurityReportAbility($this->createSettings(), new TwoFactorBridge()),
            array('min_severity' => $minSeverity)
        );
        if ($format === self::FORMAT_JSON) {
            \WP_CLI::line((string) json_encode($report));
            return;
        }

        \WP_CLI::line(sprintf(
            /* translators: 1: score from 0 to 100, 2: grade letter */
            __('Score: %1$d/100 (grade %2$s)', 'simple-jwt-login'),
            $report['score'],
            $report['grade']
        ));
        $this->outputKeyValue($report['counts'], $format);
        if (!empty($report['findings'])) {
            $this->outputItems(
                $report['findings'],
                array_intersect_key($assocArgs, array('fields' => true)),
                array('severity', 'code', 'message', 'settings')
            );
        }
    }
}
