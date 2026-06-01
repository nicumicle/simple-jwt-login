<?php

namespace SimpleJWTLogin\Services\Oauth;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\ServerCall;

/**
 * Facebook OAuth2 provider.
 *
 * Supports three flows:
 *  1. Browser OAuth redirect - exchange authorization code via the WP login page button.
 *  2. Code exchange (API)   - POST ?provider=facebook&code={code} -> returns raw token response.
 *  3. Token exchange (API)  - POST ?provider=facebook&access_token={token} -> returns a WP JWT.
 */
class FacebookOauth extends AbstractOauth
{
    const PROVIDER_SLUG          = 'facebook';
    const AUTH_URL               = 'https://www.facebook.com/v19.0/dialog/oauth';
    const TOKEN_ENDPOINT         = 'https://graph.facebook.com/v19.0/oauth/access_token';
    const USERINFO_ENDPOINT      = 'https://graph.facebook.com/me';
    const GRAPH_API_VERSION      = 'v19.0';

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
        return $this->settings->getIntegrationsSettings()->facebook()->getClientId();
    }

    protected function getClientSecret()
    {
        return $this->settings->getIntegrationsSettings()->facebook()->getClientSecret();
    }

    protected function getSavedRedirectUri()
    {
        return $this->settings->getIntegrationsSettings()->facebook()->getExchangeCodeRedirectUri();
    }

    protected function getProviderSlug()
    {
        return self::PROVIDER_SLUG;
    }

    protected function isCreateUserEnabled()
    {
        return $this->settings->getIntegrationsSettings()->facebook()->isCreateUserIfNotExistsEnabled();
    }

    /**
     * @param array $tokenResponse
     * @return string
     * @throws Exception
     */
    protected function getEmailFromTokenResponse($tokenResponse)
    {
        if (empty($tokenResponse['access_token'])) {
            throw new Exception(
                esc_html(__('Facebook did not return an access_token.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN)
            );
        }

        return $this->getUserEmailFromGraph($tokenResponse['access_token']);
    }

    /**
     * Validate a Facebook access_token by calling the Graph API /me endpoint.
     *
     * @param string $token
     * @return void
     * @throws Exception
     */
    protected function validateProviderToken($token)
    {
        $statusCode  = 400;
        $plainResult = '';
        ServerCall::get(
            self::USERINFO_ENDPOINT . '?access_token=' . rawurlencode($token),
            [],
            $statusCode,
            $plainResult
        );

        if ($statusCode !== 200) {
            throw new Exception(
                esc_html(__('The provided Facebook access_token is invalid.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN)
            );
        }
    }

    protected function getInvalidTokenErrorCode()
    {
        return ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN;
    }

    protected function getUserNotFoundErrorCode()
    {
        return ErrorCodes::ERR_FACEBOOK_USER_NOT_FOUND;
    }

    protected function getTokenParamName()
    {
        return 'access_token';
    }

    protected function getMissingParamErrorCode()
    {
        return ErrorCodes::ERR_MISSING_FACEBOOK_PARAM;
    }

    protected function getInvalidCodeErrorCode()
    {
        return ErrorCodes::ERR_FACEBOOK_INVALID_CODE;
    }

    /**
     * @param string $token
     * @return string
     * @throws Exception
     */
    protected function getEmailFromDirectToken($token)
    {
        return $this->getUserEmailFromGraph($token);
    }

    // -------------------------------------------------------------------------
    // Facebook-specific helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve the user's email from Facebook's Graph API /me endpoint.
     *
     * @param string $accessToken
     * @return string
     * @throws Exception
     */
    private function getUserEmailFromGraph($accessToken)
    {
        $statusCode  = 400;
        $plainResult = '';
        $userinfo = ServerCall::get(
            self::USERINFO_ENDPOINT . '?fields=email&access_token=' . rawurlencode($accessToken),
            [],
            $statusCode,
            $plainResult
        );

        if ($statusCode !== 200 || empty($userinfo['email'])) {
            throw new Exception(
                esc_html(__('Unable to retrieve user email from Facebook. Ensure the email permission is granted.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN)
            );
        }

        return $userinfo['email'];
    }
}
