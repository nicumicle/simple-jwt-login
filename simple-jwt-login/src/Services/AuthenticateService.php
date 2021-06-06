<?php
namespace SimpleJWTLogin\Services;

use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use WP_REST_Response;
use Exception;

class AuthenticateService extends BaseService implements ServiceInterface
{
    const ACTION_NAME_AUTHENTICATE = 1;
    const ACTION_NAME_REFRESH_JWT = 2;
    const ACTION_NAME_VALIDATE_JWT = 3;
    const ACTION_NAME_REVOKE_JWT = 4;

    /**
     * @param int|null $actionName
     * @return WP_REST_Response
     * @throws Exception
     */
    public function makeAction($actionName = null)
    {
        $allowedIpsString = trim($this->jwtSettings->getAuthenticationSettings()->getAllowedIps());
        if (!empty($allowedIpsString) && !$this->serverHelper->isClientIpInList($allowedIpsString)) {
            throw new Exception(
                sprintf(
                    __('You are not allowed to Authenticate from this IP: %s', 'simple-jwt-login'),
                    $this->serverHelper->getClientIP()
                ),
                ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP
            );
        }

        switch ($actionName) {
            case self::ACTION_NAME_AUTHENTICATE:
                return $this->authenticateUser();
            case self::ACTION_NAME_VALIDATE_JWT:
                return $this->validateAuth();
            case self::ACTION_NAME_REVOKE_JWT:
                return $this->revokeToken();
            case self::ACTION_NAME_REFRESH_JWT:
                return $this->refreshJwt();
            default:
                throw new Exception('Invalid action');
        }
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @return WP_REST_Response
     * @throws Exception
     */
    public function authenticateUser()
    {
        //Validate authentication
        if ($this->jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled() === false) {
            throw new Exception(
                __('Authentication is not enabled.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED
            );
        }
        if (!isset($this->request['email']) && !isset($this->request['username'])) {
            throw new Exception(
                __('The email or username parameter is missing from request.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_MISSING_EMAIL
            );
        }
        if (!isset($this->request['password'])) {
            throw new Exception(
                __('The password parameter is missing from request.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_MISSING_PASSWORD
            );
        }
        $user = isset($this->request['username'])
            ? $this->wordPressData->getUserByUserLogin($this->request['username'])
            : $this->wordPressData->getUserDetailsByEmail($this->request['email']);

        if (empty($user)) {
            throw new Exception(
                __('Wrong user credentials.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_WRONG_CREDENTIALS
            );
        }
        $password = $this->request['password'];
        $dbPassword = $user->get('user_pass');

        $passwordMatch = wp_check_password($password, $dbPassword);
        if ($passwordMatch === false) {
            throw new Exception(
                __('Wrong user credentials.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_WRONG_CREDENTIALS
            );
        }

        //Generate payload
        $payload = isset($this->request['payload'])
            ? json_decode(stripslashes($this->request['payload']), true)
            : [];
        $payload[AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT] = time();

        foreach ($this->jwtSettings->getAuthenticationSettings()->getJwtPayloadParameters() as $parameter) {
            if ($parameter === AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT
                || $this->jwtSettings->getAuthenticationSettings()->isPayloadDataEnabled($parameter) === false
            ) {
                continue;
            }

            switch ($parameter) {
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP:
                    $ttl = (int)$this->jwtSettings->getAuthenticationSettings()->getAuthJwtTtl() * 60;
                    $payload[$parameter] = time() + $ttl;
                    break;
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_ID:
                    $payload[$parameter] = $user->get('id');
                    break;
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL:
                    $payload[$parameter] = $user->get('user_email');
                    break;
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_SITE:
                    $payload[$parameter] = $this->wordPressData->getSiteUrl();
                    break;
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME:
                    $payload[$parameter] = $user->get('user_login');
                    break;
            }
        }

        if ($this->jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME)) {
            $payload = $this->wordPressData->triggerFilter(
                SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME,
                $payload,
                $this->request
            );
        }

        //Display result
        return $this->wordPressData->createResponse([
            'success' => true,
            'data' => [
                'jwt' => JWT::encode(
                    $payload,
                    JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
                    $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
                )
            ]
        ]);
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @return WP_REST_Response
     * @throws Exception
     */
    public function refreshJwt()
    {
        //Validate authentication
        if ($this->jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled() === false) {
            throw new Exception(
                __('Authentication is not enabled.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED
            );
        }

        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new Exception(
                __('JWT is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH
            );
        }

        try {
            JWT::$leeway = self::JWT_LEEVAY;
            JWT::decode(
                $this->jwt,
                JwtKeyFactory::getFactory($this->jwtSettings)->getPublicKey(),
                [$this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()]
            );
        } catch (Exception $e) {
            if ($e->getCode() !== ErrorCodes::ERR_TOKEN_EXPIRED) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
        }

        $payload = $this->getPayloadFromJWT($this->jwt);

        if ($payload === null) {
            throw new Exception(
                __('There was an error with your JWT and we can not refresh it.', 'simple-jwt-login'),
                ErrorCodes::ERR_JWT_REFRESH_NULL_PAYLOAD
            );
        }

        $result = $this->getUserParameterValueFromPayload(
            $payload,
            $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
        );

        $user = $this->getUserDetails($result);
        if ($user !== null) {
            $userMeta = $this->wordPressData->getUserMeta($user->get('id'), SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
            foreach ($userMeta as $key) {
                if ($key === $this->jwt) {
                    throw new Exception(__('Jwt is invalid.', 'simple-jwt-login'), ErrorCodes::ERR_REVOKED_TOKEN);
                }
            }
        }

        if (isset($payload[AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP])) {
            $refreshTimeToLive =
                $payload[AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP]
                + $this->jwtSettings->getAuthenticationSettings()->getAuthJwtRefreshTtl() * 60;

            if (time() > $refreshTimeToLive) {
                throw new Exception(
                    __('JWT is too old to be refreshed.', 'simple-jwt-login'),
                    ErrorCodes::ERR_JWT_REFRESH_JWT_TOO_OLD
                );
            }

            $payload[AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP] = time()
                + ($this->jwtSettings->getAuthenticationSettings()->getAuthJwtTtl() * 60);
        }

        //Display result
        return $this->wordPressData->createResponse([
            'success' => true,
            'data' => [
                'jwt' => JWT::encode(
                    $payload,
                    JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
                    $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
                )
            ]
        ]);
    }

    /**
     * @throws Exception
     */
    public function revokeToken()
    {
        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new Exception(
                __('The `jwt` parameter is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE
            );
        }

        $loginParameter = $this->validateJWTAndGetUserValueFromPayload(
            $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
        );
        $user = $this->getUserDetails($loginParameter);
        if ($user === null) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        $userRevokedTokens = $this->getUserRevokedTokensFromDatabase($user->get('id'));
        $this->cleanUpUserExpiredTokens($userRevokedTokens, $user->get('id'));
        $this->checkIfTokenIsAlreadyRevoked($userRevokedTokens);

        $this->wordPressData->addUserMeta(
            $user->get('id'),
            SimpleJWTLoginSettings::REVOKE_TOKEN_KEY,
            $this->jwt
        );

        return $this->wordPressData->createResponse([
            'success' => true,
            'message' => __('Token was revoked.', 'simple-jwt-login'),
            'data' => [
                'jwt' => [
                    $this->jwt
                ]
            ]
        ]);
    }

    /**
     * @return  WP_REST_Response
     * @throws Exception
     */
    private function validateAuth()
    {
        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new Exception(
                __('The `jwt` parameter is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE
            );
        }

        $loginParameter = $this->validateJWTAndGetUserValueFromPayload(
            $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
        );

        $user = $this->getUserDetails($loginParameter);
        if ($user === null) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        $this->validateJwtRevoked($user->get('id'), $this->jwt);

        $userArray  = $user->to_array();
        if (isset($userArray['user_pass'])) {
            unset($userArray['user_pass']);
        }
        $jwtParameters = [];
        $jwtParameters['token'] = $this->jwt;
        list($header, $payload) = explode('.', $this->jwt);
        $jwtParameters['header'] = json_decode(base64_decode($header), true);
        $jwtParameters['payload'] = json_decode(base64_decode($payload), true);
        if (isset($jwtParameters['payload']['exp'])) {
            $jwtParameters['expire_in'] = $jwtParameters['payload']['exp'] - time();
        }

        return $this->wordPressData->createResponse([
            'success' => true,
            'data'    => [
                'user' => $userArray,
                'jwt' => [
                    $jwtParameters
                ]
            ]
        ]);
    }

    /**
     * @param int $userId
     * @return mixed
     */
    protected function getUserRevokedTokensFromDatabase($userId)
    {
        return $this->wordPressData->getUserMeta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
    }

    /**
     * @param array $revokedTokens
     * @param int $userId
     */
    private function cleanUpUserExpiredTokens($revokedTokens, $userId)
    {
        if (empty($revokedTokens)) {
            return;
        }
        $currentTime = time();
        foreach ($revokedTokens as $token) {
            $payload = $this->getPayloadFromJWT($token);
            if (isset($payload['exp']) && $payload['exp'] < $currentTime) {
                $this->wordPressData->deleteUserMeta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY, $token);
            }
        }
    }


    /**
     * @param string $jwt
     * @return array|null
     */
    private function getPayloadFromJWT($jwt)
    {
        $jwtParts = explode('.', $jwt);
        return isset($jwtParts[1])
            ? json_decode(base64_decode($jwtParts[1]), true)
            : null;
    }

    /**
     * @param array $userRevokedTokens
     * @return bool
     * @throws Exception
     */
    private function checkIfTokenIsAlreadyRevoked($userRevokedTokens)
    {
        if (empty($userRevokedTokens)) {
            return false;
        }
        foreach ($userRevokedTokens as $token) {
            if ($token === $this->jwt) {
                throw new Exception('Token was already revoked.');
            }
        }

        return false;
    }
}
