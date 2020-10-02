<?php
namespace SimpleJWTLogin\Helpers\Jwt;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class JwtKeyFactory {

	/**
	 * @param string $algorithm
	 * @param SimpleJWTLoginSettings $settings
	 *
	 * @return JwtKeyInterface
	 */
	public static function getFactory($settings){
	    if($settings->getDecryptionSource() === SimpleJWTLoginSettings::DECRYPTION_SOURCE_CODE){
	        return new JwtKeyWpConfig($settings);
        }

		$algorithm = $settings->getJWTDecryptAlgorithm();
		if(strpos($algorithm,'RS') !== false){
			return new JwtKeyCertificate($settings);
		}
		return new JwtKeyDecryptionKey($settings);
	}
}