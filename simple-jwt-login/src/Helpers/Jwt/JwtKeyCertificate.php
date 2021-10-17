<?php

namespace SimpleJWTLogin\Helpers\Jwt;

class JwtKeyCertificate extends JwtKeyBasic implements JwtKeyInterface
{
    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->settings->getGeneralSettings()->getDecryptionKeyPublic();
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->settings->getGeneralSettings()->getDecryptionKeyPrivate();
    }
}
