<?php

namespace SimpleJWTLogin\Helpers\Jwt;

class JwtKeyWpConfig extends JwtKeyBasic implements JwtKeyInterface
{
    const SIMPLE_JWT_PRIVATE_KEY = 'SIMPLE_JWT_PRIVATE_KEY';
    const SIMPLE_JWT_PUBLIC_KEY = 'SIMPLE_JWT_PUBLIC_KEY';

    /**
     * @return mixed|string|null
     */
    public function getPublicKey()
    {
        if (strpos($this->settings->getGeneralSettings()->getJWTDecryptAlgorithm(), 'HS') !== false) {
            return $this->getPrivateKey();
        }

        return defined(self::SIMPLE_JWT_PUBLIC_KEY)
            ? constant(self::SIMPLE_JWT_PUBLIC_KEY)
            : null;
    }

    /**
     * @return mixed|string|null
     */
    public function getPrivateKey()
    {
        return defined(self::SIMPLE_JWT_PRIVATE_KEY)
            ? constant(self::SIMPLE_JWT_PRIVATE_KEY)
            : null;
    }
}
