<?php

namespace SimpleJWTLogin\Routes\Handlers;

use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ApiKeyPermissions;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Helpers\StatusCodeHelper;
use SimpleJWTLogin\Middleware\ApiKeyAuthMiddleware;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressRepositoryInterface;
use SimpleJWTLogin\Services\AuditLoggerService;
use SimpleJWTLogin\Services\RouteService;
use WP_Error;

class AuthenticationHandler
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
     * @var WordPressRepositoryInterface
     */
    protected $wordPressData;
    /**
     * @var ServerHelper
     */
    protected $serverHelper;
    /**
     * @var ApiKeyRepositoryInterface
     */
    protected $apiKeyRepository;
    /**
     * @var AuditLoggerService
     */
    protected $auditLogger;

    /**
     * @param RouteService $routeService
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param WordPressRepositoryInterface $wordPressData
     * @param ServerHelper $serverHelper
     * @param ApiKeyRepositoryInterface $apiKeyRepository
     * @param AuditLoggerService $auditLogger
     */
    public function __construct(
        $routeService,
        $jwtSettings,
        $wordPressData,
        $serverHelper,
        $apiKeyRepository,
        $auditLogger
    ) {
        $this->routeService = $routeService;
        $this->jwtSettings = $jwtSettings;
        $this->wordPressData = $wordPressData;
        $this->serverHelper = $serverHelper;
        $this->apiKeyRepository = $apiKeyRepository;
        $this->auditLogger = $auditLogger;
    }

    /**
     * @param mixed $errors
     *
     * @return mixed
     */
    public function __invoke($errors)
    {
        if (!empty($errors)) {
            return $errors;
        }

        $currentURL = $this->serverHelper->getCurrentURL();
        if (strpos($currentURL, $this->jwtSettings->getGeneralSettings()->getRouteNamespace()) !== false) {
            return $errors;
        }

        if ($this->jwtSettings->getGeneralSettings()->isMiddlewareEnabled()) {
            $jwt = $this->routeService->getJwtFromRequestHeaderOrCookie();
            if (!empty($jwt)) {
                try {
                    $this->wordPressData->setCurrentUser($this->routeService->getUserFromJwt($jwt));

                    return true;
                } catch (\Exception $exception) {
                    $status = StatusCodeHelper::getStatusCodeFromException($exception);
                    return new WP_Error(
                        'simple_jwt_login_middleware_error',
                        $exception->getMessage(),
                        [
                            'status'     => $status,
                            'error_code' => $exception->getCode(),
                        ]
                    );
                }
            }
        }

        if ($this->jwtSettings->getApiKeysSettings()->isEnabled()) {
            $headers          = array_change_key_case($this->serverHelper->getHeaders(), CASE_LOWER);
            $configuredHeader = strtolower($this->jwtSettings->getApiKeysSettings()->getHeaderName());
            $apiKeyHeader     = isset($headers[$configuredHeader])
                ? trim((string) $headers[$configuredHeader])
                : '';

            if ($apiKeyHeader !== '') {
                $requiredPermission = ApiKeyPermissions::httpMethodToPermission(
                    $this->serverHelper->getRequestMethod()
                );
                $keyData = null;
                if ($requiredPermission !== null) {
                    $keyData = (new ApiKeyAuthMiddleware($this->apiKeyRepository))
                        ->validate($this->serverHelper, $requiredPermission, $configuredHeader);
                }
                if ($keyData === null) {
                    return new WP_Error(
                        'simple_jwt_login_api_key_error',
                        __('Invalid or unauthorized API key.', 'simple-jwt-login'),
                        ['status' => 401, 'error_code' => ErrorCodes::ERR_API_KEY_UNAUTHORIZED]
                    );
                }
                $this->wordPressData->setCurrentUser(
                    $this->wordPressData->getUserDetailsById((int) $keyData['user_id'])
                );
                $endpointWithoutQuery = strtok($currentURL, '?');
                $message = json_encode([
                    'url' => $endpointWithoutQuery !== false ? $endpointWithoutQuery : $currentURL,
                ]);
                $this->auditLogger->log(
                    AuditEvents::API_KEY_USED,
                    (int) $keyData['user_id'],
                    null,
                    'success',
                    $message !== false ? $message : null,
                    (int) $keyData['id']
                );
                return true;
            }
        }

        return $errors;
    }
}
