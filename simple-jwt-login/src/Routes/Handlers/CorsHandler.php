<?php

namespace SimpleJWTLogin\Routes\Handlers;

use SimpleJWTLogin\Helpers\CorsHelper;
use SimpleJWTLogin\Modules\Settings\CorsSettings;

class CorsHandler
{
    /**
     * @var CorsSettings
     */
    protected $corsSettings;

    /**
     * @param CorsSettings $corsSettings
     */
    public function __construct($corsSettings)
    {
        $this->corsSettings = $corsSettings;
    }

    public function register()
    {
        $corsHelper = new CorsHelper();
        if ($this->corsSettings->isAllowOriginEnabled()) {
            $corsHelper->addHeader(
                'Access-Control-Allow-Origin',
                $this->corsSettings->getAllowOrigin()
            );
        }
        if ($this->corsSettings->isAllowMethodsEnabled()) {
            $corsHelper->addHeader(
                'Access-Control-Allow-Methods',
                $this->corsSettings->getAllowMethods()
            );
        }
        if ($this->corsSettings->isAllowHeadersEnabled()) {
            $corsHelper->addHeader(
                'Access-Control-Allow-Headers',
                $this->corsSettings->getAllowHeaders()
            );
        }
    }
}
