<?php

namespace SimpleJWTLogin\Routes\Handlers;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Helpers\StatusCodeHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\ProtectEndpointService;
use SimpleJWTLogin\Services\RouteService;

class EndpointProtectionHandler
{
    /**
     * @var RouteService
     */
    protected $routeService;
    /**
     * @var SimpleJWTLoginSettings
     */
    protected $jwtSettings;
    /**
     * @var ServerHelper
     */
    protected $serverHelper;
    /**
     * @var array
     */
    protected $request;
    /**
     * @var string
     */
    protected $documentRoot;

    /**
     * @param RouteService $routeService
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param ServerHelper $serverHelper
     * @param array $request
     * @param string $documentRoot
     */
    public function __construct($routeService, $jwtSettings, $serverHelper, $request, $documentRoot)
    {
        $this->routeService = $routeService;
        $this->jwtSettings = $jwtSettings;
        $this->serverHelper = $serverHelper;
        $this->request = $request;
        $this->documentRoot = $documentRoot;
    }

    /**
     * @param mixed $endpoint
     *
     * @return mixed
     */
    public function __invoke($endpoint)
    {
        $service = new ProtectEndpointService();
        $service
            ->withRequest($this->request)
            ->withRequestMethod($this->serverHelper->getRequestMethod())
            ->withSettings($this->jwtSettings)
            ->withServerHelper($this->serverHelper)
            ->withRouteService($this->routeService);
        if ($this->jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
            $service->withSession(simple_jwt_login_init_session());
        }

        $currentURL = esc_url_raw($this->serverHelper->getCurrentURL());
        $currentURL = str_replace(home_url(), '', $currentURL);
        $documentRoot = esc_html($this->documentRoot);

        try {
            $hasAccess = $service->hasAccess($currentURL, $documentRoot);
        } catch (Exception $exception) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            wp_send_json_error(
                [
                    'message'   => $exception->getMessage(),
                    'errorCode' => $exception->getCode(),
                ],
                StatusCodeHelper::getStatusCodeFromException($exception)
            );

            return false;
        }

        if ($hasAccess) {
            return $endpoint;
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        wp_send_json_error(
            [
                'message'   => __('You are not authorized to access this endpoint.', 'simple-jwt-login'),
                'errorCode' => ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT,
            ],
            401
        );

        return false;
    }
}
