<?php

namespace SimpleJWTLogin\Services\Oauth;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\ServerCall;

/**
 * Auth0 OAuth2 / OIDC provider.
 *
 * Supports three flows that mirror the Google integration:
 *  1. Browser OAuth redirect - exchange authorization code via the WP login page button.
 *  2. Code exchange (API)   - POST ?provider=auth0&code={code} → returns raw token response.
 *  3. Token exchange (API)  - POST ?provider=auth0&access_token={token} → returns a WP JWT.
 *
 * Auth0 is domain-based, so all endpoint URLs are derived from the configured domain.
 */
class Auth0Oauth extends AbstractOauth implements OauthInterface
{
    const PROVIDER_SLUG         = 'auth0';
    const IIS                   = 'accounts.auth0.com';
    const TOKEN_ENDPOINT_TPL    = 'https://%s/oauth/token';
    const AUTH_URL_TPL          = 'https://%s/authorize';
    const USERINFO_ENDPOINT_TPL = 'https://%s/userinfo';

    // -------------------------------------------------------------------------
    // OauthInterface
    // -------------------------------------------------------------------------

    public function validate()
    {
        if (!isset($this->request['code']) && !isset($this->request['access_token'])) {
            throw new Exception(
                __('The code or access_token parameter is missing from request.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_AUTH0_PARAM
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
                // Browser OAuth redirect: code is in query string
                $this->handleOauth($this->request['code']);
                break;
            case !empty($this->request['code']):
                // API: exchange authorization code for Auth0 tokens
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
                    ErrorCodes::ERR_AUTH0_INVALID_CODE
                );
            case !empty($this->request['access_token']):
                // API: exchange Auth0 access_token for a WordPress JWT
                $accessToken = $this->request['access_token'];
                $this->validateProviderToken($accessToken);
                $email = $this->getUserEmailFromUserinfo($accessToken);

                return $this->createWpJwtForEmail($email);
        }

        return [];
    }

    // -------------------------------------------------------------------------
    // AbstractOauth hooks
    // -------------------------------------------------------------------------

    protected function getTokenEndpoint()
    {
        return sprintf(self::TOKEN_ENDPOINT_TPL, $this->getAuth0Domain());
    }

    public function getAuthUrl()
    {
        return sprintf(self::AUTH_URL_TPL, $this->getAuth0Domain());
    }

    protected function getClientId()
    {
        return $this->settings->getIntegrationsSettings()->auth0()->getClientId();
    }

    protected function getClientSecret()
    {
        return $this->settings->getIntegrationsSettings()->auth0()->getClientSecret();
    }

    protected function getSavedRedirectUri()
    {
        return $this->settings->getIntegrationsSettings()->auth0()->getExchangeCodeRedirectUri();
    }

    protected function getProviderSlug()
    {
        return self::PROVIDER_SLUG;
    }

    protected function isCreateUserEnabled()
    {
        return $this->settings->getIntegrationsSettings()->auth0()->isCreateUserIfNotExistsEnabled();
    }

    /**
     * After a successful code exchange Auth0 returns an access_token.
     * We call the userinfo endpoint to retrieve the user's email.
     *
     * @param array $tokenResponse
     * @return string
     * @throws Exception
     */
    protected function getEmailFromTokenResponse($tokenResponse)
    {
        if (empty($tokenResponse['access_token'])) {
            throw new Exception(
                __('Auth0 did not return an access_token.', 'simple-jwt-login'),
                ErrorCodes::ERR_AUTH0_INVALID_TOKEN
            );
        }

        return $this->getUserEmailFromUserinfo($tokenResponse['access_token']);
    }

    /**
     * Validate an Auth0 access_token by calling the userinfo endpoint.
     * Throws when the token is rejected (non-200 response).
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
            $this->userinfoEndpoint(),
            ['headers' => ['Authorization' => 'Bearer ' . $token]],
            $statusCode,
            $plainResult
        );

        if ($statusCode !== 200) {
            throw new Exception(
                __('The provided Auth0 access_token is invalid.', 'simple-jwt-login'),
                ErrorCodes::ERR_AUTH0_INVALID_TOKEN
            );
        }
    }

    protected function getInvalidTokenErrorCode()
    {
        return ErrorCodes::ERR_AUTH0_INVALID_TOKEN;
    }

    protected function getUserNotFoundErrorCode()
    {
        return ErrorCodes::ERR_AUTH0_USER_NOT_FOUND;
    }

    // -------------------------------------------------------------------------
    // Auth0-specific public helper (used by BaseService)
    // -------------------------------------------------------------------------

    /**
     * Validate an Auth0 JWT/access_token by calling the userinfo endpoint.
     * Mirrors GoogleOauth::validateIdToken so that BaseService can call it via an instance.
     *
     * @param string $jwt
     * @param \SimpleJWTLogin\Modules\SimpleJWTLoginSettings $settings
     * @return void
     * @throws Exception
     */
    public static function validateIdToken($jwt, $settings)
    {
        $domain   = $settings->getIntegrationsSettings()->auth0()->getDomain();
        $endpoint = sprintf(self::USERINFO_ENDPOINT_TPL, $domain);

        $statusCode  = 400;
        $plainResult = '';
        ServerCall::get(
            $endpoint,
            ['headers' => ['Authorization' => 'Bearer ' . $jwt]],
            $statusCode,
            $plainResult
        );

        if ($statusCode !== 200) {
            throw new Exception(
                __('The provided Auth0 token is invalid.', 'simple-jwt-login'),
                ErrorCodes::ERR_AUTH0_INVALID_TOKEN
            );
        }
    }

    // -------------------------------------------------------------------------
    // Auth0-specific helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve the user's email from Auth0's /userinfo endpoint.
     *
     * @param string $accessToken
     * @return string
     * @throws Exception
     */
    private function getUserEmailFromUserinfo($accessToken)
    {
        $statusCode  = 400;
        $plainResult = '';
        $userinfo = ServerCall::get(
            $this->userinfoEndpoint(),
            ['headers' => ['Authorization' => 'Bearer ' . $accessToken]],
            $statusCode,
            $plainResult
        );

        if ($statusCode !== 200 || empty($userinfo['email'])) {
            throw new Exception(
                __('Unable to retrieve user email from Auth0.', 'simple-jwt-login'),
                ErrorCodes::ERR_AUTH0_INVALID_TOKEN
            );
        }

        return $userinfo['email'];
    }

    /**
     * @return string
     */
    private function userinfoEndpoint()
    {
        return sprintf(self::USERINFO_ENDPOINT_TPL, $this->getAuth0Domain());
    }

    /**
     * @return string
     */
    private function getAuth0Domain()
    {
        return $this->settings->getIntegrationsSettings()->auth0()->getDomain();
    }
}
