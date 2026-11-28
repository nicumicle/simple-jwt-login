<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;

/**
 * Generates JWT signing keys and puts them in the settings array.
 */
class SigningKeys
{
    /**
     * Random bytes per HMAC algorithm: as long as the hash, the minimum the security report accepts.
     *
     * @var array<string, int>
     */
    private static $hmacBytes = array('HS256' => 32, 'HS384' => 48, 'HS512' => 64);

    /**
     * @var array<string, int>
     */
    private static $rsaBits = array('RS256' => 2048, 'RS384' => 3072, 'RS512' => 4096);

    /**
     * @param string $algorithm
     * @return array<string, string> decryption_key, or decryption_key_private + decryption_key_public (PEM)
     * @throws Exception
     */
    public function generate($algorithm)
    {
        if (isset(self::$hmacBytes[$algorithm])) {
            return array('decryption_key' => bin2hex(openssl_random_pseudo_bytes(self::$hmacBytes[$algorithm])));
        }
        if (!isset(self::$rsaBits[$algorithm])) {
            throw new Exception(sprintf(
                /* translators: %s is the algorithm */
                esc_html__('Unsupported algorithm: %s', 'simple-jwt-login'),
                esc_html($algorithm)
            ));
        }

        $resource = openssl_pkey_new(array(
            'private_key_bits' => self::$rsaBits[$algorithm],
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ));
        $privateKey = '';
        if ($resource === false || !openssl_pkey_export($resource, $privateKey)) {
            throw new Exception(esc_html__('OpenSSL could not generate an RSA key.', 'simple-jwt-login'));
        }
        $details = openssl_pkey_get_details($resource);

        return array('decryption_key_private' => $privateKey, 'decryption_key_public' => $details['key']);
    }

    /**
     * New settings array with the algorithm and keys of the plugin settings replaced.
     *
     * @param array                 $settings Stored settings.
     * @param string                $algorithm
     * @param array<string, string> $keys From generate().
     * @return array
     */
    public function apply(array $settings, $algorithm, array $keys)
    {
        $general = isset($settings['general']) ? $settings['general'] : array();
        $general['jwt_algorithm'] = $algorithm;
        $general['decryption_source'] = GeneralSettings::DECRYPTION_SOURCE_SETTINGS;
        if (isset($keys['decryption_key'])) {
            $general['decryption_key'] = $keys['decryption_key'];
            $general['decryption_key_base64'] = false;
        }
        if (isset($keys['decryption_key_private'])) {
            // Stored base64 encoded, like the admin form saves them.
            $general['decryption_key_private'] = base64_encode($keys['decryption_key_private']);
            $general['decryption_key_public'] = base64_encode($keys['decryption_key_public']);
        }
        $settings['general'] = $general;

        return $settings;
    }

    /**
     * The algorithm option of the commands: "random" picks one of algorithms().
     *
     * @param string|null $requested As typed; null for the configured one.
     * @param string      $configured
     * @return string Upper case; not validated.
     */
    public function resolveAlgorithm($requested, $configured)
    {
        if ($requested === null) {
            return $configured;
        }
        $requested = strtoupper((string) $requested);
        if ($requested !== 'RANDOM') {
            return $requested;
        }
        $algorithms = self::algorithms();

        return $algorithms[ord(openssl_random_pseudo_bytes(1)) % count($algorithms)];
    }

    /**
     * @return string[]
     */
    public static function algorithms()
    {
        return array_merge(array_keys(self::$hmacBytes), array_keys(self::$rsaBits));
    }
}
