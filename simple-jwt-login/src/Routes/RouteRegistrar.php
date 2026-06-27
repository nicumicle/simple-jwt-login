<?php

namespace SimpleJWTLogin\Routes;

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\ParseRequest;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Routes\Handlers\AuthenticationHandler;
use SimpleJWTLogin\Routes\Handlers\CorsHandler;
use SimpleJWTLogin\Routes\Handlers\EndpointProtectionHandler;
use SimpleJWTLogin\Routes\Handlers\RouteHandler;
use SimpleJWTLogin\Routes\SessionService;
use SimpleJWTLogin\Services\AuditLoggerService;
use SimpleJWTLogin\Services\RouteService;

class RouteRegistrar
{
    /**
     * @var array
     */
    protected $server;
    /**
     * @var array
     */
    protected $requestVars;
    /**
     * @var array
     */
    protected $cookies;

    /**
     * @var WordPressRepository
     */
    protected $wordPressRepo;

    /**
     * @var SimpleJWTLoginSettings
     */
    protected $jwtSettings;

    /**
     * @var RefreshTokenRepository
     */
    protected $refreshTokenRepo;

    /**
     * @var AuditLogRepository
     */
    protected $auditLogRepo;

    /**
     * @var ApiKeyRepository
     */
    protected $apiKeyRepo;

    /**
     * @var WebhookLogRepository
     */
    protected $webhookLogRepo;

    /**
     * @param array $server
     * @param array $requestVars
     * @param array $cookies
     */
    public function __construct($server, $requestVars, $cookies)
    {
        $this->server = $server;
        $this->requestVars = $requestVars;
        $this->cookies = $cookies;
    }

    /**
     * @param WordPressRepository $wordPressRepo
     * @return $this
     */
    public function withWordPressRepository($wordPressRepo)
    {
        $this->wordPressRepo = $wordPressRepo;
        return $this;
    }

    /**
     * @param SimpleJWTLoginSettings $jwtSettings
     * @return $this
     */
    public function withSettings($jwtSettings)
    {
        $this->jwtSettings = $jwtSettings;
        return $this;
    }

    /**
     * @param RefreshTokenRepository $refreshTokenRepo
     * @return $this
     */
    public function withRefreshTokenRepo($refreshTokenRepo)
    {
        $this->refreshTokenRepo = $refreshTokenRepo;
        return $this;
    }

    /**
     * @param AuditLogRepository $auditLogRepo
     * @return $this
     */
    public function withAuditLogRepo($auditLogRepo)
    {
        $this->auditLogRepo = $auditLogRepo;
        return $this;
    }

    /**
     * @param ApiKeyRepository $apiKeyRepo
     * @return $this
     */
    public function withApiKeyRepo($apiKeyRepo)
    {
        $this->apiKeyRepo = $apiKeyRepo;
        return $this;
    }

    /**
     * @param WebhookLogRepository $webhookLogRepo
     * @return $this
     */
    public function withWebhookLogRepo($webhookLogRepo)
    {
        $this->webhookLogRepo = $webhookLogRepo;
        return $this;
    }

