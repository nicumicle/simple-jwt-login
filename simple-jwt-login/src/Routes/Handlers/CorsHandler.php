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
        if ($this->corsSettings->isAllowOriginEnabled()) {
            $origin = $this->corsSettings->getAllowOrigin();
            if ($origin !== '') {
                $this->addHeader('Access-Control-Allow-Origin', $origin);
            }
        }
        if ($this->corsSettings->isAllowMethodsEnabled()) {
            $this->addHeader(
                'Access-Control-Allow-Methods',
                $this->corsSettings->getAllowMethods()
            );
        }
        if ($this->corsSettings->isAllowHeadersEnabled()) {
            $this->addHeader(
                'Access-Control-Allow-Headers',
                $this->corsSettings->getAllowHeaders()
            );
        }
    }

    /**
     * @param string $headerName
     * @param string $value
     */
    private function addHeader($headerName, $value)
    {
        $safeValue = str_replace(array("\r", "\n"), '', $value);
        header($headerName . ': ' . $safeValue);
    }
}
