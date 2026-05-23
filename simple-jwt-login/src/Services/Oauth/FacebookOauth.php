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
class FacebookOauth extends AbstractOauth implements OauthInterface
{
    const PROVIDER_SLUG          = 'facebook';
    const AUTH_URL               = 'https://www.facebook.com/v19.0/dialog/oauth';
    const TOKEN_ENDPOINT         = 'https://graph.facebook.com/v19.0/oauth/access_token';
    const USERINFO_ENDPOINT      = 'https://graph.facebook.com/me';
    const GRAPH_API_VERSION      = 'v19.0';

    // -------------------------------------------------------------------------
    // OauthInterface
    // -------------------------------------------------------------------------

    public function validate()
    {
        if (!isset($this->request['code']) && !isset($this->request['access_token'])) {
            throw new Exception(
                __('The code or access_token parameter is missing from request.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_FACEBOOK_PARAM
            );
        }
    }

    /**
     * @throws Exception
     */
    public function call()
    {
        switch (true) {
            case $this->requestMethod === ServerCall::REQUEST_METHOD_GET:
                $this->handleOauth($this->request['code']);
                break;
            case !empty($this->request['code']):
                $result = $this->exchangeCode(
                    $this->request['code'],
                    $this->getSavedRedirectUri()
                );
                if ($result['status_code'] === 200) {
                    return ['success' => true, 'data' => $result['response']];
                }
                throw new Exception(
                    __(
                        'The code you provided is invalid.' . $this->handleErrorMessage($result['response']),
                        'simple-jwt-login'
                    ),
                    ErrorCodes::ERR_FACEBOOK_INVALID_CODE
                );
            case !empty($this->request['access_token']):
                $accessToken = $this->request['access_token'];
                $this->validateProviderToken($accessToken);
                $email = $this->getUserEmailFromGraph($accessToken);

                return $this->createWpJwtForEmail($email);
        }

        return [];
    }

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
                __('Facebook did not return an access_token.', 'simple-jwt-login'),
                ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN
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
                __('The provided Facebook access_token is invalid.', 'simple-jwt-login'),
                ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN
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
                __('Unable to retrieve user email from Facebook. Ensure the email permission is granted.', 'simple-jwt-login'),
                ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN
            );
        }

        return $userinfo['email'];
    }
}