    public function register()
    {
        // Reading and parsing php://input is only useful when the request actually
        // carries a body. Skip it when there is no body indicator (no Content-Length
        // and no Content-Type), which is the case for the vast majority of GET
        // requests hitting the REST API site-wide. Parameters for those arrive via
        // the query string ($_REQUEST) and are merged below.
        $contentLength = isset($this->server['CONTENT_LENGTH'])
            ? (int) $this->server['CONTENT_LENGTH']
            : 0;
        $hasContentType = isset($this->server['CONTENT_TYPE'])
            && trim((string) $this->server['CONTENT_TYPE']) !== '';
        $parsedVars = [];
        if ($contentLength > 0 || $hasContentType) {
            $parseRequest = ParseRequest::process($this->server);
            if (isset($parseRequest['variables'])) {
                $parsedVars = (array) $parseRequest['variables'];
            }
        }

        $request = array_merge(wp_unslash($this->requestVars), $parsedVars);
        $serverHelper = $this->jwtSettings->getGeneralSettings()->isTrustIpHeadersEnabled()
            ? ServerHelper::withTrustedProxyHeaders($this->server)
            : new ServerHelper($this->server);

        $webhookLogRepo = $this->jwtSettings->getWebhooksSettings()->isWebhookLogsEnabled()
            ? $this->webhookLogRepo
            : null;

        $auditLogger = new AuditLoggerService(
            $this->auditLogRepo,
            $this->jwtSettings->getAuditLogSettings(),
            $serverHelper,
            $this->jwtSettings->getWordPressData()
        );
        $auditLogger->registerAuditHooks();

        $routeService = new RouteService();
        $routeService->withSettings($this->jwtSettings);
        $routeService->withRequest($request);
        $routeService->withCookies($this->cookies);
        $routeService->withServerHelper($serverHelper);

        if ($this->jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
            $routeService->withSession(SessionService::init());
        }

        if ($this->jwtSettings->getCorsSettings()->isCorsEnabled()) {
            $corsHandler = new CorsHandler($this->jwtSettings->getCorsSettings());
            $corsHandler->register();
        }

        $this->registerMiddleware(
            $routeService,
            $this->jwtSettings,
            $this->wordPressRepo,
            $serverHelper,
            $this->apiKeyRepo,
            $auditLogger
        );
        $documentRoot = isset($this->server['DOCUMENT_ROOT']) ? $this->server['DOCUMENT_ROOT'] : '';
        $this->registerEndpointProtection(
            $routeService,
            $this->jwtSettings,
            $serverHelper,
            $request,
            $documentRoot,
            $this->apiKeyRepo
        );
        $this->registerRoutes(
            $routeService,
            $this->jwtSettings,
            $serverHelper,
            $request,
            $this->refreshTokenRepo,
            $webhookLogRepo
        );
        $this->registerApiKeyRoutes(
            $routeService,
            $this->jwtSettings,
            $this->wordPressRepo,
            $serverHelper,
            $request,
            $this->apiKeyRepo,
            $this->refreshTokenRepo,
            $webhookLogRepo,
            $auditLogger
        );
    }

    /**
     * @param RouteService $routeService
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param WordPressRepository $wordPressData
     * @param ServerHelper $serverHelper
     * @param ApiKeyRepository $apiKeyRepository
     * @param AuditLoggerService $auditLogger
     */
    protected function registerMiddleware(
        $routeService,
        $jwtSettings,
        $wordPressData,
        $serverHelper,
        $apiKeyRepository,
        $auditLogger
    ) {
        if (!$jwtSettings->getGeneralSettings()->isMiddlewareEnabled()
            && !$jwtSettings->getApiKeysSettings()->isEnabled()
        ) {
            return;
        }

        add_filter('rest_post_dispatch', function ($response) {
            $data = $response->get_data();
            if (!is_array($data) || !isset($data['code'])) {
                return $response;
            }
            if (strpos((string) $data['code'], 'simple_jwt_login_') !== 0) {
                return $response;
            }
            $extraData = isset($data['data']) && is_array($data['data']) ? (array) $data['data'] : [];
            unset($extraData['status']);
            $response->set_data(
                ['success' => false, 'data' => ['message' => (string) $data['message']] + $extraData]
            );
            return $response;
        }, 0);

        $authHandler = new AuthenticationHandler(
            $routeService,
            $jwtSettings,
            $wordPressData,
            $serverHelper,
            $apiKeyRepository,
            $auditLogger
        );
        add_filter('rest_authentication_errors', $authHandler, 0);
    }

