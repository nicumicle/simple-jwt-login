<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;
use WP_REST_Response;
use WP_User;

class AuthenticateService extends BaseService implements ServiceInterface
{
    const TFA_PENDING_CLAIM = 'tfa_pending';

    /**
     * @var TwoFactorBridge|null
     */
    protected $twoFactorBridge;

    /**
     * @param TwoFactorBridge $bridge
     * @return $this
     */
    public function withTwoFactorBridge(TwoFactorBridge $bridge)
    {
        $this->twoFactorBridge = $bridge;
        return $this;
    }

    /**
     * @return TwoFactorBridge
     */
    protected function getTwoFactorBridge()
    {
        if ($this->twoFactorBridge === null) {
            $this->twoFactorBridge = new TwoFactorBridge();
        }
        return $this->twoFactorBridge;
    }

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

        $authSettings = $jwtSettings->getAuthenticationSettings();
        foreach ($authSettings->getJwtPayloadParameters() as $parameter) {
            if ($parameter === AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT
                || !$authSettings->isPayloadDataEnabled($parameter)
            ) {
                continue;
            }

            switch ($parameter) {
                case AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP:
                    $ttl = (int)$authSettings->getAuthJwtTtl() * 60;
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
                    $payload[$parameter] = $authSettings->getAuthIss();
                    break;
            }
        }
        
        $customClaims = $authSettings->getCustomPayloadClaims();
        foreach ($customClaims as $claimKey => $claimValue) {
            if (!in_array($claimKey, AuthenticationSettings::$protectedPayloadKeys, true)) {
                $payload[$claimKey] = $claimValue;
            }
        }

