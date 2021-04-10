<?php


namespace SimpleJWTLogin\Helpers\Jwt;

class JwtKeyDecryptionKey extends JwtKeyBasic implements JwtKeyInterface
{
    /**
     * @return string
     */
    public function getPublicKey()
    {
        $key =  $this->settings->getDecryptionKey();
        if ($this->settings->getDecryptionKeyIsBase64Encoded()) {
            $key = base64_decode($key);
        }
        return $key;
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        $key = $this->settings->getDecryptionKey();
        if ($this->settings->getDecryptionKeyIsBase64Encoded()) {
            $key = base64_decode($key);
        }
        return $key;
    }
}
