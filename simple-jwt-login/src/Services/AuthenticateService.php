<?php

namespace SimpleJWTLogin\Services;

use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use WP_REST_Response;
use Exception;
use WP_User;

class AuthenticateService extends BaseService implements ServiceInterface
{
    /**
     * @param array $payload
     * @param WordPressDataInterface $wordPressData
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param WP_User $user
     *
     * @return array
     */
    public static function generatePayload(
        $payload,
        $wordPressData,
        $jwtSettings,
        $user
    ) {
        $payload[AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT] = time();

        foreach ($jwtSettings->getAuthenticationSettings()->getJwtPayloadParameters() as $parameter) {
            if ($parameter === AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT
                || $jwtSettings->getAuthenticationSettings()->isPayloadDataEnabled($parameter) === false
            ) {
                continue;
            }

            switch ($parameter) {
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP:
                    $ttl = (int)$jwtSettings->getAuthenticationSettings()->getAuthJwtTtl() * 60;
                    $payload[$parameter] = time() + $ttl;
                    break;
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_ID:
                    $payload[$parameter] = $wordPressData->getUserProperty($user, 'ID');
                    break;
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL:
                    $payload[$parameter] = $wordPressData->getUserProperty($user, 'user_email');
                    break;
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_SITE:
                    $payload[$parameter] = $wordPressData->getSiteUrl();
                    break;
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME:
                    $payload[$parameter] = $wordPressData->getUserProperty($user, 'user_login');
                    break;
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_ISS:
                    $payload[$parameter] = $jwtSettings->getAuthenticationSettings()->getAuthIss();
                    break;
            }
        }
        
        // Allow developers to create their own payload values inside of the returned JWT
        if ($jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::HOOK_GENERATE_PAYLOAD)) {
            $payload = $wordPressData->triggerFilter(SimpleJWTLoginHooks::HOOK_GENERATE_PAYLOAD, $payload, $user);
        }

