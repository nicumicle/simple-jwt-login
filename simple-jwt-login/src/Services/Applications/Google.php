<?php

namespace SimpleJWTLogin\Services\Applications;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\ServerCall;

class Google extends AbstractOAuthApplication implements ApplicationInterface
{
    const PROVIDER_SLUG   = 'google';
    const IIS             = 'accounts.google.com';
    const AUTH_URL        = 'https://accounts.google.com/o/oauth2/auth';
    const TOKEN_ENDPOINT  = 'https://accounts.google.com/o/oauth2/token';
    const CHECK_TOKEN_URL = 'https://oauth2.googleapis.com/tokeninfo?id_token=%s';

    // -------------------------------------------------------------------------
    // ApplicationInterface
    // -------------------------------------------------------------------------

    public function validate()
    {
        if (!isset($this->request['code']) && !isset($this->request['id_token'])) {
            throw new Exception(
                __('The code or id_token parameter is missing from request.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_GOOGLE_PARAM
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
                    ErrorCodes::ERR_GOOGLE_INVALID_CODE
                );
            case !empty($this->request['id_token']):
                $idToken = $this->request['id_token'];
                $this->validateProviderToken($idToken);
                $decoded = $this->getJwtWrapper()->extractDataFromJwt($idToken);
                $email   = isset($decoded['payload']['email']) ? $decoded['payload']['email'] : '';

                return $this->createWpJwtForEmail($email);
        }

        return [];
    }

    // -------------------------------------------------------------------------
    // AbstractOAuthApplication hooks
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
        return $this->settings->getApplicationsSettings()->google()->getClientId();
    }

    protected function getClientSecret()
    {
        return $this->settings->getApplicationsSettings()->google()->getClientSecret();
    }

    protected function getSavedRedirectUri()
    {
        return $this->settings->getApplicationsSettings()->google()->getExchangeCodeRedirectUri();
    }

    protected function getProviderSlug()
    {
        return self::PROVIDER_SLUG;
    }

    protected function isCreateUserEnabled()
    {
        return $this->settings->getApplicationsSettings()->google()->isCreateUserIfNotExistsEnabled();
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
                __('The provided id_token is invalid', 'simple-jwt-login'),
                ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN
            );
        }
    }
}
