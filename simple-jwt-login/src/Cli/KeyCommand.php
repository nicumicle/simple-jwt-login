<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Helpers\Jwt\KeyRotation;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;

/**
 * Generate and rotate the JWT signing key.
 */
class KeyCommand extends AbstractCommand
{
    /**
     * Print a new random signing key, without saving it.
     *
     * ## OPTIONS
     *
     * [--algorithm=<algorithm>]
     * : HS256, HS384, HS512, RS256, RS384, RS512 or random. Default: the configured algorithm.
     *
     * [--format=<format>]
     * : Output format. Accepts 'text' or 'json'. Default: 'text'.
     *
     * ## EXAMPLES
     *
     *     wp jwt key generate --algorithm=HS512
     *     wp jwt key generate --algorithm=RS256 --format=json
     *     wp jwt key generate --algorithm=random --format=json
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function generate(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $format = $this->getFormat($assocArgs);
        $algorithm = $this->algorithm($assocArgs);
        try {
            $keys = $this->createSigningKeys()->generate($algorithm);
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        if ($format === self::FORMAT_TEXT) {
            if (isset($assocArgs['algorithm']) && strtolower($assocArgs['algorithm']) === 'random') {
                /* translators: %s is the algorithm */
                \WP_CLI::line(sprintf(__('Algorithm: %s', 'simple-jwt-login'), $algorithm));
            }
            \WP_CLI::line(implode("\n", $keys));
            return;
        }
        $this->outputKeyValue(array_merge(array('algorithm' => $algorithm), $keys), $format);
    }

    /**
     * Replace the signing key in the settings with a new random one, without breaking issued tokens.
     *
     * New tokens are signed with the new key. The old key moves to a JWT Rule (`iat` less than the
     * rotation time), so tokens issued before keep working until they expire. The rule is removed by
     * the daily cleanup once the JWT lifetime (Authentication > JWT TTL) has passed. Refresh tokens
     * have their own key and are not affected. Other JWT Rules are not changed.
     *
     * For a leaked key, use --immediate: every token signed with the old (or an earlier) key stops
     * working at once.
     *
     * ## OPTIONS
     *
     * [--algorithm=<algorithm>]
     * : Also change the algorithm: HS256, HS384, HS512, RS256, RS384, RS512, or random to pick one.
     *
     * [--immediate]
     * : Do not keep the old key: issued tokens stop working now. Also removes keys kept by earlier rotations.
     *
     * [--show]
     * : Print the new key.
     *
     * [--yes]
     * : Do not ask for confirmation.
     *
     * ## EXAMPLES
     *
     *     wp jwt key rotate --yes
     *     wp jwt key rotate --algorithm=RS256 --show
     *     wp jwt key rotate --algorithm=random --yes
     *     wp jwt key rotate --immediate --yes
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function rotate(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $jwtSettings = $this->createSettings();
        if ($jwtSettings->getGeneralSettings()->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_CODE) {
            \WP_CLI::error(__('The signing key is defined in wp-config.php. Change it there.', 'simple-jwt-login'));
            return;
        }
        $algorithm = $this->algorithm($assocArgs);
        if (!in_array($algorithm, SigningKeys::algorithms(), true)) {
            /* translators: %s is the algorithm */
            \WP_CLI::error(sprintf(__('Unsupported algorithm: %s', 'simple-jwt-login'), $algorithm));
            return;
        }
        $immediate = isset($assocArgs['immediate']);
        $graceSeconds = KeyRotation::graceSeconds($jwtSettings);
        $this->confirmAction($immediate
            ? __('Every JWT signed with the current or an earlier key stops working now. Continue?', 'simple-jwt-login')
            : sprintf(
                /* translators: %d is a number of minutes */
                __('Tokens signed with the current key keep working for %d more minutes, until they expire. Continue?', 'simple-jwt-login'),
                (int) ceil($graceSeconds / 60)
            ), $assocArgs);

        try {
            $signingKeys = $this->createSigningKeys();
            $keys = $signingKeys->generate($algorithm);
            $now = $this->now();
            $this->audited('key rotate', function () use ($jwtSettings, $signingKeys, $algorithm, $keys, $immediate, $graceSeconds, $now) {
                $settings = $jwtSettings->getStoredSettings();
                $settings = $immediate
                    ? KeyRotation::removeAll($settings)
                    : KeyRotation::keepCurrentKey(KeyRotation::removeExpired($settings, $graceSeconds, $now), $jwtSettings, $now);
                $jwtSettings->saveSettingsArray($signingKeys->apply($settings, $algorithm, $keys));
            }, array('algorithm' => $algorithm, 'immediate' => $immediate));
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        if (isset($assocArgs['show'])) {
            \WP_CLI::line(implode("\n", $keys));
        }
        /* translators: %s is the algorithm */
        \WP_CLI::success(sprintf(__('The signing key has been rotated (%s).', 'simple-jwt-login'), $algorithm));
    }

    /**
     * @return int
     */
    protected function now()
    {
        return time();
    }

    /**
     * @param array $assocArgs
     * @return string
     */
    protected function algorithm(array $assocArgs)
    {
        return $this->createSigningKeys()->resolveAlgorithm(
            isset($assocArgs['algorithm']) ? $assocArgs['algorithm'] : null,
            $this->createSettings()->getGeneralSettings()->getJWTDecryptAlgorithm()
        );
    }

    /**
     * @return SigningKeys
     */
    protected function createSigningKeys()
    {
        return new SigningKeys();
    }
}
