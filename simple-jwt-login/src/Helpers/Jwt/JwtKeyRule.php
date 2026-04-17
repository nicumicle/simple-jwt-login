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
            return isset($this->ruleConfig['decryption_key_public'])
                ? (string)base64_decode($this->ruleConfig['decryption_key_public'])
                : '';
        }

        $key = isset($this->ruleConfig['decryption_key']) ? (string)$this->ruleConfig['decryption_key'] : '';
        if (!empty($this->ruleConfig['decryption_key_base64'])) {
            $key = (string)base64_decode($key);
        }
        return $key;
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        $algorithm = isset($this->ruleConfig['algorithm']) ? $this->ruleConfig['algorithm'] : '';
        if (strpos($algorithm, 'RS') !== false) {
            return isset($this->ruleConfig['decryption_key_private'])
                ? (string)base64_decode($this->ruleConfig['decryption_key_private'])
                : '';
        }

        $key = isset($this->ruleConfig['decryption_key']) ? (string)$this->ruleConfig['decryption_key'] : '';
        if (!empty($this->ruleConfig['decryption_key_base64'])) {
            $key = (string)base64_decode($key);
        }
        return $key;
    }
}
