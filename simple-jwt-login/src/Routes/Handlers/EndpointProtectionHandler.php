<?php

namespace SimpleJWTLogin\Routes\Handlers;

use SimpleJWTLogin\Routes\SessionService;
use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\RestErrorResponse;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Services\ProtectEndpointService;
use SimpleJWTLogin\Services\RouteService;
use WP_REST_Request;

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
     * @var ApiKeyRepositoryInterface|null
     */
    protected $apiKeyRepository;
    /**
     * @var RevokedTokenRepositoryInterface
     */
    protected $revokedTokenRepo;
    /**
     * "METHOD route" pairs already granted in this PHP request, so the same dispatch is
     * not checked twice. Kept per route: /batch/v1 and _embed dispatch other routes
     * through the same handler, and each one needs its own check.
     *
     * @var array<string, bool>
     */
    protected $grantedRoutes = [];

    /**
     * @param RouteService $routeService
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param ServerHelper $serverHelper
     * @param array $request
     */
    public function __construct($routeService, $jwtSettings, $serverHelper, $request)
    {
        $this->routeService = $routeService;
        $this->jwtSettings = $jwtSettings;
        $this->serverHelper = $serverHelper;
        $this->request = $request;
    }

    /**
     * @param ApiKeyRepositoryInterface $repository
     *
     * @return $this
     */
    public function withApiKeyRepository($repository)
    {
        $this->apiKeyRepository = $repository;

        return $this;
    }

    /**
     * @param RevokedTokenRepositoryInterface $repository
     *
     * @return $this
     */
    public function withRevokedTokenRepository($repository)
    {
        $this->revokedTokenRepo = $repository;

        return $this;
    }

    /**
     * Hooked on "rest_pre_dispatch": returning a response short-circuits the
     * dispatch, while WordPress still serves it through the regular REST
     * pipeline (rest_post_dispatch, rest_pre_serve_request, CORS headers).
     *
     * Registered with 3 arguments: ($result, WP_REST_Server, WP_REST_Request).
     * Only the dispatched request is needed, read as the third argument.
     *
     * @param mixed $result Response from an earlier filter, or null.
     *
     * @return mixed
     */
    public function __invoke($result)
    {
        $request = func_num_args() > 2 ? func_get_arg(2) : null;
        // rest_pre_dispatch also fires for internal rest_do_request() calls made
        // outside a REST HTTP request (e.g. block editor preloading). REST_REQUEST
        // is only defined once WP_REST_Server::serve_request() starts handling a
        // genuine REST HTTP request, so skip protection otherwise.
        if ($result !== null || !defined('REST_REQUEST') || !REST_REQUEST) {
            return $result;
        }

        // Without the dispatched request the route is unknown: an empty route is protected.
        $route = '';
        $method = $this->serverHelper->getRestRequestMethod();
        if ($request instanceof WP_REST_Request) {
            // A batch sub-request has its own method; core already applied any method override to it.
            $route = $request->get_route();
            $method = strtoupper((string) $request->get_method());
        }
        $routeKey = $method . ' ' . $route;
        if (isset($this->grantedRoutes[$routeKey])) {
            return $result;
        }

        $service = new ProtectEndpointService();
        $service
            ->withRequest($this->request)
            ->withRequestMethod($method)
            ->withSettings($this->jwtSettings)
            ->withServerHelper($this->serverHelper)
            ->withRouteService($this->routeService)
            ->withRevokedTokenRepository($this->revokedTokenRepo);
        if ($this->jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
            $service->withSession(SessionService::init());
        }
        if ($this->apiKeyRepository !== null) {
            $service->withApiKeyRepository($this->apiKeyRepository);
        }

        try {
            $hasAccess = $service->hasAccess($route);
        } catch (Exception $exception) {
            return RestErrorResponse::fromException($exception);
        }

        if ($hasAccess) {
            $this->grantedRoutes[$routeKey] = true;

            return $result;
        }

        return RestErrorResponse::create(
            __('You are not authorized to access this endpoint.', 'simple-jwt-login'),
            ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT,
            401
        );
    }
}
