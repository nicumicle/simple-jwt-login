<?php

namespace SimpleJWTLogin\Services\Oauth;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

interface OauthInterface
{
    /**
     * @param array $request
     * @param string $requestMethod
     * @param SimpleJWTLoginSettings $settings
     * @param WordPressDataInterface $wordPressData
     */
    public function __construct($request, $requestMethod, SimpleJWTLoginSettings $settings, WordPressDataInterface $wordPressData);
    public function validate();
    /**
     * @return array
     */
    public function call();
}
