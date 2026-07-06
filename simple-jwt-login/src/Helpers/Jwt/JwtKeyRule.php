<?php

namespace SimpleJWTLogin\Helpers\Jwt;

class JwtKeyRule implements JwtKeyInterface
{
    /**
     * @var array
     */
    private $ruleConfig;

    /**
     * @param array $ruleConfig
     */
    public function __construct(array $ruleConfig)
    {
        $this->ruleConfig = $ruleConfig;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        $algorithm = isset($this->ruleConfig['algorithm']) ? $this->ruleConfig['algorithm'] : '';
        if (strpos($algorithm, 'RS') !== false) {
            $pub = isset($this->ruleConfig['decryption_key_public']) ? $this->ruleConfig['decryption_key_public'] : '';
            return (string) base64_decode($pub);
        }
        return $this->getSymmetricKey();
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        $algorithm = isset($this->ruleConfig['algorithm']) ? $this->ruleConfig['algorithm'] : '';
        if (strpos($algorithm, 'RS') !== false) {
            $priv = isset($this->ruleConfig['decryption_key_private']) ? $this->ruleConfig['decryption_key_private'] : '';
            return (string) base64_decode($priv);
        }
        return $this->getSymmetricKey();
    }

    /**
     * @return string
     */
    private function getSymmetricKey()
    {
        $key = (string) (isset($this->ruleConfig['decryption_key']) ? $this->ruleConfig['decryption_key'] : '');
        if (!empty($this->ruleConfig['decryption_key_base64'])) {
            $key = (string) base64_decode($key);
        }
        return $key;
    }
}
