<?php
namespace SimpleJWTLogin\Helpers\Jwt;

interface JwtKeyInterface {
	/**
	 * @return string
	 */
	public function getPublicKey();

	/**
	 * @return string
	 */
	public function getPrivateKey();
}