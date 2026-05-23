<?php

namespace SimpleJWTLogin\Modules\Jwt;

interface JwtInterface
{
    /**
     * @param object|array $payload
     * @param string $key
     * @param string $alg
     * @return string
     */
    public function encode($payload, $key, $alg);

    /**
     * @param string $jwt
     * @param string $key
     * @param array $allowedAlgs
     * @return object
     */
    public function decode($jwt, $key, array $allowedAlgs);

    /**
     * @param string $jwt
     * @return array
     */
    public function extractDataFromJwt($jwt);

    /**
     * @param int $leeway
     * @return void
     */
    public function applyLeeway($leeway);
}
