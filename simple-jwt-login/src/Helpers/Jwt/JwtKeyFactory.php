<?php

namespace SimpleJWTLogin\Helpers\Jwt;

use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class JwtKeyFactory
{
    /**
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
}