        // Allow developers to create their own payload values inside of the returned JWT
        if ($jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::HOOK_GENERATE_PAYLOAD)) {
            $payload = $wordPressData->applyFilters(SimpleJWTLoginHooks::HOOK_GENERATE_PAYLOAD, $payload, $user);
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
        $this->validateAuthenticationAuthKey();

        return $this->authenticateUser();
    }

    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    public function authenticateUser()
    {
        if (empty($this->request['email']) && empty($this->request['username']) && empty($this->request['login'])) {
            throw new ValidationException(
                esc_html(__('The email, username, or login parameter is missing from the request.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_AUTHENTICATION_MISSING_EMAIL)
            );
        }
        if (empty($this->request['password']) && empty($this->request['password_hash'])) {
            throw new ValidationException(
                esc_html(__('The password or password_hash parameter is missing from request.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_AUTHENTICATION_MISSING_PASSWORD)
            );
        }

        $authSettings = $this->jwtSettings->getAuthenticationSettings();
        if (!empty($this->request['password_hash']) && !$authSettings->isAuthPasswordHashAllowed()) {
            throw new ValidationException(
                esc_html(__('Authentication with password_hash is not enabled.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_AUTHENTICATION_PASSWORD_HASH_NOT_ALLOWED)
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
            if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_LOGIN_FAILED)) {
                $this->wordPressData->doAction(
                    SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_FAILED,
                    null,
                    $attemptedEmail,
                    __('Wrong user credentials.', 'simple-jwt-login')
                );
            }
            throw new Exception(
                esc_html(__('Wrong user credentials.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS)
            );
        }

        $userId    = (int) $this->wordPressData->getUserProperty($user, 'ID');
        $userEmail = (string) $this->wordPressData->getUserProperty($user, 'user_email');

        $password = isset($this->request['password'])
            ? $this->wordPressData->wpSlash($this->request['password'])
            : null;
        $passwordHash = isset($this->request['password_hash'])
            ? $this->request['password_hash']
            : null;

        if ($authSettings->isAuthPasswordBase64Encoded()) {
            if ($password !== null) {
                $password = base64_decode($password);
            }
            if ($passwordHash !== null) {
                $passwordHash = base64_decode($passwordHash);
            }
        }

        $dbPassword = $this->wordPressData->getUserPassword($user);
        $passwordMatch = $this->wordPressData->checkPassword($password, $passwordHash, $dbPassword);

        if (!$passwordMatch) {
            if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_LOGIN_FAILED)) {
                $this->wordPressData->doAction(
                    SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_FAILED,
                    $userId,
                    $userEmail,
                    __('Wrong user credentials.', 'simple-jwt-login')
                );
            }
            throw new Exception(
                esc_html(__('Wrong user credentials.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS)
            );
        }

        $challengeResponse = $this->handleTwoFactorChallenge($user);
        if ($challengeResponse !== null) {
            return $challengeResponse;
        }

        $payload = self::generatePayload(
            [],
            $this->wordPressData,
            $this->jwtSettings,
            $user
        );

        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME)) {
            $payload = $this->wordPressData->applyFilters(
                SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME,
                $payload,
                $this->request
            );
        }

        $customHeaderClaims = $authSettings->getCustomHeaderClaims();
        $jwt = $this->getJwtWrapper()->encode(
            $payload,
            JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
            $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm(),
            empty($customHeaderClaims) ? null : $customHeaderClaims
        );

        $responseData = ['jwt' => $jwt];

        if ($authSettings->isRefreshTokenEnabled()) {
            $refreshToken = $this->generateRefreshToken();
            $tokenExpiresAt = time() + ($authSettings->getAuthJwtRefreshTtl() * 60);

            $this->tokenRepository->insert(
                $userId,
                $this->encryptRefreshToken($refreshToken),
                $tokenExpiresAt
            );

            $responseData['refresh_token'] = $refreshToken;
        }

        $response = [
            'success' => true,
            'data'    => $responseData,
        ];
        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_AUTH_USER)) {
            $response = $this->wordPressData->applyFilters(
                SimpleJWTLoginHooks::HOOK_RESPONSE_AUTH_USER,
                $response,
                $user
            );
        }

        if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_LOGIN_SUCCESS)) {
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SUCCESS,
                $userId,
                $userEmail
            );
        }

        (new WebhooksService($this->jwtSettings, $this->webhookLogRepository))->dispatch(
            WebhooksSettings::EVENT_AUTH,
            [
                'user_id'    => $userId,
                'user_email' => $userEmail,
            ]
        );

        return $this->wordPressData->createResponse($response);
    }

    /**
     * Check if the user has 2FA enabled and issue a challenge if so.
     * Returns null when 2FA is not active or the user does not have 2FA configured,
     * letting the normal JWT issuance flow continue.
     *
     * @param \WP_User $user
     * @return \WP_REST_Response|null
     * @throws Exception
     */
    protected function handleTwoFactorChallenge($user)
    {
        $tfaSettings = $this->jwtSettings->getIntegrationsSettings()->twoFactor();
        if (!$tfaSettings->isEnabled()) {
            return null;
        }

        $bridge = $this->getTwoFactorBridge();
        if (!$bridge->isAvailable()) {
            return null;
        }

        if (!$bridge->isUserUsing2FA($user)) {
            return null;
        }

        // Respect the two_factor_user_api_login_enable filter: when it returns true
        // the admin has explicitly opted the user out of the 2FA challenge for API logins.
        $apiLoginEnabled = $this->wordPressData->applyFilters(
            'two_factor_user_api_login_enable',
            false,
            $user
        );
        if ($apiLoginEnabled === true) {
            return null;
        }

        $userId = (int) $this->wordPressData->getUserProperty($user, 'ID');

        $nonce = $bridge->createNonce($userId);
        if ($nonce === false) {
            throw new Exception(
                esc_html(__('Unable to create two-factor nonce.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_TWO_FACTOR_INVALID_NONCE)
            );
        }

        $provider      = $bridge->getPrimaryProvider($user);
        $providerClass = $provider !== null ? get_class($provider) : '';

        $ttlSeconds = $tfaSettings->getInterimTtl() * 60;
        $interimPayload = [
            'iat'                    => time(),
            'exp'                    => time() + $ttlSeconds,
            self::TFA_PENDING_CLAIM  => 1,
            'tfa_user_id'            => $userId,
            'tfa_nonce'              => $nonce['key'],
            'tfa_provider'           => $providerClass,
        ];

        $interimJwt = $this->getJwtWrapper()->encode(
            $interimPayload,
            JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
            $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
        );

        if ($provider !== null
            && strpos($providerClass, 'Two_Factor_Email') !== false
            && method_exists($provider, 'generate_and_email_token')
        ) {
            $provider->generate_and_email_token($user);
        }

        $response = [
            'success' => true,
            'data'    => [
                'jwt'                 => $interimJwt,
                'two_factor_required' => true,
                'provider'            => $providerClass,
            ],
        ];

        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_2FA_CHALLENGE)) {
            $response = $this->wordPressData->applyFilters(
                SimpleJWTLoginHooks::HOOK_RESPONSE_2FA_CHALLENGE,
                $response,
                $user
            );
        }

        if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_2FA_CHALLENGE_ISSUED)) {
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_2FA_CHALLENGE_ISSUED,
                $userId,
                $this->wordPressData->getUserProperty($user, 'user_email')
            );
        }

        return $this->wordPressData->createResponse($response);
    }

    /**
     * Generate a cryptographically secure random refresh token
     * @return string
     */
    protected function generateRefreshToken()
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
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
                esc_html(
                    sprintf(
                        /* translators: %s: client IP address */
                        __('You are not allowed to Authenticate from this IP: %s', 'simple-jwt-login'),
                        $this->serverHelper->getClientIP()
                    )
                ),
                absint(ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP)
            );
        }
    }

    /**
     * @throws Exception
     */
    protected function checkAuthenticationEnabled()
    {
        if (!$this->jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled()) {
            throw new Exception(
                esc_html(__('Authentication is not enabled.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_AUTHENTICATION_IS_NOT_ENABLED)
            );
        }
    }

    /**
     * @param bool|null $isRequired
     *
     * @throws Exception
     */
    protected function validateAuthenticationAuthKey($isRequired = null)
    {
        $required = $isRequired !== null
            ? $isRequired
            : $this->jwtSettings->getAuthenticationSettings()->isAuthKeyRequired();
        if ($required) {
            $this->validateAuthKey();
        }
    }
}
