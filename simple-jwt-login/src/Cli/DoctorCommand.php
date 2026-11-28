<?php

namespace SimpleJWTLogin\Cli;

use SimpleJWTLogin\Modules\Abilities\Diagnostics\DiagnoseAbility;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

/**
 * Check the configuration: versions, REST URLs, endpoints, JWT settings and warnings.
 */
class DoctorCommand extends AbstractCommand
{
    /**
     * Check the configuration: versions, REST URLs, endpoints, JWT settings and warnings.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt doctor
     *     wp jwt doctor --format=json
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function __invoke(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $format = $this->getFormat($assocArgs);
        $report = $this->runAbility(new DiagnoseAbility($this->createSettings(), new TwoFactorBridge()));
        if ($format === self::FORMAT_JSON) {
            \WP_CLI::line((string) json_encode($report));
            return;
        }

        $this->outputKeyValue(array_merge($report['versions'], $report['rest'], $report['jwt']), $format);
        \WP_CLI::line('');
        $this->outputItems($report['endpoints'], array(), array('method', 'url', 'enabled', 'auth_code_required'));
        if (empty($report['warnings'])) {
            \WP_CLI::success(__('No configuration problems found.', 'simple-jwt-login'));
            return;
        }
        \WP_CLI::line('');
        $this->outputItems($report['warnings'], array(), array('severity', 'code', 'message', 'settings'));
    }
}
