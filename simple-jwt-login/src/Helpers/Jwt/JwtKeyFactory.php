<?php

namespace SimpleJWTLogin\Helpers\Jwt;

use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class JwtKeyFactory
{
    /**
     * Resolve the key factory for the default (ELSE) configuration.
     *
     * @param SimpleJWTLoginSettings $settings
     *
     * @return JwtKeyInterface
     */
    public static function getFactory($settings)
    {
        if ($settings->getGeneralSettings()->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_CODE) {
            return new JwtKeyWpConfig($settings);
        }

        $algorithm = $settings->getGeneralSettings()->getJWTDecryptAlgorithm();
        if (strpos($algorithm, 'RS') !== false) {
            return new JwtKeyCertificate($settings);
        }

        return new JwtKeyDecryptionKey($settings);
    }

    /**
     * Resolve the key factory by the JWT "iss" claim.
     * Falls back to the default factory when no matching rule is configured.
     *
     * @param SimpleJWTLoginSettings $settings
     * @param string|null            $iss
     *
     * @return JwtKeyInterface
     */
    public static function getFactoryForRule($settings, $iss)
    {
        if ($iss !== null) {
            $ruleConfig = $settings->getJwtRulesSettings()->findByIss($iss);
            if ($ruleConfig !== null) {
                return new JwtKeyRule($ruleConfig);
            }
        }
        return self::getFactory($settings);
    }

    /**
     * Resolve the key factory from an already-matched rule config array.
     * Accepts a nullable $ruleConfig: returns a JwtKeyRule when it is set,
     * or falls back to the default factory derived from $settings.
     *
     * Use this instead of instantiating JwtKeyRule directly, so callers
     * do not need to depend on JwtKeyRule themselves.
     *
     * @param SimpleJWTLoginSettings $settings
     * @param array|null             $ruleConfig
     *
     * @return JwtKeyInterface
     */
    public static function getFactoryFromConfig($settings, $ruleConfig)
    {
        if ($ruleConfig !== null) {
            return new JwtKeyRule($ruleConfig);
        }
        return self::getFactory($settings);
    }
}
