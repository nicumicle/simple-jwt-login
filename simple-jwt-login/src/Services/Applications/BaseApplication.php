<?php

namespace SimpleJWTLogin\Services\Applications;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class BaseApplication
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
        $username = "user_" . $this->randomString(6);
        $password = $this->wordPressData->generatePassword(
            $this->settings->getRegisterSettings()->getRandomPasswordLength()
        );
        $user = $this->wordPressData->createUser(
            $username,
            $email,
            $password,
            $this->settings->getRegisterSettings()->getNewUSerProfile(),
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
            $result .= $chars[rand(0, $charLength - 1)];
        }

        return $result;
    }
}
