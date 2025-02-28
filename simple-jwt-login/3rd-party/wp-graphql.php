<?php

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Helpers\StatusCodeHelper;
use SimpleJWTLogin\Libraries\ParseRequest;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;
use SimpleJWTLogin\Services\RouteService;

// This will allow to log in  a user to WPGraphQL is not authenticated
add_action('init_graphql_request', function () {
    $jwtSettings = new SimpleJWTLoginSettings(new WordPressData());
    if (!$jwtSettings->getGeneralSettings()->isWpGraphqlAuthenticationEnabled()) {
        return;
    }
    $parseRequest = ParseRequest::process($_SERVER);
    $parsedRequestVariables = [];
    if (isset($parseRequest['variables'])) {
        $parsedRequestVariables = (array) $parseRequest['variables'];
    }

    $routeService = (new RouteService())
        ->withSettings($jwtSettings)
        ->withRequest(array_merge($_REQUEST, $parsedRequestVariables))
        ->withCookies($_COOKIE)
        ->withServerHelper(new ServerHelper($_SERVER));

    if ($jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
        if (empty(session_id()) && !headers_sent()) {
            @session_start();
        }
        $routeService->withSession($_SESSION);
    }
    // Check if user is already authenticated
    if (is_user_logged_in()) {
        return;
    }

    $jwt = $routeService->getJwtFromRequestHeaderOrCookie();
    if (empty($jwt)) {
        return;
    }

    try {
        (new WordPressData())
            ->loginUser(
                $routeService->getUserFromJwt($jwt)
            );
        return true;
    } catch (\Exception $exception) {
        wp_send_json_error(
            [
                'message'   => $exception->getMessage(),
                'errorCode' => $exception->getCode(),
                'type'      => 'simple-jwt-login-middleware'
            ],
            StatusCodeHelper::getStatusCodeFromExeption($exception, 400)
        );

        return;
    }
});
