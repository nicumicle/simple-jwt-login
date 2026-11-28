<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Helpers\Jwt\KeyRotation;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Services\RouteService;

/**
 * First setup: a signing key and the /auth endpoint.
 */
class SetupCommand extends AbstractCommand
{
    /**
     * Get ready to issue JWTs: generate a signing key when there is none, enable the /auth
     * endpoint, then print its URL and a sample request.
     *
     * ## OPTIONS
     *
     * [--algorithm=<algorithm>]
     * : HS256, HS384, HS512, RS256, RS384, RS512, or random to pick one. Default: the configured
     * one. A different algorithm gets a new key.
     *
     * [--force]
     * : Generate a new key even when there is one. Issued tokens keep working until they expire,
     * like with `wp jwt key rotate`.
     *
     * ## EXAMPLES
     *
     *     wp jwt setup
     *     wp jwt setup --algorithm=RS256 --force
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function __invoke(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $jwtSettings = $this->createSettings();
        $general = $jwtSettings->getGeneralSettings();
        $algorithm = (new SigningKeys())->resolveAlgorithm(
            isset($assocArgs['algorithm']) ? $assocArgs['algorithm'] : null,
            $general->getJWTDecryptAlgorithm()
        );
        if (!in_array($algorithm, SigningKeys::algorithms(), true)) {
            /* translators: %s is the algorithm */
            \WP_CLI::error(sprintf(__('Unsupported algorithm: %s', 'simple-jwt-login'), $algorithm));
            return;
        }
        $fromCode = $general->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_CODE;
        $newAlgorithm = $algorithm !== $general->getJWTDecryptAlgorithm();
        if ($newAlgorithm && $fromCode) {
            \WP_CLI::error(__('The signing key is defined in wp-config.php. Change the algorithm there.', 'simple-jwt-login'));
            return;
        }
        // A new algorithm always gets a new key: the old one may be too short for it.
        $newKey = !$fromCode
            && (isset($assocArgs['force']) || $newAlgorithm || !$this->hasKey($jwtSettings->getStoredSettings(), $algorithm));

        try {
            $this->audited('setup', function () use ($jwtSettings, $algorithm, $newKey) {
                $settings = $jwtSettings->getStoredSettings();
                if ($newKey) {
                    // Like `wp jwt key rotate`: tokens signed with the replaced key keep working until they expire.
                    $now = time();
                    $settings = KeyRotation::keepCurrentKey(
                        KeyRotation::removeExpired($settings, KeyRotation::graceSeconds($jwtSettings), $now),
                        $jwtSettings,
                        $now
                    );
                    $signingKeys = new SigningKeys();
                    $settings = $signingKeys->apply($settings, $algorithm, $signingKeys->generate($algorithm));
                }
                $settings['authorization']['enabled'] = '1';
                $jwtSettings->saveSettingsArray($settings);
            }, array('new_key' => $newKey, 'algorithm' => $algorithm));
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }

        $url = rtrim((string) $this->createWordPressData()->getSiteUrl(), '/') . '/?rest_route=/'
            . trim($jwtSettings->getGeneralSettings()->getRouteNamespace(), '/') . '/' . RouteService::AUTHENTICATION_ROUTE;
        \WP_CLI::line($newKey
            /* translators: %s is the algorithm */
            ? sprintf(__('Generated a new %s signing key.', 'simple-jwt-login'), $algorithm)
            /* translators: %s is the algorithm */
            : sprintf(__('Kept the existing %s signing key.', 'simple-jwt-login'), $algorithm));
        \WP_CLI::line(sprintf("curl -X POST '%s' -d 'username=<user>' -d 'password=<password>'", $url));
        \WP_CLI::line(__('Or, from here: wp jwt login --login=<user>', 'simple-jwt-login'));
        \WP_CLI::success(__('The /auth endpoint is enabled.', 'simple-jwt-login'));
    }

    /**
     * @param array  $settings
     * @param string $algorithm
     * @return bool
     */
    protected function hasKey(array $settings, $algorithm)
    {
        $general = isset($settings['general']) ? $settings['general'] : array();
        $fields = strpos($algorithm, 'RS') === 0
            ? array('decryption_key_private', 'decryption_key_public')
            : array('decryption_key');
        foreach ($fields as $field) {
            if (empty($general[$field])) {
                return false;
            }
        }

        return true;
    }
}
