<?php

namespace SimpleJWTLogin\Helpers\Jwt;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class JwtKeyBasic
{
    /**
     * @var SimpleJWTLoginSettings
     */
    protected $settings;

    /**
     * @param SimpleJWTLoginSettings $settings
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }
}
