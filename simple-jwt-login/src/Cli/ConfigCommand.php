<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use WP_Filesystem_Direct;

/**
 * Read and write Simple JWT Login plugin configuration.
 */
class ConfigCommand extends AbstractCommand
{
    /**
     * Get the current value of a plugin setting.
     *
     * ## OPTIONS
     *
     * <key>
     * : Setting key. Use dot notation for nested settings (e.g. cors.enabled).
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt config get authorization.enabled
     *     wp jwt config get cors.enabled
     *     wp jwt config get authorization.jwt_payload --format=json
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Named arguments.
     * @return void
     */
    public function get(array $args, array $assocArgs)
    {
        if (empty($args)) {
            \WP_CLI::error(__('Please provide a setting key.', 'simple-jwt-login'));
            return;
        }

        $key = $args[0];

        try {
            $this->createSettingsLayout()->assertKey($key);
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        $settings = $this->loadSettings();
        $value = $this->getNestedValue($settings, $key);

        if ($value === null) {
            /* translators: %s is the setting key */
            \WP_CLI::error(sprintf(__('Setting "%s" not found.', 'simple-jwt-login'), $key));
            return;
        }

        $format = $this->getFormat($assocArgs);

        if ($format === self::FORMAT_JSON) {
            \WP_CLI::line((string) json_encode($value));
            return;
        }
        \WP_CLI::line(is_array($value) ? (string) json_encode($value) : (string) $value);
    }

    /**
     * Update the value of a plugin setting.
     *
     * ## OPTIONS
     *
     * <key>
     * : Setting key. Use dot notation for nested settings (e.g. cors.enabled).
     *
     * <value>
     * : New value for the setting.
     *
     * [--type=<type>]
     * : How to cast the value. Accepts 'auto', 'string', 'int', 'bool', 'json'.
     * Default: 'auto' (infers the type from the current stored value).
     *
     * [--force]
     * : Skip plugin validation and save regardless of validation errors.
     *
     * ## EXAMPLES
     *
     *     wp jwt config set authorization.enabled 1
     *     wp jwt config set cors.enabled true
     *     wp jwt config set general.decryption_key mysecretkey
     *     wp jwt config set authorization.jwt_payload '["iat","exp","id"]' --type=json
     *     wp jwt config set general.route_namespace '' --force
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Named arguments.
     * @return void
     */
    public function set(array $args, array $assocArgs)
    {
        if (count($args) < 2) {
            \WP_CLI::error(
                __('Please provide both a key and a value: wp jwt config set <key> <value>', 'simple-jwt-login')
            );
            return;
        }

        $key = $args[0];
        $rawValue = $args[1];
        $type = isset($assocArgs['type']) ? $assocArgs['type'] : 'auto';
        $force = isset($assocArgs['force']);

        $settings = $this->loadSettings();
        $currentValue = $this->getNestedValue($settings, $key);

        try {
            $value = $this->castValue($rawValue, $type, $currentValue);
            $settings = $this->setNestedValue($settings, $key, $value);
            $this->createSettingsLayout()->assertWritable($key, $settings);
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        if (!$force) {
            try {
                $this->performValidation($settings);
            } catch (Exception $exception) {
                \WP_CLI::error(__('Validation failed: ', 'simple-jwt-login') . $exception->getMessage());
                return;
            }
        }

        try {
            $this->audited('config set', function () use ($settings) {
                $this->saveSettings($settings);
            }, array('key' => $key, 'validated' => !$force));
        } catch (Exception $exception) {
            \WP_CLI::error(__('Failed to save settings: ', 'simple-jwt-login') . $exception->getMessage());
            return;
        }
        /* translators: %s is the setting key */
        \WP_CLI::success(sprintf(__('Updated "%s".', 'simple-jwt-login'), $key));
    }

    /**
     * List all plugin settings as key/value pairs.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt config list
     *     wp jwt config list --format=json
     *
     * @subcommand list
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Named arguments.
     * @return void
     */
    public function listSettings(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $settings = $this->loadSettings();
        $format = $this->getFormat($assocArgs);

        if ($format === self::FORMAT_JSON) {
            \WP_CLI::line((string) json_encode($settings, JSON_PRETTY_PRINT));
            return;
        }

        $flat = $this->flattenSettings($settings);

        foreach ($flat as $k => $v) {
            \WP_CLI::line(sprintf('%-45s %s', $k, $v));
        }
    }

    /**
     * Export the current plugin configuration to a JSON file or stdout.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : Write the JSON output to this file instead of printing to stdout.
     *
     * ## EXAMPLES
     *
     *     wp jwt config export
     *     wp jwt config export --file=jwt-config.json
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Named arguments.
     * @return void
     */
    public function export(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $settings = $this->loadSettings();
        $json = (string) json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (isset($assocArgs['file'])) {
            try {
                $this->writeFile($assocArgs['file'], $json);
            } catch (Exception $exception) {
                \WP_CLI::error($exception->getMessage());
                return;
            }
            \WP_CLI::success(sprintf(
                /* translators: %s is the file path */
                __('Configuration exported to %s', 'simple-jwt-login'),
                $assocArgs['file']
            ));
            return;
        }
        \WP_CLI::warning(
            __(
                'The exported configuration may contain sensitive values such as decryption keys. Handle with care.', // phpcs:ignore Generic.Files.LineLength
                'simple-jwt-login'
            )
        );
        \WP_CLI::line($json);
    }

    /**
     * Import plugin configuration from a JSON file.
     *
     * By default the entire settings object is replaced with the contents of
     * the file. Use --merge to only update the keys present in the file while
     * leaving all other settings untouched.
     *
     * ## OPTIONS
     *
     * <file>
     * : Path to the JSON file to import.
     *
     * [--merge]
     * : Merge the imported settings into the existing ones instead of
     * replacing them. Keys absent from the file are preserved.
     *
     * [--dry-run]
     * : Preview the changes that would be applied without saving anything.
     *
     * [--yes]
     * : Skip the confirmation prompt (useful for scripting).
     *
     * [--force]
     * : Skip plugin validation and save regardless of validation errors.
     *
     * ## EXAMPLES
     *
     *     wp jwt config import jwt-config.json
     *     wp jwt config import jwt-config.json --merge
     *     wp jwt config import jwt-config.json --dry-run
     *     wp jwt config import jwt-config.json --yes
     *     wp jwt config import jwt-config.json --force
     *
     * @param array $args Positional arguments.
     * @param array $assocArgs Named arguments.
     * @return void
     */
    public function import(array $args, array $assocArgs)
    {
        if (empty($args)) {
            \WP_CLI::error(__('Please provide a file path: wp jwt config import <file>', 'simple-jwt-login'));
            return;
        }

        $file = $args[0];
        $merge = isset($assocArgs['merge']);
        $dryRun = isset($assocArgs['dry-run']);
        $force = isset($assocArgs['force']);

        $contents = $this->readFile($file);
        if ($contents === false) {
            /* translators: %s is the file path */
            \WP_CLI::error(sprintf(__('Cannot read file: %s', 'simple-jwt-login'), $file));
            return;
        }

        $imported = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            \WP_CLI::error(
                sprintf(
                    /* translators: 1: file path, 2: JSON error message */
                    __(
                        'Invalid JSON in %1$s: %2$s',
                        'simple-jwt-login'
                    ),
                    $file,
                    json_last_error_msg()
                )
            );
            return;
        }

        if (!is_array($imported)) {
            \WP_CLI::error(__('The import file must contain a JSON object.', 'simple-jwt-login'));
            return;
        }

        try {
            $imported = $this->createSettingsLayout()->prepareImport($imported, $merge);
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        $current = $this->loadSettings();
        $target = $merge ? $this->mergeSettings($current, $imported) : $imported;
        $diff = $this->computeDiff($current, $target);

        if (empty($diff)) {
            \WP_CLI::line(__('No changes detected.', 'simple-jwt-login'));
            return;
        }

        $this->printDiff($diff, $current, $target);

        if ($dryRun) {
            \WP_CLI::line(__('Dry run - no changes were saved.', 'simple-jwt-login'));
            return;
        }

        if (!$force) {
            try {
                $this->performValidation($target);
            } catch (Exception $exception) {
                \WP_CLI::error(__('Validation failed: ', 'simple-jwt-login') . $exception->getMessage());
                return;
            }
        }

        $this->confirmAction('Apply these changes?', $assocArgs);
        try {
            $this->audited('config import', function () use ($target) {
                $this->saveSettings($target);
            }, array('keys' => array_column($diff, 'key'), 'validated' => !$force));
        } catch (Exception $exception) {
            \WP_CLI::error(__('Failed to save settings: ', 'simple-jwt-login') . $exception->getMessage());
            return;
        }
        \WP_CLI::success(__('Configuration imported successfully.', 'simple-jwt-login'));
    }

    // -------------------------------------------------------------------------
    // Data helpers (public for direct testing)
    // -------------------------------------------------------------------------

    /**
     * Retrieve a value from the settings array using dot notation.
     *
     * @param array  $settings Full settings array.
     * @param string $key      Dot-notation key (e.g. "cors.enabled").
     * @return mixed|null Null when the key does not exist.
     */
    protected function getNestedValue(array $settings, $key)
    {
        $value = $settings;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !isset($value[$part])) {
                return null;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Write a value into the settings array using dot notation and return the
     * updated array (immutable-style).
     *
     * @param array  $settings Full settings array.
     * @param string $key      Dot-notation key (e.g. "cors.enabled").
     * @param mixed  $value    Value to store.
     * @return array Updated settings array.
     */
    protected function setNestedValue(array $settings, $key, $value)
    {
        $updated = $settings;
        $parts = explode('.', $key, 2);

        if (count($parts) === 1) {
            $updated[$key] = $value;
            return $updated;
        }

        list($group, $name) = $parts;

        if (!isset($updated[$group]) || !is_array($updated[$group])) {
            $updated[$group] = array();
        }

        $updated[$group] = $this->setNestedValue($updated[$group], $name, $value);

        return $updated;
    }

    /**
     * Merge settings. Lists are replaced, not merged by index.
     *
     * @param array $current
     * @param array $imported
     * @return array
     */
    protected function mergeSettings(array $current, array $imported)
    {
        $merged = $current;
        foreach ($imported as $key => $value) {
            $old = isset($current[$key]) ? $current[$key] : null;
            // Two associative arrays. An empty one decodes the same from {} and []: it clears only a list.
            $nested = is_array($old) && is_array($value)
                && $old !== array() && !isset($old[0]) && !isset($value[0]);
            $merged[$key] = $nested ? $this->mergeSettings($old, $value) : $value;
        }

        return $merged;
    }

    /**
     * Cast a raw string value to the appropriate PHP type.
     *
     * @param string $rawValue The raw string provided by the user.
     * @param string $type     Explicit type hint ('auto','string','int','bool','json').
     * @param mixed  $current  Current stored value used for 'auto' detection.
     * @return mixed Typed value.
     * @throws Exception When JSON decoding fails or an unsupported type is given.
     */
    protected function castValue($rawValue, $type, $current)
    {
        switch ($type) {
            case 'int':
                return (int) $rawValue;

            case 'bool':
                return $this->parseBool($rawValue);

            case 'json':
                $decoded = json_decode($rawValue, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception(
                        sprintf(
                            /* translators: %s is the JSON error message */
                            esc_html__(
                                'Invalid JSON value: %s',
                                'simple-jwt-login'
                            ),
                            esc_html(json_last_error_msg())
                        )
                    );
                }
                return $decoded;

            case 'string':
                return $rawValue;

            case 'auto':
            default:
                if ($current !== null) {
                    if (is_bool($current)) {
                        return $this->parseBool($rawValue);
                    }
                    if (is_int($current)) {
                        return (int) $rawValue;
                    }
                    if (is_array($current)) {
                        $decoded = json_decode($rawValue, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new Exception(
                                esc_html__(
                                    'The current value is an array. Provide a JSON string or pass --type=json.',
                                    'simple-jwt-login'
                                )
                            );
                        }
                        return $decoded;
                    }
                }
                return $rawValue;
        }
    }

    /**
     * Flatten a settings array to one level using dot notation for nested keys.
     * Indexed arrays are serialised to a JSON string.
     *
     * @param array $settings Full settings array.
     * @return array Flat key => value map.
     */
    protected function flattenSettings(array $settings)
    {
        $result = array();

        foreach ($settings as $key => $value) {
            if (is_array($value) && !empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
                // Associative sub-array: flatten one level with dot notation
                foreach ($value as $subKey => $subValue) {
                    $result[$key . '.' . $subKey] = is_array($subValue)
                        ? (string) json_encode($subValue)
                        : $subValue;
                }
                continue;
            }
            $result[$key] = is_array($value) ? (string) json_encode($value) : $value;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // WordPress I/O (protected so tests can override via subclass)
    // -------------------------------------------------------------------------

    /**
     * @return SettingsLayout
     */
    protected function createSettingsLayout()
    {
        return new SettingsLayout();
    }

    /**
     * Load the settings array from the database, migrated to the current layout.
     *
     * @return array
     */
    protected function loadSettings()
    {
        $wordPressData = $this->createWordPressData();
        $data = $wordPressData->getOptionFromDatabase(SimpleJWTLoginSettings::OPTIONS_KEY);

        $decoded = empty($data) ? array() : json_decode($data, true);

        return $this->createSettingsLayout()->normalize(is_array($decoded) ? $decoded : array());
    }

    /**
     * Persist the settings array to the database.
     *
     * @param array $settings Settings to save.
     * @return void
     */
    protected function saveSettings(array $settings)
    {
        $wordPressData = $this->createWordPressData();
        $existing = $wordPressData->getOptionFromDatabase(SimpleJWTLoginSettings::OPTIONS_KEY);

        if ($existing !== false) {
            $wordPressData->updateOption(
                SimpleJWTLoginSettings::OPTIONS_KEY,
                (string) wp_json_encode($settings)
            );
            return;
        }
        $wordPressData->addOption(
            SimpleJWTLoginSettings::OPTIONS_KEY,
            (string) wp_json_encode($settings)
        );
    }

    /**
     * Compute a flat diff between two settings arrays.
     *
     * Each entry has keys: 'key', 'status' ('added'|'changed'|'removed'),
     * 'old', 'new'.
     *
     * @param array $current  Currently stored settings.
     * @param array $target   Settings that would be saved.
     * @return array
     */
    protected function computeDiff(array $current, array $target)
    {
        $flatCurrent = $this->flattenSettings($current);
        $flatTarget = $this->flattenSettings($target);
        $diff = array();

        foreach ($flatTarget as $key => $newValue) {
            if (!array_key_exists($key, $flatCurrent)) {
                $diff[] = array('key' => $key, 'status' => 'added', 'old' => null, 'new' => $newValue);
            } elseif (json_encode($flatCurrent[$key]) !== json_encode($newValue)) {
                $diff[] = array('key' => $key, 'status' => 'changed', 'old' => $flatCurrent[$key], 'new' => $newValue);
            }
        }

        foreach ($flatCurrent as $key => $oldValue) {
            if (!array_key_exists($key, $flatTarget)) {
                $diff[] = array('key' => $key, 'status' => 'removed', 'old' => $oldValue, 'new' => null);
            }
        }

        return $diff;
    }

    // -------------------------------------------------------------------------
    // I/O helpers (protected so tests can override via subclass)
    // -------------------------------------------------------------------------

    /**
     * Validate a settings array through all plugin parsers.
     *
     * @param array $settings Settings to validate.
     * @return void
     * @throws Exception When validation fails.
     */
    protected function performValidation(array $settings)
    {
        $this->createSettings()->validateSettingsArray($settings);
    }

    /**
     * Read a file and return its contents, or false on failure.
     *
     * @param string $path
     * @return string|false
     */
    protected function readFile($path)
    {
        // Prevent directory traversal
        if (strpos($path, '..') !== false) {
            return false;
        }

        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            return false;
        }

        return (string) file_get_contents($realPath);
    }

    /**
     * Write content to a file.
     *
     * @param string $path
     * @param string $content
     * @return void
     * @throws \Exception When the path is invalid or the write fails.
     */
    protected function writeFile($path, $content)
    {
        // Prevent directory traversal
        if (strpos($path, '..') !== false) {
            throw new Exception(
                esc_html__('Invalid file path: directory traversal is not allowed.', 'simple-jwt-login')
            );
        }

        if (!class_exists('WP_Filesystem_Direct')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
        }
        $filesystem = new WP_Filesystem_Direct(null);

        // The export holds the signing keys: owner-only, before the content is written (also for an existing file).
        $written = $filesystem->put_contents($path, '', 0600)
            && $filesystem->chmod($path, 0600)
            && $filesystem->put_contents($path, $content, 0600);
        if (!$written) {
            throw new Exception(
                /* translators: %s is the file path */
                sprintf(esc_html__('Failed to write file: %s', 'simple-jwt-login'), esc_html($path))
            );
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Print a diff to the console.
     *
     * Secrets are masked: the output often ends up in deploy logs.
     *
     * @param array $diff    From computeDiff().
     * @param array $current
     * @param array $target
     * @return void
     */
    private function printDiff(array $diff, array $current, array $target)
    {
        $redactor = new SettingsRedactor(new AbilitySettingsPolicy());
        $old = $this->flattenSettings($redactor->redact($current));
        $new = $this->flattenSettings($redactor->redact($target));
        foreach ($diff as $entry) {
            $name = $entry['key'];
            $key = sprintf('%-45s', $name);
            switch ($entry['status']) {
                case 'added':
                    \WP_CLI::line(sprintf('[+] %s %s', $key, $new[$name]));
                    break;
                case 'removed':
                    \WP_CLI::line(sprintf('[-] %s %s', $key, $old[$name]));
                    break;
                case 'changed':
                    \WP_CLI::line(sprintf('[~] %s %s -> %s', $key, $old[$name], $new[$name]));
                    break;
            }
        }
    }

    /**
     * @param string $value
     * @return bool
     */
    private function parseBool($value)
    {
        return in_array(strtolower($value), array('1', 'true', 'yes', 'on'), true);
    }
}
