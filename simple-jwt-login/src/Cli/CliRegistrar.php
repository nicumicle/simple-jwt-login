<?php

namespace SimpleJWTLogin\Cli;

use SimpleJWTLogin\Modules\Settings\GeneralSettings;

/**
 * Registers the wp jwt commands, when enabled in General > CLI.
 */
class CliRegistrar
{
    /**
     * Declared by the standalone Simple-JWT-Login CLI add-on, which registers the same commands.
     */
    const ADDON_FUNCTION = 'simple_jwt_login_cli_check_dependency';

    /**
     * @var GeneralSettings
     */
    private $generalSettings;

    /**
     * @param GeneralSettings $generalSettings
     */
    public function __construct(GeneralSettings $generalSettings)
    {
        $this->generalSettings = $generalSettings;
    }

    /**
     * Runs on plugins_loaded, once the add-on (if any) is loaded.
     *
     * @return void
     */
    public function register()
    {
        if (!$this->isWpCli() || $this->isAddonActive() || !$this->isEnabled()) {
            return;
        }

        foreach (self::commands() as $name => $class) {
            \WP_CLI::add_command($name, $class);
        }
    }

    /**
     * Command name => class. Groups (`jwt`) come before their subcommands.
     *
     * @return array<string, string>
     */
    public static function commands()
    {
        return array(
            'jwt'                 => SimpleJWTLoginCommand::class,
            'jwt config'          => ConfigCommand::class,
            'jwt api-key'         => ApiKeyCommand::class,
            'jwt key'             => KeyCommand::class,
            'jwt auth-code'       => AuthCodeCommand::class,
            'jwt log'             => LogCommand::class,
            'jwt webhook'         => WebhookCommand::class,
            'jwt setup'           => SetupCommand::class,
            'jwt doctor'          => DoctorCommand::class,
            'jwt security-report' => SecurityReportCommand::class,
            'jwt explain-error'   => ExplainErrorCommand::class,
            'jwt cleanup'         => CleanupCommand::class,
        );
    }

    /**
     * The General > CLI setting. Until it is saved: on, unless the add-on is active.
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (!$this->generalSettings->hasCliSetting()) {
            return !$this->isAddonActive();
        }

        return $this->generalSettings->isCliEnabled();
    }

    /**
     * @return bool True when the standalone Simple-JWT-Login CLI plugin is active.
     */
    public function isAddonActive()
    {
        return function_exists(self::ADDON_FUNCTION);
    }

    /**
     * @return bool
     */
    protected function isWpCli()
    {
        return defined('WP_CLI') && constant('WP_CLI');
    }
}
