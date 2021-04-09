<?php

use SimpleJWTLogin\Modules\CorsService;
use SimpleJWTLogin\Modules\RouteService;
use SimpleJWTLogin\Modules\SimpleJWTLoginService;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

require_once( ABSPATH . 'wp-admin/includes/user.php' );

add_action( 'rest_api_init', function () {
	$jwtService = new SimpleJWTLoginService();
	$jwtSettings = new SimpleJWTLoginSettings( new WordPressData() );
	$jwtService->withSettings( $jwtSettings );
	$jwtService->withRequest($_REQUEST);
	$jwtService->withCookie( $_COOKIE );

	if($jwtSettings->getJwtFromSessionEnabled()){
        if( empty(session_id()) && !headers_sent()) {
            @session_start();
        }
        $jwtService->withSession( $_SESSION );
    }

	$corsService = new CorsService($jwtSettings);
	if($corsService->isCorsEnabled()) {

		if($corsService->isAllowOriginEnabled()){
			$corsService->addHeader('Access-Control-Allow-Origin', $corsService->getAllowOrigin());
		}
		if($corsService->isAllowMethodsEnabled()){
			$corsService->addHeader('Access-Control-Allow-Methods', $corsService->getAllowMethods());
		}
		if($corsService->isAllowHeadersEnabled()){
			$corsService->addHeader('Access-Control-Allow-Headers', $corsService->getAllowHeaders());
		}
	}

	if ( $jwtSettings->isMiddlewareEnabled() ) {
		add_action( 'rest_endpoints', function ( $endpoint ) use ( $jwtService, $jwtSettings ) {
			$currentURL =
				"http"
				.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "")
				. "://" .$_SERVER['HTTP_HOST']
				.$_SERVER['REQUEST_URI'];
			if( strpos($currentURL, $jwtSettings->getRouteNamespace()) !== false){
				//Skip middleware for simple-jwt-plugin
				return $endpoint;
			}

			$jwt = $jwtService->getJwtFromRequestHeaderOrCookie();
			if ( ! empty( $jwt ) ) {
				try {
					$userID = $jwtService->getUserIdFromJWT( $jwt );
					wp_set_current_user( $userID );
				} catch ( \Exception $e ) {
					@header( 'Content-Type: application/json; charset=UTF-8' );
					wp_send_json_error( [
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
		}, 99 );
	}

	$routeService    = new RouteService();
	$availableRoutes = $routeService->getAllRoutes();

	foreach ( $availableRoutes as $route ) {
		register_rest_route( rtrim($jwtSettings->getRouteNamespace(),'/\\'), $route['name'], [
				'methods'  => $route['method'],
				'callback' => function ( $request ) use ( $route, $routeService, $jwtService, $jwtSettings ) {
					/***
					 * @var $request WP_REST_Request
					 */

					try {
						$jwtService->withRequest( $request->get_params() );
						$routeService->withService( $jwtService );

						return $routeService->makeAction( $route['name'], $route['method'] );
					} catch ( Exception $e ) {
						@header( 'Content-Type: application/json; charset=UTF-8' );
						wp_send_json_error( [
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
} );
