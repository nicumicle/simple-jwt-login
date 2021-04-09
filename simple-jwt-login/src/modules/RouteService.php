<?php


namespace SimpleJWTLogin\Modules;


use SimpleJWTLogin\ErrorCodes;

class RouteService {
	const LOGIN_ROUTE = 'autologin';
	const REGISTER_ROUTE_OLD = 'register';
	const USER_ROUTE = 'users';
	const AUTHENTICATION_ROUTE = 'auth';
	const AUTHENTICATION_REFRESH_ROUTE = 'auth/refresh';
	const AUTHENTICATION_VALIDATE_ROUTE = 'auth/validate';
	const AUTHENTICATION_REVOKE = 'auth/revoke';

	const METHOD_POST = 'POST';
	const METHOD_GET = 'GET';
	const METHOD_DELETE = 'DELETE';
	const METHOD_PUT = 'PUT';

	/**
	 * @var SimpleJWTLoginService
	 */
	private $jwtService;

	/**
	 * @param SimpleJWTLoginService $jwtService
	 *
	 * @return $this
	 */
	public function withService( SimpleJWTLoginService $jwtService ) {
		$this->jwtService = $jwtService;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAllRoutes() {
		return [
			[ 'name' => self::LOGIN_ROUTE, 'method' => self::METHOD_GET ],
			[ 'name' => self::REGISTER_ROUTE_OLD, 'method' => self::METHOD_POST ],
			[ 'name' => self::USER_ROUTE, 'method' => self::METHOD_POST ],
			[ 'name' => self::USER_ROUTE, 'method' => self::METHOD_DELETE ],
			[ 'name' => self::AUTHENTICATION_ROUTE, 'method' => self::METHOD_POST ],
			[ 'name' => self::AUTHENTICATION_REFRESH_ROUTE, 'method' => self::METHOD_POST ],
			[ 'name' => self::AUTHENTICATION_VALIDATE_ROUTE, 'method' => self::METHOD_GET ],
            [ 'name' => self::AUTHENTICATION_REVOKE, 'method' => self::METHOD_POST],
		];
	}

	/**
	 * @param string $routeName
	 *
	 * @param string $method
	 *
	 * @return void|\WP_REST_Response
	 * @throws \Exception
	 */
	public function makeAction( $routeName, $method ) {
		switch ( $routeName ) {
			case self::LOGIN_ROUTE:
				return $this->jwtService->doLogin();
				break;
			case self::USER_ROUTE:
				switch ( $method ) {
					case self::METHOD_POST:
						$this->jwtService->validateRegisterUser();

						return $this->jwtService->createUser();
						break;
					case self::METHOD_DELETE:
						return $this->jwtService->deleteUser();
						break;
					default:
						throw new \Exception( __( 'Invalid method for this route.', 'simple-jwt-login' ),
							ErrorCodes::ERR_INVALID_ROUTE_METHOD );
				}
				break;

			/**
			 * @since 2.0.0
			 */
			case self::AUTHENTICATION_ROUTE:
				return $this->jwtService->authenticateUser();
				break;


			case self::AUTHENTICATION_REFRESH_ROUTE:
				return $this->jwtService->refreshJwt();
				break;

			case self::AUTHENTICATION_VALIDATE_ROUTE:
				return $this->jwtService->validateAuth();
				break;

            case self::AUTHENTICATION_REVOKE:
                return $this->jwtService->revokeToken();
                break;
			/**
			 * @deprecated 1.5.0 This route should not be used anymore
			 */
			case self::REGISTER_ROUTE_OLD:
				$this->jwtService->validateRegisterUser();

				return $this->jwtService->createUser();
				break;
			default:
				throw new \Exception( __( 'Invalid route name.', 'simple-jwt-login' ),
					ErrorCodes::ERR_INVALID_ROUTE_NAME );
		}
	}

}
