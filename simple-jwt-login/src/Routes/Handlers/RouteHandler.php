<?php

namespace SimpleJWTLogin\Routes\Handlers;

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Helpers\StatusCodeHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;
use SimpleJWTLogin\Services\AuditLoggerService;
use WP_Error;

class RouteHandler
{
    /**
     * @var array
     */
    protected $route;
    /**
     * @var array
     */
    protected $request;
    /**
     * @var array
     */
    protected $cookies;
    /**
     * @var SimpleJWTLoginSettings
     */
    protected $jwtSettings;
    /**
     * @var ServerHelper
     */
    protected $serverHelper;
    /**
     * @var RefreshTokenRepositoryInterface
     */
    protected $tokenRepository;
    /**
     * @var WebhookLogRepositoryInterface|null
     */
    protected $webhookLogRepo;
    /**
     * @var ApiKeyRepositoryInterface|null
     */
    protected $apiKeyRepository;
    /**
     * @var AuditLoggerService|null
     */
    protected $auditLogger;

    /**
     * @param array $route
     * @param array $request
     * @param array $cookies
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param ServerHelper $serverHelper
     * @param RefreshTokenRepositoryInterface $tokenRepository
     * @param WebhookLogRepositoryInterface|null $webhookLogRepo
     */
    public function __construct(
        $route,
        $request,
        $cookies,
        $jwtSettings,
        $serverHelper,
        $tokenRepository,
        $webhookLogRepo
    ) {
        $this->route = $route;
        $this->request = $request;
        $this->cookies = $cookies;
        $this->jwtSettings = $jwtSettings;
        $this->serverHelper = $serverHelper;
        $this->tokenRepository = $tokenRepository;
        $this->webhookLogRepo = $webhookLogRepo;
    }

    /**
     * @param ApiKeyRepositoryInterface $repository
     *
     * @return $this
     */
    public function withApiKey($repository)
    {
        $this->apiKeyRepository = $repository;

        return $this;
    }

    /**
     * @param AuditLoggerService $auditLogger
     *
     * @return $this
     */
    public function withAuditLogger($auditLogger)
    {
        $this->auditLogger = $auditLogger;

        return $this;
    }

    /**
     * @param string $namespace
     * @param mixed $permissionCallback
     */
    public function register($namespace, $permissionCallback)
    {
        register_rest_route(
            $namespace,
            $this->route['name'],
            [
                'methods'             => $this->route['method'],
                'callback'            => $this,
                'permission_callback' => $permissionCallback,
            ]
        );
    }

    /**
     * @param mixed $wpRequest
     *
     * @return mixed
     */
    public function __invoke($wpRequest = null)
    {
        $request = $this->request;
        if ($this->apiKeyRepository !== null && $wpRequest !== null && is_object($wpRequest)) {
            $urlParams = (array) $wpRequest->get_url_params();
            $request = array_merge($request, $urlParams);
        }

        $keyId = isset($request['id']) ? (int) $request['id'] : null;

        try {
            $this->triggerBeforeHook();

            $service = new $this->route['service']();
            $service
                ->withRequestMethod($this->route['method'])
                ->withRequest($request)
                ->withCookies($this->cookies)
                ->withServerHelper($this->serverHelper)
                ->withSettings($this->jwtSettings)
                ->withRefreshTokenRepository($this->tokenRepository);

            if ($this->webhookLogRepo !== null) {
                $service->withWebhookLogRepository($this->webhookLogRepo);
            }

            if ($this->apiKeyRepository !== null) {
                $service->withApiKeyRepository($this->apiKeyRepository);
            }

            if ($this->apiKeyRepository === null
                && $this->jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()
            ) {
                $service->withSession(simple_jwt_login_init_session());
            }

            $result = $service->makeAction();

            $this->logAudit('audit_success', 'success', null, $keyId);

            return $result;
        } catch (\Throwable $exception) {
            $this->logAudit('audit_failure', 'failure', $exception->getMessage(), $keyId);

            if ($this->apiKeyRepository !== null && $exception instanceof \Exception) {
                return new WP_Error(
                    'simple_jwt_login_api_key_error',
                    $exception->getMessage(),
                    [
                        'status'     => StatusCodeHelper::getStatusCodeFromException($exception),
                        'error_code' => $exception->getCode(),
                    ]
                );
            }

            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }

            wp_send_json_error(
                [
                    'message'    => $exception->getMessage(),
                    'error_code' => $exception->getCode(),
                ],
                StatusCodeHelper::getStatusCodeFromException($exception)
            );

            return false;
        }
    }

    protected function triggerBeforeHook()
    {
        if ($this->apiKeyRepository !== null) {
            return;
        }
        if (!$this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::HOOK_BEFORE_ENDPOINT)) {
            return;
        }
        /** @phpstan-ignore-next-line */
        $this->jwtSettings->getWordPressData()->doAction(
            SimpleJWTLoginHooks::HOOK_BEFORE_ENDPOINT,
            $this->route['method'],
            $this->route['name'],
            $this->request
        );
    }

    /**
     * @param string $routeKey
     * @param string $status
     * @param string|null $message
     * @param int|null $keyId
     */
    protected function logAudit($routeKey, $status, $message, $keyId)
    {
        if ($this->auditLogger === null || empty($this->route[$routeKey])) {
            return;
        }
        $userId = $this->jwtSettings->getWordPressData()->getCurrentUserId();
        $this->auditLogger->log(
            $this->route[$routeKey],
            $userId ?: null,
            null,
            $status,
            $message,
            $keyId ?: null
        );
    }
}
