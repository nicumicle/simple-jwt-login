<?php

use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ApiKeyPermissions;
use SimpleJWTLogin\Helpers\CorsHelper;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Helpers\StatusCodeHelper;
use SimpleJWTLogin\Libraries\ParseRequest;
use SimpleJWTLogin\Middleware\ApiKeyAuthMiddleware;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Services\ApiKeys\ApiKeyServiceInterface;
use SimpleJWTLogin\Services\ApiKeys\CreateApiKeyService;
use SimpleJWTLogin\Services\ApiKeys\DeleteApiKeyService;
use SimpleJWTLogin\Services\ApiKeys\ListApiKeysService;
use SimpleJWTLogin\Services\ApiKeys\RevokeApiKeyService;
use SimpleJWTLogin\Services\ApiKeys\UpdateApiKeyService;
use SimpleJWTLogin\Services\AuditLoggerService;
use SimpleJWTLogin\Services\ProtectEndpointService;
use SimpleJWTLogin\Services\RouteService;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Services\ServiceInterface;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

require_once(ABSPATH . 'wp-admin/includes/user.php');

add_action('rest_api_init', function () {
    $parseRequest = ParseRequest::process($_SERVER);
    $parsedRequestVariables = [];
    if (isset($parseRequest['variables'])) {
        $parsedRequestVariables = (array) $parseRequest['variables'];
    }

    $request = array_merge($_REQUEST, $parsedRequestVariables);
    $wordPressData = new WordPressRepository();
    $serverHelper = new ServerHelper($_SERVER);
    $jwtSettings = new SimpleJWTLoginSettings($wordPressData);
    global $wpdb;
    $refreshTokenRepository = new RefreshTokenRepository($wpdb);
    $auditLogRepository     = new AuditLogRepository($wpdb);
    $apiKeyRepository       = new ApiKeyRepository($wpdb);
    $webhookLogRepository = $jwtSettings->getWebhooksSettings()->isWebhookLogsEnabled()
        ? new WebhookLogRepository($wpdb)
        : null;
    $auditLogger            = new AuditLoggerService(
        $auditLogRepository,
        $jwtSettings->getAuditLogSettings(),
        $serverHelper
    );

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SUCCESS, function ($userId, $userEmail) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_LOGIN_SUCCESS, $userId, $userEmail, 'success');
    }, 10, 2);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_FAILED, function ($userId, $userEmail, $message) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_LOGIN_FAILED, $userId, $userEmail, 'failure', $message);
    }, 10, 3);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_LOGOUT_SUCCESS, function ($userId, $userEmail) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_LOGOUT_SUCCESS, $userId, $userEmail, 'success');
    }, 10, 2);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_LOGOUT_FAILED, function ($userId, $userEmail, $message) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_LOGOUT_FAILED, $userId, $userEmail, 'failure', $message);
    }, 10, 3);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_REGISTER_SUCCESS, function ($userId, $userEmail) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_REGISTER_SUCCESS, $userId, $userEmail, 'success');
    }, 10, 2);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_REGISTER_FAILED, function ($userId, $userEmail, $message) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_REGISTER_FAILED, $userId, $userEmail, 'failure', $message);
    }, 10, 3);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_PASSWORD_RESET_REQUEST, function ($userId, $userEmail) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_PASSWORD_RESET_REQUEST, $userId, $userEmail, 'success');
    }, 10, 2);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_PASSWORD_RESET_SUCCESS, function ($userId, $userEmail) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_PASSWORD_RESET_SUCCESS, $userId, $userEmail, 'success');
    }, 10, 2);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_PASSWORD_RESET_FAILED, function ($userId, $userEmail, $message) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_PASSWORD_RESET_FAILED, $userId, $userEmail, 'failure', $message);
    }, 10, 3);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_DELETE_USER_SUCCESS, function ($userId, $userEmail) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_DELETE_USER_SUCCESS, $userId, $userEmail, 'success');
    }, 10, 2);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_DELETE_USER_FAILED, function ($userId, $userEmail, $message) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_DELETE_USER_FAILED, $userId, $userEmail, 'failure', $message);
    }, 10, 3);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SESSION_SUCCESS, function ($userId, $userEmail) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_LOGIN_SESSION_SUCCESS, $userId, $userEmail, 'success');
    }, 10, 2);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SESSION_FAILED, function ($userId, $userEmail, $message) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_LOGIN_SESSION_FAILED, $userId, $userEmail, 'failure', $message);
    }, 10, 3);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_REFRESH_TOKEN_SUCCESS, function ($userId, $userEmail) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS, $userId, $userEmail, 'success');
    }, 10, 2);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_REFRESH_TOKEN_FAILED, function ($userId, $userEmail, $message) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_REFRESH_TOKEN_FAILED, $userId, $userEmail, 'failure', $message);
    }, 10, 3);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_SUCCESS, function ($userId, $userEmail) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_OAUTH_SUCCESS, $userId, $userEmail, 'success');
    }, 10, 2);

    add_action(SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_FAILED, function ($userId, $userEmail, $message) use ($auditLogger) {
        $auditLogger->log(AuditEvents::AUTH_OAUTH_FAILED, $userId, $userEmail, 'failure', $message);
    }, 10, 3);

    $routeService = new RouteService();
    $routeService->withSettings($jwtSettings);
    $routeService->withRequest($request);
    $routeService->withCookies($_COOKIE);
    $routeService->withServerHelper($serverHelper);

    if ($jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
        $routeService->withSession(simple_jwt_login_init_session());
    }

    if ($jwtSettings->getCorsSettings()->isCorsEnabled()) {
        $corsService = new CorsHelper();
        if ($jwtSettings->getCorsSettings()->isAllowOriginEnabled()) {
            $corsService->addHeader(
                'Access-Control-Allow-Origin',
                $jwtSettings->getCorsSettings()->getAllowOrigin()
            );
        }
        if ($jwtSettings->getCorsSettings()->isAllowMethodsEnabled()) {
            $corsService->addHeader(
                'Access-Control-Allow-Methods',
                $jwtSettings->getCorsSettings()->getAllowMethods()
            );
        }
        if ($jwtSettings->getCorsSettings()->isAllowHeadersEnabled()) {
            $corsService->addHeader(
                'Access-Control-Allow-Headers',
                $jwtSettings->getCorsSettings()->getAllowHeaders()
            );
        }
    }

    if ($jwtSettings->getGeneralSettings()->isMiddlewareEnabled() || $jwtSettings->getApiKeysSettings()->isEnabled()) {
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
            $response->set_data(['success' => false, 'data' => ['message' => (string) $data['message']] + $extraData]);
            return $response;
        }, 0);

        add_filter('rest_authentication_errors', function ($errors) use ($routeService, $jwtSettings, $wordPressData, $serverHelper, $apiKeyRepository, $auditLogger) {
	        if (!empty($errors)) {
		        return $errors;
	        }

            $currentURL = $serverHelper->getCurrentURL();
            if (strpos($currentURL, $jwtSettings->getGeneralSettings()->getRouteNamespace()) !== false) {
                //Skip middleware for simple-jwt-plugin
                return $errors;
            }

            if ($jwtSettings->getGeneralSettings()->isMiddlewareEnabled()) {
                $jwt = $routeService->getJwtFromRequestHeaderOrCookie();
                if (!empty($jwt)) {
                    try {
                        $wordPressData->loginUser($routeService->getUserFromJwt($jwt));

                        return true;
                    } catch (\Exception $exception) {
                        $status = StatusCodeHelper::getStatusCodeFromException($exception, 400);
                        return new WP_Error(
                            'simple_jwt_login_middleware_error',
                            $exception->getMessage(),
                            [
                                'status'    => $status,
                                'errorCode' => $exception->getCode(),
                            ]
                        );
                    }
                }
            }

            if ($jwtSettings->getApiKeysSettings()->isEnabled()) {
                $headers          = array_change_key_case($serverHelper->getHeaders(), CASE_LOWER);
                $configuredHeader = strtolower($jwtSettings->getApiKeysSettings()->getHeaderName());
                $apiKeyHeader     = isset($headers[$configuredHeader]) ? trim((string) $headers[$configuredHeader]) : '';

                if ($apiKeyHeader !== '') {
                    $requiredPermission = ApiKeyPermissions::httpMethodToPermission($serverHelper->getRequestMethod());
                    $keyData            = null;
                    if ($requiredPermission !== null) {
                        $keyData = (new ApiKeyAuthMiddleware($apiKeyRepository))
                            ->validate($serverHelper, $requiredPermission, $configuredHeader);
                    }
                    if ($keyData === null) {
                        return new WP_Error(
                            'simple_jwt_login_api_key_error',
                            __('Invalid or unauthorized API key.', 'simple-jwt-login'),
                            ['status' => 401, 'errorCode' => ErrorCodes::ERR_API_KEY_UNAUTHORIZED]
                        );
                    }
                    $wordPressData->loginUser($wordPressData->getUserDetailsById((int) $keyData['user_id']));
                    $auditLogger->log(
                        AuditEvents::API_KEY_USED,
                        (int) $keyData['user_id'],
                        null,
                        'success',
                        null,
                        (int) $keyData['id']
                    );
                    return true;
                }
            }

            return $errors;
        }, 0);
    }

    if ($jwtSettings->getProtectEndpointsSettings()->isEnabled()) {
        add_action('rest_endpoints', function ($endpoint) use ($routeService, $jwtSettings, $serverHelper, $request) {
            $service = new ProtectEndpointService();
            $service
                ->withRequest($request)
                ->withRequestMethod($serverHelper->getRequestMethod())
                ->withSettings($jwtSettings)
                ->withServerHelper($serverHelper)
                ->withRouteService($routeService);
            if ($jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
                $service->withSession(simple_jwt_login_init_session());
            }
                
            $currentURL = esc_url_raw($serverHelper->getCurrentURL());
            $currentURL = str_replace(home_url(), '', $currentURL);
            $documentRoot = esc_html($_SERVER['DOCUMENT_ROOT']);

            try {
                $hasAccess = $service->hasAccess($currentURL, $documentRoot);
            } catch (Exception $exception) {
                @header('Content-Type: application/json; charset=UTF-8');
                wp_send_json_error(
                    [
                        'message'   => $exception->getMessage(),
                        'errorCode' => $exception->getCode(),
                    ],
                    StatusCodeHelper::getStatusCodeFromException($exception, 400)
                );

                return false;
            }

            if ($hasAccess) {
                return $endpoint;
            }

            @header('Content-Type: application/json; charset=UTF-8');
            wp_send_json_error(
                [
                    'message'   => __('You are not authorized to access this endpoint.', 'simple-jwt-login'),
                    'errorCode' => ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT,
                ],
                401
            );

            return false;
        }, 0);
    }

    $availableRoutes = $routeService->getAllRoutes();
    foreach ($availableRoutes as $route) {
        register_rest_route(
            rtrim($jwtSettings->getGeneralSettings()->getRouteNamespace(), '/\\'),
            $route['name'],
            [
                'methods'  => $route['method'],
                'callback' => function () use ($request, $route, $jwtSettings, $serverHelper, $refreshTokenRepository, $webhookLogRepository) {
                    try {
                        if ($jwtSettings
                            ->getHooksSettings()
                            ->isHookEnabled(SimpleJWTLoginHooks::HOOK_BEFORE_ENDPOINT)
                        ) {
                            /** @phpstan-ignore-next-line */
                            $jwtSettings->getWordPressData()->triggerAction(
                                SimpleJWTLoginHooks::HOOK_BEFORE_ENDPOINT,
                                $route['method'],
                                $route['name'],
                                $request
                            );
                        }

                        /** @var ServiceInterface $service */
                        $service = new $route['service']();
                        $service
                            ->withRequestMethod($route['method'])
                            ->withRequest($request)
                            ->withCookies($_COOKIE)
                            ->withServerHelper($serverHelper)
                            ->withSettings($jwtSettings)
                            ->withRefreshTokenRepository($refreshTokenRepository)
                            ->withWebhookLogRepository($webhookLogRepository);
                        if ($jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
                            $service->withSession(simple_jwt_login_init_session());
                        }

                        return $service->makeAction();
                    } catch (Exception $exception) {
                        @header('Content-Type: application/json; charset=UTF-8');
                        wp_send_json_error(
                            [
                            'message'   => $exception->getMessage(),
                            'errorCode' => $exception->getCode()
                            ],
                            StatusCodeHelper::getStatusCodeFromException($exception, 400)
                        );

                        return false;
                    }
                },
                'permission_callback' => '__return_true',
            ]
        );
    }

    $apiKeysCrudRoutes = [
        [
            'name'    => RouteService::API_KEYS_ROUTE,
            'method'  => RouteService::METHOD_GET,
            'service' => ListApiKeysService::class,
        ],
        [
            'name'          => RouteService::API_KEYS_ROUTE,
            'method'        => RouteService::METHOD_POST,
            'service'       => CreateApiKeyService::class,
            'audit_success' => AuditEvents::API_KEY_CREATE_SUCCESS,
            'audit_failure' => AuditEvents::API_KEY_CREATE_FAILED,
        ],
        [
            'name'          => RouteService::API_KEYS_SINGLE_ROUTE,
            'method'        => RouteService::METHOD_PUT,
            'service'       => UpdateApiKeyService::class,
            'audit_success' => AuditEvents::API_KEY_UPDATE_SUCCESS,
            'audit_failure' => AuditEvents::API_KEY_UPDATE_FAILED,
        ],
        [
            'name'          => RouteService::API_KEYS_SINGLE_ROUTE,
            'method'        => RouteService::METHOD_DELETE,
            'service'       => RevokeApiKeyService::class,
            'audit_success' => AuditEvents::API_KEY_REVOKE_SUCCESS,
            'audit_failure' => AuditEvents::API_KEY_REVOKE_FAILED,
        ],
        [
            'name'          => RouteService::API_KEYS_HARD_DELETE_ROUTE,
            'method'        => RouteService::METHOD_DELETE,
            'service'       => DeleteApiKeyService::class,
            'audit_success' => AuditEvents::API_KEY_DELETE_SUCCESS,
            'audit_failure' => AuditEvents::API_KEY_DELETE_FAILED,
        ],
    ];

    foreach ($apiKeysCrudRoutes as $route) {
        register_rest_route(
            rtrim($jwtSettings->getGeneralSettings()->getRouteNamespace(), '/\\'),
            $route['name'],
            [
                'methods'             => $route['method'],
                'callback'            => function ($wpRequest) use ($route, $jwtSettings, $serverHelper, $apiKeyRepository, $refreshTokenRepository, $webhookLogRepository, $request, $auditLogger) {
                    $urlParams     = is_object($wpRequest) ? (array) $wpRequest->get_url_params() : [];
                    $mergedRequest = array_merge($request, $urlParams);
                    $keyId         = isset($mergedRequest['id']) ? (int) $mergedRequest['id'] : null;

                    try {
                        /** @var ApiKeyServiceInterface $service */
                        $service = new $route['service']();
                        $service
                            ->withRequestMethod($route['method'])
                            ->withRequest($mergedRequest)
                            ->withCookies($_COOKIE)
                            ->withServerHelper($serverHelper)
                            ->withSettings($jwtSettings)
                            ->withRefreshTokenRepository($refreshTokenRepository)
                            ->withWebhookLogRepository($webhookLogRepository)
                            ->withApiKeyRepository($apiKeyRepository);

                        $result = $service->makeAction();

                        if (!empty($route['audit_success'])) {
                            $userId = $jwtSettings->getWordPressData()->getCurrentUserId();
                            $auditLogger->log(
                                $route['audit_success'],
                                $userId ?: null,
                                null,
                                'success',
                                null,
                                $keyId ?: null
                            );
                        }

                        return $result;
                    } catch (Exception $exception) {
                        if (!empty($route['audit_failure'])) {
                            $userId = $jwtSettings->getWordPressData()->getCurrentUserId();
                            $auditLogger->log(
                                $route['audit_failure'],
                                $userId ?: null,
                                null,
                                'failure',
                                $exception->getMessage(),
                                $keyId ?: null
                            );
                        }

                        return new \WP_Error(
                            'simple_jwt_login_api_key_error',
                            $exception->getMessage(),
                            [
                                'status'    => StatusCodeHelper::getStatusCodeFromException($exception, 400),
                                'errorCode' => $exception->getCode(),
                            ]
                        );
                    }
                },
                'permission_callback' => function () use ($routeService, $wordPressData) {
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
                    } catch (Exception $exception) {
                        return false;
                    }
                },
            ]
        );
    }
});


/**
 * @SuppressWarnings(PHPMD.Superglobals)
 * @return array
 */
function simple_jwt_login_init_session()
{
    switch (session_status()) {
        case PHP_SESSION_DISABLED:
            return [];
        case PHP_SESSION_NONE:
            if (headers_sent()) {
                return [];
            }

            session_start();
            
            return $_SESSION;
        case PHP_SESSION_ACTIVE:
            return $_SESSION;
        default:
            return [];
    }
}
