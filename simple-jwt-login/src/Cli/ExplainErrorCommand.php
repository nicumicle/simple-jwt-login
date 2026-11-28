<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\ExplainErrorAbility;

/**
 * Explain a Simple JWT Login error code.
 */
class ExplainErrorCommand extends AbstractCommand
{
    /**
     * Explain an error code from an API response or a settings save: the cause and the settings to check.
     *
     * ## OPTIONS
     *
     * <code>
     * : The error code, for example 48.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt explain-error 48
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function __invoke(array $args, array $assocArgs)
    {
        $format = $this->getFormat($assocArgs);
        if (empty($args) || !ctype_digit((string) $args[0])) {
            \WP_CLI::error(__('Please provide a numeric error code: wp jwt explain-error <code>', 'simple-jwt-login'));
            return;
        }

        try {
            $result = $this->runAbility(
                new ExplainErrorAbility($this->createSettings(), new AbilitySettingsPolicy()),
                array('error_code' => (int) $args[0])
            );
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }
        $this->outputKeyValue($result, $format);
    }
}
