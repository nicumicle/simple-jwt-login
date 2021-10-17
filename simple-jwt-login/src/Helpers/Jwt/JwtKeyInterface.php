<?php

namespace SimpleJWTLogin\Helpers\Jwt;

interface JwtKeyInterface
{
    /**
     * @return string
     */
    public function getPublicKey();

    /**
     * @return mixed
     */
    public function getPrivateKey();
}