    /**
     * @param RouteService $routeService
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param ServerHelper $serverHelper
     * @param array $request
     * @param string $documentRoot
     * @param ApiKeyRepository|null $apiKeyRepository
     */
    protected function registerEndpointProtection(
        $routeService,
        $jwtSettings,
        $serverHelper,
        $request,
        $documentRoot,
        $apiKeyRepository = null
    ) {
        if (!$jwtSettings->getProtectEndpointsSettings()->isEnabled()) {
            return;
        }

        $handler = new EndpointProtectionHandler(
            $routeService,
            $jwtSettings,
            $serverHelper,
            $request,
            $documentRoot
        );
        if ($apiKeyRepository !== null) {
            $handler->withApiKeyRepository($apiKeyRepository);
        }
        add_action('rest_endpoints', $handler, 0);
    }

    /**
     * @param RouteService $routeService
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param ServerHelper $serverHelper
     * @param array $request
     * @param RefreshTokenRepository $tokenRepository
     * @param WebhookLogRepository|null $webhookLogRepository
     */
    protected function registerRoutes(
        $routeService,
        $jwtSettings,
        $serverHelper,
        $request,
        $tokenRepository,
        $webhookLogRepository
    ) {
        $namespace = rtrim($jwtSettings->getGeneralSettings()->getRouteNamespace(), '/\\');

        foreach ($routeService->getAllRoutes() as $route) {
            $handler = $this->buildRouteHandler(
                $route,
                $request,
                $jwtSettings,
                $serverHelper,
                $tokenRepository,
                $webhookLogRepository
            );
            $handler->register($namespace, '__return_true');
        }
    }

    /**
     * Build a RouteHandler wired with the shared per-request dependencies.
     * The two route loops differ only in API-key/audit wiring and the
     * permission callback, applied by the caller.
     *
     * @param array $route
     * @param array $request
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param ServerHelper $serverHelper
     * @param RefreshTokenRepository $tokenRepository
     * @param WebhookLogRepository|null $webhookLogRepository
     * @return RouteHandler
     */
    protected function buildRouteHandler(
        $route,
        $request,
        $jwtSettings,
        $serverHelper,
        $tokenRepository,
        $webhookLogRepository
    ) {
        return new RouteHandler(
            $route,
            $request,
            $this->cookies,
            $jwtSettings,
            $serverHelper,
            $tokenRepository,
            $webhookLogRepository
        );
    }

    /**
     * @param RouteService $routeService
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param WordPressRepository $wordPressData
     * @param ServerHelper $serverHelper
     * @param array $request
     * @param ApiKeyRepository $apiKeyRepository
     * @param RefreshTokenRepository $tokenRepository
     * @param WebhookLogRepository|null $webhookLogRepository
     * @param AuditLoggerService $auditLogger
     */
    protected function registerApiKeyRoutes(
        $routeService,
        $jwtSettings,
        $wordPressData,
        $serverHelper,
        $request,
        $apiKeyRepository,
        $tokenRepository,
        $webhookLogRepository,
        $auditLogger
    ) {
        $namespace = rtrim($jwtSettings->getGeneralSettings()->getRouteNamespace(), '/\\');

        foreach ($routeService->getApiKeyRoutes() as $route) {
            $handler = $this->buildRouteHandler(
                $route,
                $request,
                $jwtSettings,
                $serverHelper,
                $tokenRepository,
                $webhookLogRepository
            );
            $handler->withApiKey($apiKeyRepository);
            $handler->withAuditLogger($auditLogger);
            $handler->register($namespace, $this->buildPermissionCallback($routeService, $wordPressData));
        }
    }

    /**
     * @param RouteService $routeService
     * @param WordPressRepository $wordPressData
     *
     * @return callable
     */
    protected function buildPermissionCallback($routeService, $wordPressData)
    {
        return function () use ($routeService, $wordPressData) {
            if (is_user_logged_in()) {
                return true;
            }
            $jwt = $routeService->getJwtFromRequestHeaderOrCookie();
            if ($jwt === null) {
                return false;
            }
            try {
                $wordPressData->setCurrentUser($routeService->getUserFromJwt($jwt));
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        };
    }
}
