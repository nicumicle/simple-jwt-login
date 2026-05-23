<?php

namespace SimpleJWTLogin\Services\Oauth;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\ServerCall;

/**
 * GitHub OAuth2 provider.
 *
 * Supports three flows:
 *  1. Browser OAuth redirect - exchange authorization code via the WP login page button.
 *  2. Code exchange (API)   - POST ?provider=github&code={code} -> returns raw token response.
 *  3. Token exchange (API)  - POST ?provider=github&access_token={token} -> returns a WP JWT.
 *
 * GitHub's token endpoint returns JSON only when Accept: application/json is sent,
 * so exchangeCode is overridden to include that header.
 * Email is retrieved from GET /user/emails (requires the "user:email" scope).
 */
class GithubOauth extends AbstractOauth implements OauthInterface
{
    const PROVIDER_SLUG      = 'github';
    const AUTH_URL           = 'https://github.com/login/oauth/authorize';
    const TOKEN_ENDPOINT     = 'https://github.com/login/oauth/access_token';
    const USERINFO_ENDPOINT  = 'https://api.github.com/user/emails';
    const USER_AGENT         = 'simple-jwt-login';

    // -------------------------------------------------------------------------
    // OauthInterface
    // -------------------------------------------------------------------------

    public function validate()
    {
        if (!isset($this->request['code']) && !isset($this->request['access_token'])) {
            throw new Exception(
                __('The code or access_token parameter is missing from request.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_GITHUB_PARAM
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
                    ErrorCodes::ERR_GITHUB_INVALID_CODE
                );
            case !empty($this->request['access_token']):
                $accessToken = $this->request['access_token'];
                $this->validateProviderToken($accessToken);
                $email = $this->getPrimaryEmailFromGithub($accessToken);

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
        return $this->settings->getIntegrationsSettings()->github()->getClientId();
    }

    protected function getClientSecret()
    {
        return $this->settings->getIntegrationsSettings()->github()->getClientSecret();
    }

    protected function getSavedRedirectUri()
    {
        return $this->settings->getIntegrationsSettings()->github()->getExchangeCodeRedirectUri();
    }

    protected function getProviderSlug()
    {
        return self::PROVIDER_SLUG;
    }

    protected function isCreateUserEnabled()
    {
        return $this->settings->getIntegrationsSettings()->github()->isCreateUserIfNotExistsEnabled();
    }

    /**
     * GitHub's token endpoint returns URL-encoded text by default.
     * Override exchangeCode to request JSON via the Accept header.
     *
     * @param string $code
     * @param string $redirectUri
     * @return array{status_code: int, response: array}
     */
    public function exchangeCode($code, $redirectUri)
    {
        $response = wp_remote_post(
            self::TOKEN_ENDPOINT,
            [
                'headers' => [
                    'Accept'     => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ],
                'body' => [
                    'client_id'     => $this->getClientId(),
                    'client_secret' => $this->getClientSecret(),
                    'redirect_uri'  => $redirectUri,
                    'code'          => $code,
                ],
            ]
        );

        return [
            'status_code' => (int) wp_remote_retrieve_response_code($response),
            'response'    => json_decode(wp_remote_retrieve_body($response), true),
        ];
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
                __('GitHub did not return an access_token.', 'simple-jwt-login'),
                ErrorCodes::ERR_GITHUB_INVALID_TOKEN
            );
        }

        return $this->getPrimaryEmailFromGithub($tokenResponse['access_token']);
    }

    /**
     * Validate a GitHub access_token by calling the /user/emails endpoint.
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
            self::USERINFO_ENDPOINT,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'User-Agent'    => self::USER_AGENT,
                    'Accept'        => 'application/vnd.github+json',
                ],
            ],
            $statusCode,
            $plainResult
        );

        if ($statusCode !== 200) {
            throw new Exception(
                __('The provided GitHub access_token is invalid.', 'simple-jwt-login'),
                ErrorCodes::ERR_GITHUB_INVALID_TOKEN
            );
        }
    }

    protected function getInvalidTokenErrorCode()
    {
        return ErrorCodes::ERR_GITHUB_INVALID_TOKEN;
    }

    protected function getUserNotFoundErrorCode()
    {
        return ErrorCodes::ERR_GITHUB_USER_NOT_FOUND;
    }

    // -------------------------------------------------------------------------
    // GitHub-specific helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve the user's primary verified email from GitHub's /user/emails endpoint.
     *
     * @param string $accessToken
     * @return string
     * @throws Exception
     */
    private function getPrimaryEmailFromGithub($accessToken)
    {
        $statusCode  = 400;
        $plainResult = '';
        $emails = ServerCall::get(
            self::USERINFO_ENDPOINT,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'User-Agent'    => self::USER_AGENT,
                    'Accept'        => 'application/vnd.github+json',
                ],
            ],
            $statusCode,
            $plainResult
        );

        if ($statusCode !== 200 || !is_array($emails)) {
            throw new Exception(
                __('Unable to retrieve user email from GitHub.', 'simple-jwt-login'),
                ErrorCodes::ERR_GITHUB_INVALID_TOKEN
            );
        }

        foreach ($emails as $entry) {
            if (!empty($entry['primary']) && !empty($entry['verified']) && !empty($entry['email'])) {
                return $entry['email'];
            }
        }

        throw new Exception(
            __('No verified primary email found in the GitHub account.', 'simple-jwt-login'),
            ErrorCodes::ERR_GITHUB_INVALID_TOKEN
        );
    }
}
