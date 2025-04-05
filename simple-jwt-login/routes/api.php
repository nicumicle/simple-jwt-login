<?php

use SimpleJWTLogin\Helpers\CorsHelper;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Helpers\StatusCodeHelper;
use SimpleJWTLogin\Libraries\ParseRequest;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Services\ProtectEndpointService;
use SimpleJWTLogin\Services\RouteService;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;
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
    $wordPressData = new WordPressData();
    $serverHelper = new ServerHelper($_SERVER);
    $jwtSettings = new SimpleJWTLoginSettings($wordPressData);
    $routeService = new RouteService();
    $routeService->withSettings($jwtSettings);
    $routeService->withRequest($request);
    $routeService->withCookies($_COOKIE);
    $routeService->withServerHelper($serverHelper);

    if ($jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
        if (empty(session_id()) && !headers_sent()) {
            @session_start();
        }
        $routeService->withSession($_SESSION);
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

    if ($jwtSettings->getGeneralSettings()->isMiddlewareEnabled()) {
        add_filter('rest_authentication_errors', function ($errors) use ($routeService, $jwtSettings, $wordPressData, $serverHelper) {
	        if (!empty($errors)) {
		        return $errors;
	        }

            $currentURL = $serverHelper->getCurrentURL();
            if (strpos($currentURL, $jwtSettings->getGeneralSettings()->getRouteNamespace()) !== false) {
                //Skip middleware for simple-jwt-plugin
                return $errors;
            }

            $jwt = $routeService->getJwtFromRequestHeaderOrCookie();
            if (!empty($jwt)) {
                try {
                    $wordPressData->loginUser($routeService->getUserFromJwt($jwt));

                    return true;
                } catch (\Exception $exception) {
                    @header('Content-Type: application/json; charset=UTF-8');

                    wp_send_json_error(
                        [
                        'message'   => $exception->getMessage(),
                        'errorCode' => $exception->getCode(),
                        'type'      => 'simple-jwt-login-middleware'
                        ],
                        StatusCodeHelper::getStatusCodeFromExeption($exception, 400)
                    );

	                /* The wp_send_json_error call breaks the filter chain; the return statement will never be reached.
	                   however if we remove above lines, this will cause a change in the api error response format */

	                $status = StatusCodeHelper::getStatusCodeFromExeption($exception, 400);
	                return new WP_Error($exception->getCode(), $exception->getMessage(), ["status" => $status]);
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
                if (empty(session_id()) && !headers_sent()) {
                    @session_start();
                }
                $service->withSession($_SESSION);
            }
                
            $currentURL = esc_url($serverHelper->getCurrentURL());
            $currentURL = str_replace(home_url(), "", $currentURL);
            $documentRoot = esc_html($_SERVER['DOCUMENT_ROOT']);

            $hasAccess = $service->hasAccess($currentURL, $documentRoot);
            if ($hasAccess) {
                return $endpoint;
            }
            
            @header('Content-Type: application/json; charset=UTF-8');
            wp_send_json_error(
                [
                    'message'   => 'You are not authorized to access this endpoint.',
                    'errorCode' => 403,
                    'type'      => 'simple-jwt-login-route-protect'
                ],
                403
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
                'callback' => function () use ($request, $route, $jwtSettings, $serverHelper) {
                    try {
                        if ($jwtSettings
                            ->getHooksSettings()
                            ->isHookEnable(SimpleJWTLoginHooks::HOOK_BEFORE_ENDPOINT)
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
                            ->withSettings($jwtSettings);
                        if ($jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
                            if (empty(session_id()) && !headers_sent()) {
                                @session_start();
                            }
                            $service->withSession($_SESSION);
                        }

                        return $service->makeAction();
                    } catch (Exception $exception) {
                        @header('Content-Type: application/json; charset=UTF-8');
                        wp_send_json_error(
                            [
                            'message'   => $exception->getMessage(),
                            'errorCode' => $exception->getCode()
                            ],
                            StatusCodeHelper::getStatusCodeFromExeption($exception, 400)
                        );

                        return false;
                    }
                },
                'permission_callback' => '__return_true',
            ]
        );
    }
});
