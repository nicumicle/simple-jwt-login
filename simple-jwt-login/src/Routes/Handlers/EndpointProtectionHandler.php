<?php

namespace SimpleJWTLogin\Routes\Handlers;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\RestErrorResponse;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
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
     * @var ApiKeyRepositoryInterface|null
     */
    protected $apiKeyRepository;
    /**
     * @var RevokedTokenRepositoryInterface
     */
    protected $revokedTokenRepo;

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
     * @param mixed $result Response from an earlier filter, or null.
     *
     * @return mixed
     */
    public function __invoke($result)
    {
        // rest_pre_dispatch also fires for internal rest_do_request() calls made
        // outside a REST HTTP request (e.g. block editor preloading). REST_REQUEST
        // is only defined once WP_REST_Server::serve_request() starts handling a
        // genuine REST HTTP request, so skip protection otherwise.
        if ($result !== null || !defined('REST_REQUEST') || !REST_REQUEST) {
            return $result;
        }

        $service = new ProtectEndpointService();
        $service
            ->withRequest($this->request)
            ->withRequestMethod($this->serverHelper->getRequestMethod())
            ->withSettings($this->jwtSettings)
            ->withServerHelper($this->serverHelper)
            ->withRouteService($this->routeService)
            ->withRevokedTokenRepository($this->revokedTokenRepo);
        if ($this->jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
            $service->withSession(simple_jwt_login_init_session());
        }
        if ($this->apiKeyRepository !== null) {
            $service->withApiKeyRepository($this->apiKeyRepository);
        }

        $currentURL = esc_url_raw($this->serverHelper->getCurrentURL());
        $currentURL = str_replace(home_url(), '', $currentURL);
        $documentRoot = esc_html($this->documentRoot);

        try {
            $hasAccess = $service->hasAccess($currentURL, $documentRoot);
        } catch (Exception $exception) {
            return RestErrorResponse::fromException($exception);
        }

        if ($hasAccess) {
            return $result;
        }

        return RestErrorResponse::create(
            __('You are not authorized to access this endpoint.', 'simple-jwt-login'),
            ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT,
            401
        );
    }
}
