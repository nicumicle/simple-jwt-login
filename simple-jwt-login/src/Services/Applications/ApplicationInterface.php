<?php

namespace SimpleJWTLogin\Services\Applications;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

interface ApplicationInterface
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
