<?php

namespace SimpleJWTLogin\Cli;

use Exception;

/**
 * Manage auth codes.
 */
class AuthCodeCommand extends AbstractCommand
{
    /**
     * List the auth codes. Codes are hidden unless --show is passed.
     *
     * ## OPTIONS
     *
     * [--show]
     * : Show the codes.
     *
     * [--fields=<fields>]
     * : Comma separated fields to show.
     *
     * [--format=<format>]
     * : table, csv, json, yaml or count. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp jwt auth-code list
     *
     * @subcommand list
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function listCodes(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $items = array();
        foreach ($this->createSettings()->getAuthCodesSettings()->getAuthCodes() as $index => $code) {
            $value = isset($code['code']) ? (string) $code['code'] : '';
            $items[] = array(
                'index'           => $index,
                'code'            => isset($assocArgs['show']) ? $value : $this->mask($value),
                'role'            => isset($code['role']) ? $code['role'] : '',
                'expiration_date' => isset($code['expiration_date']) ? $code['expiration_date'] : '',
            );
        }
        $this->outputItems($items, $assocArgs, array('index', 'code', 'role', 'expiration_date'));
    }

    /**
     * Add an auth code. Without <code>, a random one is generated and printed.
     *
     * ## OPTIONS
     *
     * [<code>]
     * : The code.
     *
     * [--role=<role>]
     * : Role of the users registered with this code. Default: the register setting.
     *
     * [--expires=<date>]
     * : Expiration date, for example 2027-01-31 23:59:59. Default: never.
     *
     * ## EXAMPLES
     *
     *     wp jwt auth-code add --role=subscriber
     *     wp jwt auth-code add MY_CODE --expires="2027-01-31 23:59:59"
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function add(array $args, array $assocArgs)
    {
        $wordPressData = $this->createWordPressData();
        $generated = empty($args);
        // Sanitized like the admin form: saveSettingsArray() stores the values as they are.
        $code = $generated
            ? bin2hex(openssl_random_pseudo_bytes(16))
            : trim((string) $wordPressData->sanitizeTextField((string) $args[0]));
        if ($code === '') {
            \WP_CLI::error(__('The code can not be empty.', 'simple-jwt-login'));
            return;
        }
        $expires = isset($assocArgs['expires']) ? (string) $wordPressData->sanitizeTextField($assocArgs['expires']) : '';
        if ($expires !== '' && strtotime($expires) === false) {
            \WP_CLI::error(__('--expires is not a valid date.', 'simple-jwt-login'));
            return;
        }
        $role = isset($assocArgs['role']) ? (string) $wordPressData->sanitizeTextField($assocArgs['role']) : '';

        $this->saveCodes('auth-code add', function (array $codes) use ($code, $role, $expires) {
            $codes[] = array('code' => $code, 'role' => $role, 'expiration_date' => $expires);
            return $codes;
        }, array('role' => $role, 'expiration_date' => $expires));

        if ($generated) {
            \WP_CLI::line($code);
        }
        \WP_CLI::success(__('Auth code added.', 'simple-jwt-login'));
    }

    /**
     * Remove an auth code.
     *
     * ## OPTIONS
     *
     * <index>
     * : The index (from `wp jwt auth-code list`).
     *
     * [--yes]
     * : Do not ask for confirmation.
     *
     * ## EXAMPLES
     *
     *     wp jwt auth-code remove 0 --yes
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function remove(array $args, array $assocArgs)
    {
        $codes = $this->createSettings()->getAuthCodesSettings()->getAuthCodes();
        $index = isset($args[0]) && ctype_digit((string) $args[0]) ? (int) $args[0] : -1;
        if (!isset($codes[$index])) {
            \WP_CLI::error(__('Auth code not found. See wp jwt auth-code list.', 'simple-jwt-login'));
            return;
        }
        $this->confirmAction(__('Requests that send this auth code will fail. Remove it?', 'simple-jwt-login'), $assocArgs);

        $this->saveCodes('auth-code remove', function (array $codes) use ($index) {
            unset($codes[$index]);
            return array_values($codes);
        }, array('index' => $index));
        \WP_CLI::success(__('Auth code removed.', 'simple-jwt-login'));
    }

    /**
     * The first 3 characters of long codes only: a short code would be shown almost entirely.
     *
     * @param string $code
     * @return string
     */
    protected function mask($code)
    {
        return strlen($code) > 8 ? substr($code, 0, 3) . '****' : '****';
    }

    /**
     * Save the changed list, with the admin form's validation. Stops on error.
     *
     * @param string   $command
     * @param callable $change  Takes and returns the list of codes.
     * @param array    $details
     * @return void
     */
    protected function saveCodes($command, $change, array $details)
    {
        $jwtSettings = $this->createSettings();
        try {
            $this->audited($command, function () use ($jwtSettings, $change) {
                $settings = $jwtSettings->getStoredSettings();
                $current = isset($settings['auth_codes']['codes']) ? (array) $settings['auth_codes']['codes'] : array();
                $settings['auth_codes']['codes'] = call_user_func($change, $current);
                $jwtSettings->saveSettingsArray($settings);
            }, $details);
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
        }
    }
}
