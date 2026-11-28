<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Modules\Abilities\AbilityInterface;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

/**
 * Base class for all Simple JWT Login WP-CLI commands.
 *
 * Provides shared factories and output helpers so that concrete commands
 * can focus on their own logic. To add a new command, extend this class
 * and add it to CliRegistrar::$commands.
 */
abstract class AbstractCommand extends \WP_CLI_Command
{
    const FORMAT_TEXT = 'text';
    const FORMAT_JSON = 'json';
    const FORMAT_TABLE = 'table';
    const DEFAULT_OUTPUT_FORMAT = self::FORMAT_TEXT;

    /**
     * Formats of list output (WP_CLI\Utils\format_items).
     *
     * @var string[]
     */
    protected static $listFormats = array('table', 'csv', 'json', 'yaml', 'count');

    // -------------------------------------------------------------------------
    // WordPress factories (protected so tests can override via subclass)
    // -------------------------------------------------------------------------

    /**
     * Create the JWT settings instance.
     *
     * @return SimpleJWTLoginSettings
     */
    protected function createSettings()
    {
        return new SimpleJWTLoginSettings($this->createWordPressData());
    }

    /**
     * Create the WordPress data-access instance.
     *
     * @return WordPressRepository
     */
    protected function createWordPressData()
    {
        return WordPressRepository::getInstance();
    }

    /**
     * @param string $identifier User ID, login or email, like `wp user get`.
     * @return mixed WP_User
     * @throws Exception When no user matches.
     */
    protected function findUser($identifier)
    {
        $wordPressData = $this->createWordPressData();
        $identifier = trim((string) $identifier);
        $user = null;
        if (ctype_digit($identifier)) {
            $user = $wordPressData->getUserDetailsById((int) $identifier);
        }
        if (!$wordPressData->isInstanceOfuser($user)) {
            $user = $wordPressData->getUserByUserLogin($identifier);
        }
        if (!$wordPressData->isInstanceOfuser($user) && strpos($identifier, '@') !== false) {
            $user = $wordPressData->getUserDetailsByEmail($identifier);
        }
        if (!$wordPressData->isInstanceOfuser($user)) {
            throw new Exception(sprintf(
                /* translators: %s is the user ID, login or email */
                esc_html__('User not found: %s', 'simple-jwt-login'),
                esc_html($identifier)
            ));
        }

        return $user;
    }

    /**
     * @return CliAuditLog
     */
    protected function createAuditLog()
    {
        global $wpdb;

        return new CliAuditLog(new AuditLogRepository($wpdb), $this->createWordPressData());
    }

    /**
     * Run a command that changes something and record it in the audit log (when enabled).
     *
     * @param string   $command e.g. "config set"
     * @param callable $action
     * @param array    $details Recorded with the entry. Never secrets.
     * @return mixed What $action returns.
     * @throws Exception What $action throws, after it was recorded.
     */
    protected function audited($command, $action, array $details = array())
    {
        $auditLog = $this->createAuditLog();
        $auditLog->start();
        try {
            $result = call_user_func($action);
        } catch (Exception $exception) {
            $auditLog->record($command, 'failure', array_merge($details, array('error' => $exception->getMessage())));
            throw $exception;
        }
        $auditLog->record($command, 'success', $details);

        return $result;
    }

    /**
     * Run an AI ability for its result: the CLI shares the abilities' logic, not their access rules.
     *
     * @param AbilityInterface $ability
     * @param array            $input
     * @return array
     * @throws Exception
     */
    protected function runAbility(AbilityInterface $ability, array $input = array())
    {
        // Abilities return objects for JSON schema reasons; the CLI only needs arrays.
        $result = json_decode((string) wp_json_encode($ability->execute($input)), true);

        return is_array($result) ? $result : array();
    }

    // -------------------------------------------------------------------------
    // Output helpers
    // -------------------------------------------------------------------------

    /**
     * Output an associative array in text or JSON format.
     *
     * @param array  $data
     * @param string $format FORMAT_TEXT or FORMAT_JSON
     * @return void
     */
    protected function outputKeyValue(array $data, $format)
    {
        if ($format === self::FORMAT_JSON) {
            \WP_CLI::line((string) json_encode($data));
            return;
        }

        foreach ($data as $key => $value) {
            \WP_CLI::line(sprintf('%-20s %s', $key, $this->displayValue($value)));
        }
    }

    /**
     * Output a list with --format=table|csv|json|yaml|count and --fields.
     *
     * @param array    $items
     * @param array    $assocArgs
     * @param string[] $defaultFields
     * @return void
     */
    protected function outputItems(array $items, array $assocArgs, array $defaultFields)
    {
        $format = $this->getFormat($assocArgs, self::FORMAT_TABLE, self::$listFormats);
        $fields = isset($assocArgs['fields'])
            ? array_map('trim', explode(',', (string) $assocArgs['fields']))
            : $defaultFields;

        $rows = array();
        foreach ($items as $item) {
            $row = array();
            foreach ($fields as $field) {
                $value = isset($item[$field]) ? $item[$field] : null;
                $row[$field] = $format === self::FORMAT_JSON ? $value : $this->displayValue($value);
            }
            $rows[] = $row;
        }

        \WP_CLI\Utils\format_items($format, $rows, $fields);
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function displayValue($value)
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return is_array($value) ? (string) json_encode($value) : (string) $value;
    }

    /**
     * For subcommands that take no positional arguments.
     *
     * @param array $args
     * @return void
     */
    protected function rejectPositionalArgs(array $args)
    {
        if (!empty($args)) {
            \WP_CLI::error(sprintf(
                /* translators: %s is the unexpected argument */
                __('Unexpected argument: %s', 'simple-jwt-login'),
                (string) reset($args)
            ));
        }
    }

    /**
     * Resolve and validate the --format option from assoc args.
     *
     * Emits a WP_CLI error (halting in production) when the supplied value is
     * not one of the allowed formats and falls back to $default so that tests,
     * which do not exit on error, can still assert the error was recorded.
     *
     * @param array         $assocArgs
     * @param string        $default
     * @param string[]|null $allowed   Default: text and json.
     * @return string
     */
    protected function getFormat(array $assocArgs, $default = self::DEFAULT_OUTPUT_FORMAT, $allowed = null)
    {
        if (!isset($assocArgs['format'])) {
            return $default;
        }

        $format  = $assocArgs['format'];
        $allowed = $allowed === null ? array(self::FORMAT_TEXT, self::FORMAT_JSON) : $allowed;

        if (!in_array($format, $allowed, true)) {
            \WP_CLI::error(sprintf(
                /* translators: 1: format string provided, 2: list of allowed formats */
                __('Invalid format "%1$s". Allowed formats: %2$s.', 'simple-jwt-login'),
                $format,
                implode(', ', $allowed)
            ));
            return $default;
        }

        return $format;
    }

    /**
     * Prompt the user for confirmation before a destructive action (skipped with --yes).
     *
     * @param string $question
     * @param array  $assocArgs WP-CLI assoc args (may contain 'yes' flag).
     * @return void
     */
    protected function confirmAction($question, array $assocArgs = array())
    {
        \WP_CLI::confirm($question, $assocArgs);
    }

    /**
     * Stop with an exit code, for scripts.
     *
     * @param int $exitCode
     * @return void
     */
    protected function halt($exitCode)
    {
        \WP_CLI::halt($exitCode);
    }
}
