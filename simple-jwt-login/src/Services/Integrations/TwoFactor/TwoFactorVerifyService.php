<?php

namespace SimpleJWTLogin\Services\Integrations\TwoFactor;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Services\ServiceInterface;
use WP_REST_Response;

class TwoFactorVerifyService extends AuthenticateService implements ServiceInterface
{
    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    public function makeAction()
    {
        $this->checkAuthenticationEnabled();
        return $this->verifyTwoFactor();
    }

    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    protected function verifyTwoFactor()
    {
        $bridge = $this->getTwoFactorBridge();

        if (!$bridge->isAvailable()) {
            throw new Exception(
                __('Two-factor authentication plugin is not active.', 'simple-jwt-login'),
                ErrorCodes::ERR_TWO_FACTOR_NOT_ACTIVE
            );
        }

        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new ValidationException(
                __('Interim JWT is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_JWT_IS_MISSING
            );
        }

        $interimPayload = $this->decodeInterimJwt($this->jwt);
        $this->assertIsInterimJwt($interimPayload);

        $userId   = isset($interimPayload['tfa_user_id']) ? (int) $interimPayload['tfa_user_id'] : 0;
        $nonce    = isset($interimPayload['tfa_nonce']) ? (string) $interimPayload['tfa_nonce'] : '';
        $provider = isset($interimPayload['tfa_provider']) ? (string) $interimPayload['tfa_provider'] : '';

        if ($userId === 0 || empty($nonce)) {
            throw new Exception(
                __('Invalid interim JWT claims.', 'simple-jwt-login'),
                ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REQUIRED
            );
        }

        $user = $this->wordPressData->getUserDetailsById($userId);
        if (!$this->wordPressData->isInstanceOfuser($user)) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        if ($bridge->isRateLimited($user)) {
            $delay = $bridge->getTimeDelay($user);
            throw new Exception(
                sprintf(
                    __('Too many failed attempts. Please wait %d seconds.', 'simple-jwt-login'),
                    (int) $delay
                ),
                ErrorCodes::ERR_TWO_FACTOR_RATE_LIMITED
            );
        }

        if (!$bridge->verifyNonce($userId, $nonce)) {
            $this->wordPressData->triggerAction(
                SimpleJWTLoginHooks::AUDIT_2FA_VERIFY_FAILED,
                $userId,
                $this->wordPressData->getUserProperty($user, 'user_email'),
                __('Invalid or expired two-factor nonce.', 'simple-jwt-login')
            );
            throw new Exception(
                __('Invalid or expired two-factor session. Please authenticate again.', 'simple-jwt-login'),
                ErrorCodes::ERR_TWO_FACTOR_INVALID_NONCE
            );
        }

        $code = isset($this->request['code'])
            ? $this->wordPressData->sanitizeTextField($this->request['code'])
            : '';

        if (empty($code)) {
            throw new ValidationException(
                __('The two-factor code is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_TWO_FACTOR_INVALID_CODE
            );
        }

        if (!$this->verifyCodeForProvider($provider, $user, $code, $userId)) {
            $this->wordPressData->triggerAction(
                SimpleJWTLoginHooks::AUDIT_2FA_VERIFY_FAILED,
                $userId,
                $this->wordPressData->getUserProperty($user, 'user_email'),
                __('Invalid two-factor code.', 'simple-jwt-login')
            );
            throw new Exception(
                __('Invalid two-factor code.', 'simple-jwt-login'),
                ErrorCodes::ERR_TWO_FACTOR_INVALID_CODE
            );
        }

        $payload = isset($this->request['payload'])
            ? json_decode(
                stripslashes($this->wordPressData->sanitizeTextField($this->request['payload'])),
                true
            )
            : [];
        if (!is_array($payload)) {
            $payload = [];
        }

        $payload = self::generatePayload($payload, $this->wordPressData, $this->jwtSettings, $user);

        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME)) {
            $payload = $this->wordPressData->triggerFilter(
                SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME,
                $payload,
                $this->request
            );
        }

        $jwt = $this->getJwtWrapper()->encode(
            $payload,
            JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
            $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
        );

        $responseData = ['jwt' => $jwt];

        if ($this->jwtSettings->getAuthenticationSettings()->isRefreshTokenEnabled()) {
            $refreshToken   = $this->generateRefreshToken();
            $tokenExpiresAt = time()
                + ($this->jwtSettings->getAuthenticationSettings()->getAuthJwtRefreshTtl() * 60);
            $this->tokenRepository->insert(
                (int) $this->wordPressData->getUserProperty($user, 'ID'),
                $this->encryptRefreshToken($refreshToken),
                $tokenExpiresAt
            );
            $responseData['refresh_token'] = $refreshToken;
        }

        $response = ['success' => true, 'data' => $responseData];

        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_2FA_VERIFY)) {
            $response = $this->wordPressData->triggerFilter(
                SimpleJWTLoginHooks::HOOK_RESPONSE_2FA_VERIFY,
                $response,
                $user
            );
        }

        $this->wordPressData->triggerAction(
            SimpleJWTLoginHooks::AUDIT_2FA_VERIFY_SUCCESS,
            (int) $this->wordPressData->getUserProperty($user, 'ID'),
            $this->wordPressData->getUserProperty($user, 'user_email')
        );

        return $this->wordPressData->createResponse($response);
    }

    /**
     * Decode and signature-verify the interim JWT using the plugin's own key.
     *
     * @param string $jwt
     * @return array
     * @throws Exception
     */
    protected function decodeInterimJwt($jwt)
    {
        $decoded = $this->getJwtWrapper()->decode(
            $jwt,
            JwtKeyFactory::getFactory($this->jwtSettings)->getPublicKey(),
            [$this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()]
        );
        return (array) $decoded;
    }

    /**
     * @param array $payload
     * @throws Exception
     */
    protected function assertIsInterimJwt($payload)
    {
        if (empty($payload[self::TFA_PENDING_CLAIM])) {
            throw new Exception(
                __('A valid interim two-factor JWT is required.', 'simple-jwt-login'),
                ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REQUIRED
            );
        }
    }

    /**
     * Verify the submitted code against the user's configured 2FA provider.
     * Uses strpos() on the class name to stay resilient to provider class renames.
     *
     * @param string $providerClass
     * @param mixed  $user
     * @param string $code
     * @param int    $userId
     * @return bool
     */
    protected function verifyCodeForProvider($providerClass, $user, $code, $userId)
    {
        if (strpos($providerClass, 'Two_Factor_Totp') !== false
            && class_exists('\Two_Factor_Totp')
        ) {
            $instance = \Two_Factor_Totp::get_instance();
            if (method_exists($instance, 'validate_code_for_user')) {
                return (bool) $instance->validate_code_for_user($user, $code);
            }
        }

        if (strpos($providerClass, 'Two_Factor_Email') !== false
            && class_exists('\Two_Factor_Email')
        ) {
            $instance = \Two_Factor_Email::get_instance();
            if (method_exists($instance, 'validate_token')) {
                return (bool) $instance->validate_token($userId, $code);
            }
        }

        if (strpos($providerClass, 'Two_Factor_Backup_Codes') !== false
            && class_exists('\Two_Factor_Backup_Codes')
        ) {
            $instance = \Two_Factor_Backup_Codes::get_instance();
            if (method_exists($instance, 'validate_code')) {
                return (bool) $instance->validate_code($user, $code);
            }
        }

        return false;
    }
}
