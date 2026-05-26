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

    public function register()
    {
        $parseRequest = ParseRequest::process($this->server);
        $parsedVars = [];
        if (isset($parseRequest['variables'])) {
            $parsedVars = (array) $parseRequest['variables'];
        }

        $request = array_merge($this->requestVars, $parsedVars);
        $wordPressData = new WordPressRepository();
        $serverHelper = new ServerHelper($this->server);
        $jwtSettings = new SimpleJWTLoginSettings($wordPressData);

        global $wpdb;
        $tokenRepository = new RefreshTokenRepository($wpdb);
        $auditLogRepository     = new AuditLogRepository($wpdb);
        $apiKeyRepository       = new ApiKeyRepository($wpdb);
        $webhookLogRepository = $jwtSettings->getWebhooksSettings()->isWebhookLogsEnabled()
            ? new WebhookLogRepository($wpdb)
            : null;
        $auditLogger = new AuditLoggerService(
            $auditLogRepository,
            $jwtSettings->getAuditLogSettings(),
            $serverHelper
        );

        $auditLogger->registerAuditHooks();

        $routeService = new RouteService();
        $routeService->withSettings($jwtSettings);
        $routeService->withRequest($request);
        $routeService->withCookies($this->cookies);
        $routeService->withServerHelper($serverHelper);

        if ($jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
            $routeService->withSession(SessionService::init());
        }

        if ($jwtSettings->getCorsSettings()->isCorsEnabled()) {
            $corsHandler = new CorsHandler($jwtSettings->getCorsSettings());
            $corsHandler->register();
        }

        $this->registerMiddleware(
            $routeService,
            $jwtSettings,
            $wordPressData,
            $serverHelper,
            $apiKeyRepository,
            $auditLogger
        );
        $documentRoot = isset($this->server['DOCUMENT_ROOT']) ? $this->server['DOCUMENT_ROOT'] : '';
        $this->registerEndpointProtection($routeService, $jwtSettings, $serverHelper, $request, $documentRoot);
        $this->registerRoutes(
            $routeService,
            $jwtSettings,
            $serverHelper,
            $request,
            $tokenRepository,
            $webhookLogRepository
        );
        $this->registerApiKeyRoutes(
            $routeService,
            $jwtSettings,
            $wordPressData,
            $serverHelper,
            $request,
            $apiKeyRepository,
            $tokenRepository,
            $webhookLogRepository,
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
     */
    protected function registerEndpointProtection(
        $routeService,
        $jwtSettings,
        $serverHelper,
        $request,
        $documentRoot
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
            $handler = new RouteHandler(
                $route,
                $request,
                $this->cookies,
                $jwtSettings,
                $serverHelper,
                $tokenRepository,
                $webhookLogRepository
            );
            $handler->register($namespace, '__return_true');
        }
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
            $handler = new RouteHandler(
                $route,
                $request,
                $this->cookies,
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
                $wordPressData->loginUser($routeService->getUserFromJwt($jwt));
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        };
    }
}
