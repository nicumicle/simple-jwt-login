<?php

namespace SimpleJWTLogin\Helpers\Jwt;

class JwtKeyDecryptionKey extends JwtKeyBasic implements JwtKeyInterface
{
    /**
     * @return string
     */
    public function getPublicKey()
    {
        $key =  $this->settings->getGeneralSettings()->getDecryptionKey();
        if ($this->settings->getGeneralSettings()->isDecryptionKeyBase64Encoded()) {
            $key = base64_decode($key);
        }
        return $key;
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        $key = $this->settings->getGeneralSettings()->getDecryptionKey();
        if ($this->settings->getGeneralSettings()->isDecryptionKeyBase64Encoded()) {
            $key = base64_decode($key);
        }
        return $key;
    }
}
