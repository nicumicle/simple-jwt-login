<?php

namespace SimpleJWTLogin\Routes\Handlers;

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\CorsSettings;

class CorsHandler
{
    /**
     * Runs right after WordPress' rest_send_cors_headers() (default priority 10),
     * which reflects any request origin, so the configured values take precedence.
     */
    const HOOK_PRIORITY = 11;

    /**
     * @var CorsSettings
     */
    protected $corsSettings;

    /**
     * @var ServerHelper
     */
    protected $serverHelper;

    /**
     * @param CorsSettings $corsSettings
     * @param ServerHelper $serverHelper
     */
    public function __construct($corsSettings, $serverHelper)
    {
        $this->corsSettings = $corsSettings;
        $this->serverHelper = $serverHelper;
    }

    public function register()
    {
        add_filter('rest_pre_serve_request', $this, self::HOOK_PRIORITY);
    }

    /**
     * @param mixed $served Whether the request has already been served.
     *
     * @return mixed
     */
    public function __invoke($served)
    {
        if ($this->corsSettings->isAllowOriginEnabled()) {
            $this->sendAllowOrigin();
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

        return $served;
    }

    protected function sendAllowOrigin()
    {
        if (empty($this->corsSettings->getAllowedOrigins())) {
            return;
        }

        $allowOrigin = $this->corsSettings->resolveAllowOrigin(
            $this->serverHelper->getHeader('Origin')
        );
        if ($allowOrigin === null) {
            // Drop the origin WordPress reflected, so browsers block origins
            // that are not in the configured list.
            $this->removeHeader('Access-Control-Allow-Origin');
            $this->removeHeader('Access-Control-Allow-Credentials');

            return;
        }

        $this->addHeader('Access-Control-Allow-Origin', $allowOrigin);
    }

    /**
     * @param string $headerName
     * @param string $value
     */
    protected function addHeader($headerName, $value)
    {
        $safeValue = str_replace(array("\r", "\n"), '', $value);
        $this->sendHeader($headerName . ': ' . $safeValue);
    }

    /**
     * @param string $header
     */
    protected function sendHeader($header)
    {
        header($header);
    }

    /**
     * @param string $headerName
     */
    protected function removeHeader($headerName)
    {
        header_remove($headerName);
    }
}
