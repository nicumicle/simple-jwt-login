<?php

namespace SimpleJWTLogin\Services\Oauth;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\ServerCall;

class GoogleOauth extends AbstractOauth
{
    const PROVIDER_SLUG   = 'google';
    const IIS             = 'accounts.google.com';
    const AUTH_URL        = 'https://accounts.google.com/o/oauth2/auth';
    const TOKEN_ENDPOINT  = 'https://accounts.google.com/o/oauth2/token';
    const CHECK_TOKEN_URL = 'https://oauth2.googleapis.com/tokeninfo?id_token=%s';

    // -------------------------------------------------------------------------
    // AbstractOauth hooks
    // -------------------------------------------------------------------------

    protected function getTokenEndpoint()
    {
        return self::TOKEN_ENDPOINT;
    }

    public function getAuthUrl()
    {
        return self::AUTH_URL;
    }

    protected function getClientId()
    {
        return $this->settings->getIntegrationsSettings()->google()->getClientId();
    }

    protected function getClientSecret()
    {
        return $this->settings->getIntegrationsSettings()->google()->getClientSecret();
    }

    protected function getSavedRedirectUri()
    {
        return $this->settings->getIntegrationsSettings()->google()->getExchangeCodeRedirectUri();
    }

    protected function getProviderSlug()
    {
        return self::PROVIDER_SLUG;
    }

    protected function isCreateUserEnabled()
    {
        return $this->settings->getIntegrationsSettings()->google()->isCreateUserIfNotExistsEnabled();
    }

    /**
     * @param array $tokenResponse
     * @return string
     */
    protected function getEmailFromTokenResponse($tokenResponse)
    {
        $jwt = $this->getJwtWrapper()->extractDataFromJwt($tokenResponse['id_token']);

        return isset($jwt['payload']['email']) ? $jwt['payload']['email'] : '';
    }

    /**
     * @param string $token
     * @return void
     * @throws Exception
     */
    protected function validateProviderToken($token)
    {
        self::validateIdToken($token);
    }

    protected function getInvalidTokenErrorCode()
    {
        return ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN;
    }

    protected function getUserNotFoundErrorCode()
    {
        return ErrorCodes::ERR_GOOGLE_USER_NOT_FOUND;
    }

    protected function getTokenParamName()
    {
        return 'id_token';
    }

    protected function getMissingParamErrorCode()
    {
        return ErrorCodes::ERR_MISSING_GOOGLE_PARAM;
    }

    protected function getInvalidCodeErrorCode()
    {
        return ErrorCodes::ERR_GOOGLE_INVALID_CODE;
    }

    /**
     * @param string $token
     * @return string
     */
    protected function getEmailFromDirectToken($token)
    {
        $decoded = $this->getJwtWrapper()->extractDataFromJwt($token);

        return isset($decoded['payload']['email']) ? $decoded['payload']['email'] : '';
    }

    // -------------------------------------------------------------------------
    // Google-specific public helper (used by AuthenticateService / views)
    // -------------------------------------------------------------------------

    /**
     * Validate a Google id_token against Google's tokeninfo endpoint.
     *
     * @param string $idToken
     * @return void
     * @throws Exception
     */
    public static function validateIdToken($idToken)
    {
        $statusCode  = 400;
        $plainResult = '';
        ServerCall::get(
            sprintf(self::CHECK_TOKEN_URL, $idToken),
            [],
            $statusCode,
            $plainResult
        );

        if ($statusCode !== 200) {
            throw new Exception(
                esc_html(__('The provided id_token is invalid', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN)
            );
        }
    }
}