        return $payload;
    }

    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    public function makeAction()
    {
        $this->checkAuthenticationEnabled();
        $this->checkAllowedIPAddress();
        $this->validateAuthenticationAuthKey(ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED);

        return $this->authenticateUser();
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @return WP_REST_Response
     * @throws Exception
     */
    public function authenticateUser()
    {
        if (!isset($this->request['email']) && !isset($this->request['username']) && !isset($this->request['login'])) {
            throw new Exception(
                __('The email, username, or login parameter is missing from the request.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_MISSING_EMAIL
            );
        }
        if (!isset($this->request['password']) && !isset($this->request['password_hash'])) {
            throw new Exception(
                __('The password or password_hash parameter is missing from request.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_MISSING_PASSWORD
            );
        }

        $user = null;
        switch (true) {
            case isset($this->request['username']):
                // login by username
                $user = $this->wordPressData->getUserByUserLogin(
                    $this->wordPressData->sanitizeTextField($this->request['username'])
                );
                break;
            case isset($this->request['email']):
                // login by email
                $user = $this->wordPressData->getUserDetailsByEmail(
                    $this->wordPressData->sanitizeTextField($this->request['email'])
                );
                break;
            case isset($this->request['login']):
                // login by username or email
                $loginParameter = $this->request['login'];
                $user = $this->wordPressData->getUserByUserLogin($loginParameter);
                if (!$user && strpos($loginParameter, '@') !== false) {
                    $user = $this->wordPressData->getUserDetailsByEmail($loginParameter);
                }
                break;
        }

        if (empty($user)) {
            $attemptedEmail = isset($this->request['email'])
                ? $this->request['email']
                : (isset($this->request['username']) ? $this->request['username'] : null);
            $this->wordPressData->triggerAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_FAILED,
                null,
                $attemptedEmail,
                __('Wrong user credentials.', 'simple-jwt-login')
            );
            throw new Exception(
                __('Wrong user credentials.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_WRONG_CREDENTIALS
            );
        }

        $password = isset($this->request['password'])
            ? $this->wordPressData->sanitizeTextField($this->request['password'])
            : null;
        $passwordHash = isset($this->request['password_hash'])
            ? $this->wordPressData->sanitizeTextField($this->request['password_hash'])
            : null;

        if ($this->jwtSettings->getAuthenticationSettings()->isAuthPasswordBase64Encoded()) {
            if ($password !== null) {
                $password = base64_decode($password);
            }
            if ($passwordHash !== null) {
                $passwordHash = base64_decode($passwordHash);
            }
        }

        $dbPassword = $this->wordPressData->getUserPassword($user);
        $passwordMatch = $this->wordPressData->checkPassword($password, $passwordHash, $dbPassword);

        if ($passwordMatch === false) {
            $this->wordPressData->triggerAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_FAILED,
                $this->wordPressData->getUserProperty($user, 'ID'),
                $this->wordPressData->getUserProperty($user, 'user_email'),
                __('Wrong user credentials.', 'simple-jwt-login')
            );
            throw new Exception(
                __('Wrong user credentials.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_WRONG_CREDENTIALS
            );
        }

        //Generate payload
        $payload = isset($this->request['payload'])
            ? json_decode(
                stripslashes(
                    $this->wordPressData->sanitizeTextField($this->request['payload'])
                ),
                true
            )
            : [];

        $payload = self::generatePayload(
            $payload,
            $this->wordPressData,
            $this->jwtSettings,
            $user
        );

        if ($this->jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME)) {
            $payload = $this->wordPressData->triggerFilter(
                SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME,
                $payload,
                $this->request
            );
        }

        $jwt = JWT::encode(
            $payload,
            JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
            $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
        );

        $responseData = ['jwt' => $jwt];

        if ($this->jwtSettings->getAuthenticationSettings()->isRefreshTokenEnabled()) {
            $refreshToken = $this->generateRefreshToken();
            $tokenExpiresAt = time() + ($this->jwtSettings->getAuthenticationSettings()->getAuthJwtRefreshTtl() * 60);

            $this->tokenRepository->insert(
                $this->wordPressData->getUserProperty($user, 'ID'),
                $this->encryptRefreshToken($refreshToken),
                $tokenExpiresAt
            );

            $responseData['refresh_token'] = $refreshToken;
        }

        $response = [
            'success' => true,
            'data'    => $responseData,
        ];
        if ($this->jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::HOOK_RESPONSE_AUTH_USER)) {
            $response = $this->wordPressData->triggerFilter(
                SimpleJWTLoginHooks::HOOK_RESPONSE_AUTH_USER,
                $response,
                $user
            );
        }

        $this->wordPressData->triggerAction(
            SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SUCCESS,
            $this->wordPressData->getUserProperty($user, 'ID'),
            $this->wordPressData->getUserProperty($user, 'user_email')
        );

        (new WebhooksService($this->jwtSettings, $this->webhookLogRepository))->dispatch(
            WebhooksSettings::EVENT_AUTH,
            [
                'user_id'    => $this->wordPressData->getUserProperty($user, 'ID'),
                'user_email' => $this->wordPressData->getUserProperty($user, 'user_email'),
            ]
        );

        return $this->wordPressData->createResponse($response);
    }

    /**
     * Generate a cryptographically secure random refresh token
     * @return string
     */
    protected function generateRefreshToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Encrypt refresh token using HMAC-SHA256 with the configured key
     * Falls back to JWT decryption key for backward compatibility
     * @param string $refreshToken
     * @return string
     */
    protected function encryptRefreshToken($refreshToken)
    {
        $key = $this->jwtSettings->getAuthenticationSettings()->getRefreshTokenKey();
        if (empty($key)) {
            $key = $this->jwtSettings->getGeneralSettings()->getDecryptionKey();
        }

        return hash_hmac('sha256', $refreshToken, $key);
    }

    /**
     * @throws Exception
     */
    protected function checkAllowedIPAddress()
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
    }

    /**
     * @throws Exception
     */
    protected function checkAuthenticationEnabled()
    {
        if ($this->jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled() === false) {
            throw new Exception(
                __('Authentication is not enabled.', 'simple-jwt-login'),
                ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED
            );
        }
    }

    /**
     * @param int $errrCode
     * @param bool|null $isRequired
     *
     * @throws Exception
     */
    protected function validateAuthenticationAuthKey($errrCode, $isRequired = null)
    {
        $required = $isRequired !== null
            ? $isRequired
            : $this->jwtSettings->getAuthenticationSettings()->isAuthKeyRequired();
        if ($required && $this->validateAuthKey() === false) {
            throw new Exception(
                sprintf(
                    __('Invalid Auth Code ( %s ) provided.', 'simple-jwt-login'),
                    $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()
                ),
                $errrCode
            );
        }
    }
}
