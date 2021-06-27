<?php
use SimpleJWTLogin\Helpers\CorsHelper;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\ParseRequest;
use SimpleJWTLogin\Services\RouteService;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;
use SimpleJWTLogin\Services\ServiceInterface;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

require_once(ABSPATH . 'wp-admin/includes/user.php');

add_action('rest_api_init', function () {
    $parseRequest = ParseRequest::process($_SERVER);
    $parsedRequestVariables = [];
    if(isset($parseRequest['variables'])){
        $parsedRequestVariables = (array) $parseRequest['variables'];
    }
    $request = array_merge($_REQUEST, $parsedRequestVariables);

    $jwtSettings = new SimpleJWTLoginSettings(new WordPressData());
    $routeService = new RouteService();
    $routeService->withSettings($jwtSettings);
    $routeService->withRequest($request);
    $routeService->withCookies($_COOKIE);
    $routeService->withServerHelper(new ServerHelper($_SERVER));

    if ($jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
        if (empty(session_id()) && !headers_sent()) {
            @session_start();
        }
        $routeService->withSession($_SESSION);
    }

    $corsService = new CorsHelper();
    if ($jwtSettings->getCorsSettings()->isCorsEnabled()) {
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
        add_action('rest_endpoints', function ($endpoint) use ($routeService, $jwtSettings) {
            $currentURL =
                "http"
                . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "")
                . "://" . $_SERVER['HTTP_HOST']
                . $_SERVER['REQUEST_URI'];
            if (strpos($currentURL, $jwtSettings->getGeneralSettings()->getRouteNamespace()) !== false) {
                //Skip middleware for simple-jwt-plugin
                return $endpoint;
            }

            $jwt = $routeService->getJwtFromRequestHeaderOrCookie($_SERVER);
            if (! empty($jwt)) {
                try {
                    $userID = $routeService->getUserIdFromJWT($jwt);
                    wp_set_current_user($userID);
                } catch (\Exception $e) {
                    @header('Content-Type: application/json; charset=UTF-8');
                    wp_send_json_error(
                        [
                        'message'   => $e->getMessage(),
                        'errorCode' => $e->getCode(),
                        'type'      => 'simple-jwt-login-middleware'
						],
                        400
                    );
                    die();
                }
            }

            return $endpoint;
        }, 99);
    }

    $availableRoutes = $routeService->getAllRoutes();
    $serverHelper = new ServerHelper($_SERVER);

    foreach ($availableRoutes as $route) {
        register_rest_route(
            rtrim($jwtSettings->getGeneralSettings()->getRouteNamespace(), '/\\'),
            $route['name'],
            [
                'methods'  => $route['method'],
                'callback' => function () use ($request, $route, $routeService, $jwtSettings, $serverHelper) {
                    try {
                        /** @var ServiceInterface $service */
                        $service = new $route['service']();
                        $service
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
                    } catch (Exception $e) {
                        @header('Content-Type: application/json; charset=UTF-8');
                        wp_send_json_error(
                            [
                            'message'   => $e->getMessage(),
                            'errorCode' => $e->getCode()
							],
                            400
                        );

                        return false;
                    }
                },
                'permission_callback' => '__return_true',
            ]
        );
    }
});
