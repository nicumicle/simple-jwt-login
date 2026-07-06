<?php

namespace SimpleJwtLoginTests\Unit\Services\Oauth;

use SimpleJWTLogin\Services\Oauth\AbstractOauth;

/**
 * Concrete subclass that does NOT override exchangeCode(), so the real
 * AbstractOauth::exchangeCode() (wp_remote_post + retrieve helpers) is exercised.
 */
class ConcreteOauthRealExchange extends AbstractOauth
{
    public function getAuthUrl()
    {
        return 'https://provider.example.com/oauth/authorize';
    }

    protected function getTokenEndpoint()
    {
        return 'https://provider.example.com/oauth/token';
    }

    protected function getClientId()
    {
        return 'client-id';
    }

    protected function getClientSecret()
    {
        return 'client-secret';
    }

    protected function getSavedRedirectUri()
    {
        return 'https://example.com/oauth/callback';
    }

    protected function getProviderSlug()
    {
        return 'testprovider';
    }

    protected function isCreateUserEnabled()
    {
        return false;
    }

    protected function getEmailFromTokenResponse($tokenResponse)
    {
        return isset($tokenResponse['email']) ? $tokenResponse['email'] : 'user@example.com';
    }

    /** @var string */
    public $lastValidatedToken = '';

    protected function validateProviderToken($token)
    {
        $this->lastValidatedToken = (string) $token;
    }

    protected function getInvalidTokenErrorCode()
    {
        return 9001;
    }

    protected function getUserNotFoundErrorCode()
    {
        return 9002;
    }

    protected function getTokenParamName()
    {
        return 'access_token';
    }

    protected function getMissingParamErrorCode()
    {
        return 9003;
    }

    protected function getInvalidCodeErrorCode()
    {
        return 9004;
    }

    protected function getEmailFromDirectToken($token)
    {
        return $token !== '' ? 'user@example.com' : '';
    }
}
