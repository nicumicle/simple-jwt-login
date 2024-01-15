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
        $password = $this->wordPressData->generatePassword(
            $this->settings->getRegisterSettings()->getRandomPasswordLength()
        );
        $user = $this->wordPressData->createUser(
            $email,
            $email,
            $password,
            $this->settings->getRegisterSettings()->getNewUSerProfile(),
            []
        );

        return $user;
    }
}
