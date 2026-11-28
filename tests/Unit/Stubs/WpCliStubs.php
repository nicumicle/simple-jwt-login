<?php

// phpcs:disable PSR1.Files.SideEffects

/**
 * WP-CLI test doubles: they mirror the snake_case static API of WP-CLI.
 */

namespace {
    /**
     * Minimal WP_CLI_Command stub. The real class provides argument parsing helpers
     * that are not needed for unit tests.
     */
    class WP_CLI_Command
    {
    }

    /**
     * Minimal WP_CLI test double.
     *
     * Captures output and errors so assertions can inspect them.
     * Call WP_CLI::reset() in setUp() to start each test with a clean slate.
     */
    class WP_CLI
    {
        /** @var string[] Lines printed via WP_CLI::line() */
        public static array $lines = [];

        /** @var string[] Messages passed to WP_CLI::error() */
        public static array $errors = [];

        /** @var string[] Messages passed to WP_CLI::success() */
        public static array $successes = [];

        /** @var string[] Messages passed to WP_CLI::warning() */
        public static array $warnings = [];

        /** @var array<string,string> Commands passed to WP_CLI::add_command(), name => class */
        public static array $commands = [];

        /** @var array<int,array{format: string, items: array, fields: array}> Calls of WP_CLI\Utils\format_items() */
        public static array $formatted = [];

        /** @var int|null Exit code passed to WP_CLI::halt() */
        public static ?int $exitCode = null;

        public static function line(string $message): void
        {
            self::$lines[] = $message;
        }

        /**
         * @param string $message
         * @param bool   $exit    Ignored in tests – we never exit.
         */
        public static function error(string $message, bool $exit = true): void
        {
            self::$errors[] = $message;
        }

        public static function success(string $message): void
        {
            self::$successes[] = $message;
        }

        public static function warning(string $message): void
        {
            self::$warnings[] = $message;
        }

        /**
         * Always confirms in tests.
         *
         * @param string  $question
         * @param mixed[] $assocArgs WP-CLI assoc args (may contain 'yes').
         */
        public static function confirm(string $question, array $assocArgs = []): void
        {
            self::$lines[] = 'confirm:' . $question;
        }

        public static function halt(int $exitCode): void
        {
            self::$exitCode = $exitCode;
        }

        public static function add_command(string $name, string $class): void
        {
            self::$commands[$name] = $class;
        }

        /** Reset captured output between tests. */
        public static function reset(): void
        {
            self::$lines     = [];
            self::$errors    = [];
            self::$successes = [];
            self::$warnings  = [];
            self::$commands  = [];
            self::$formatted = [];
            self::$exitCode  = null;
        }
    }
}

namespace WP_CLI\Utils {
    /**
     * Records the call instead of rendering a table.
     *
     * @param string $format
     * @param array  $items
     * @param array  $fields
     */
    function format_items($format, $items, $fields): void
    {
        \WP_CLI::$formatted[] = ['format' => $format, 'items' => $items, 'fields' => $fields];
    }
}

namespace cli {
    /**
     * Tests override the commands' readPassword() instead.
     *
     * @param string      $question
     * @param string|bool $default
     * @param string      $marker
     * @param bool        $hide
     */
    function prompt($question, $default = false, $marker = ': ', $hide = false): string
    {
        return '';
    }
}
