<?php

namespace SimpleJWTLogin\Services\Oauth;

use SimpleJWTLogin\Modules\Jwt\JwtInterface;
use SimpleJWTLogin\Modules\Jwt\JwtWrapper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

class BaseOauth
{
    /**
     * @var string
     */
    protected $requestMethod;

    /**
     * @var array
     */
    protected $request = [];
    /**
     * @var WordPressDataInterface
     */
    protected $wordPressData;

    /**
     * @var SimpleJWTLoginSettings
     */
    protected $settings;

    /**
     * @var JwtInterface|null
     */
    protected $jwtWrapper;

    /**
     * @var TwoFactorBridge|null
     */
    protected $twoFactorBridge;

    /**
     * @return JwtInterface
     */
    protected function getJwtWrapper()
    {
        if ($this->jwtWrapper === null) {
            $this->jwtWrapper = new JwtWrapper();
        }

        return $this->jwtWrapper;
    }

    /**
     * @param TwoFactorBridge $bridge
     * @return $this
     */
    public function withTwoFactorBridge(TwoFactorBridge $bridge)
    {
        $this->twoFactorBridge = $bridge;
        return $this;
    }

    /**
     * @return TwoFactorBridge
     */
    protected function getTwoFactorBridge()
    {
        if ($this->twoFactorBridge === null) {
            $this->twoFactorBridge = new TwoFactorBridge();
        }
        return $this->twoFactorBridge;
    }

    /**
     * @param array $request
     * @param string $requestMethod
     * @param SimpleJWTLoginSettings $settings
     * @param WordPressDataInterface $wordPressData
     */
    public function __construct(
        $request,
        $requestMethod,
        SimpleJWTLoginSettings $settings,
        WordPressDataInterface $wordPressData
    ) {
        $this->request = $request;
        $this->wordPressData = $wordPressData;
        $this->settings = $settings;
        $this->requestMethod = $requestMethod;
    }

    /**
     * @param string $email
     * @return \WP_User
     * @throws \Exception
     */
    protected function createUser($email)
    {
        $username = 'user_' . $this->randomString(6);
        $password = $this->wordPressData->generatePassword(
            $this->settings->getRegisterSettings()->getRandomPasswordLength()
        );
        $user = $this->wordPressData->createUser(
            $username,
            $email,
            $password,
            $this->settings->getRegisterSettings()->getNewUserProfile(),
            []
        );

        return $user;
    }

    /**
     * @param int $length
     * @return string
     */
    private function randomString($length = 8)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charLength = strlen($chars);
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $charLength - 1)];
        }

        return $result;
    }
}
