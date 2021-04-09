<?php


namespace SimpleJWTLogin\Modules;


class CorsService {

	const DEFAULT_HEADER_PARAMETER = '*';
	const DEFAULT_METHODS = 'GET, POST, PUT, DELETE, OPTIONS, HEAD';
	/**
	 * @var array
	 */
	private $settings;

	/**
	 * CorsService constructor.
	 *
	 * @param SimpleJWTLoginSettings  $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings->getSettingsAsArray();
	}

	/**
	 * @return bool
	 */
	public function isCorsEnabled() {
		return isset( $this->settings['cors'] ) && ! empty( $this->settings['cors']['enabled'] );
	}

	/**
	 * @return bool
	 */
	public function isAllowOriginEnabled() {
		return isset( $this->settings['cors'] )
		       && isset($this->settings['cors']['allow_origin_enabled'])
		       && filter_var($this->settings['cors']['allow_origin_enabled'],FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * @return string
	 */
	public function getAllowOrigin() {
		return isset( $this->settings['cors'] ) && isset( $this->settings['cors']['allow_origin'] )
			? $this->settings['cors']['allow_origin']
			: self::DEFAULT_HEADER_PARAMETER;
	}

	/**
	 * @return bool
	 */
	public function isAllowHeadersEnabled() {
		return isset( $this->settings['cors'] )
		       && isset( $this->settings['cors']['allow_headers_enabled'] )
		       && filter_var($this->settings['cors']['allow_headers_enabled'],FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * @return string
	 */
	public function getAllowHeaders() {
		return isset( $this->settings['cors'] ) && isset( $this->settings['cors']['allow_headers'] )
			? $this->settings['cors']['allow_headers']
			: self::DEFAULT_HEADER_PARAMETER;
	}

	/**
	 * @return bool
	 */
	public function isAllowMethodsEnabled() {
		return isset( $this->settings['cors'] )
		       && isset( $this->settings['cors']['allow_methods_enabled'] )
		       && filter_var($this->settings['cors']['allow_methods_enabled'], FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * @return string
	 */
	public function getAllowMethods() {
		return isset( $this->settings['cors'] ) && isset( $this->settings['cors']['allow_methods'] )
			? $this->settings['cors']['allow_methods']
			: self::DEFAULT_METHODS;
	}

	/**
	 * @codeCoverageIgnore
	 * @param string $headerName
	 * @param string $value
	 */
	public function addHeader( $headerName, $value ) {
		header( $headerName . ": " . $value );
	}


}
