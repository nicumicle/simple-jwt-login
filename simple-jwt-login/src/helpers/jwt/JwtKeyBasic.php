<?php


namespace SimpleJWTLogin\Helpers\Jwt;


use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class JwtKeyBasic {
	protected $settings;

	/**
	 * JwtKeyBasic constructor.
	 *
	 * @param SimpleJWTLoginSettings $settings
	 */
	public function __construct($settings) {

		$this->settings = $settings;
	}
}