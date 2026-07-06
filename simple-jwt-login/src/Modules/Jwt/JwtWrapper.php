<?php

namespace SimpleJWTLogin\Modules\Jwt;

use Exception;
use SimpleJWTLogin\Exceptions\JWTException;
use SimpleJWTLogin\Libraries\JWT\JWT;

class JwtWrapper implements JwtInterface
{
    /**
     * @param object|array $payload
     * @param string $key
     * @param string $alg
     * @param array|null $head
     * @return string
     * @throws JWTException
     */
    public function encode($payload, $key, $alg, $head = null)
    {
        try {
            return JWT::encode($payload, $key, $alg, null, $head);
        } catch (Exception $exception) {
            throw new JWTException(esc_html($exception->getMessage()), absint($exception->getCode()));
        }
    }

    /**
     * @param string $jwt
     * @param string $key
     * @param array $allowedAlgs
     * @return object
     * @throws JWTException
     */
    public function decode($jwt, $key, array $allowedAlgs)
    {
        try {
            return JWT::decode($jwt, $key, $allowedAlgs);
        } catch (Exception $exception) {
            throw new JWTException(esc_html($exception->getMessage()), absint($exception->getCode()));
        }
    }

    /**
     * @param string $jwt
     * @return array
     * @throws JWTException
     */
    public function extractDataFromJwt($jwt)
    {
        try {
            return JWT::extractDataFromJwt($jwt);
        } catch (Exception $exception) {
            throw new JWTException(esc_html($exception->getMessage()), absint($exception->getCode()));
        }
    }

    /**
     * @param int $leeway
     * @return void
     */
    public function applyLeeway($leeway)
    {
        JWT::$leeway = $leeway;
    }
}
