<?php

namespace SimpleJwtLoginTests\Unit\Services\Oauth;

use Exception;
use SimpleJWTLogin\Services\Oauth\AbstractOauth;

/**
 * Concrete subclass used purely for testing AbstractOauth methods.
 * exchangeCode() is overridden to return whatever $stubbedExchangeResult holds,
 * avoiding any wp_remote_post global-function calls in unit tests.
 * doHtmlRedirect() is overridden to capture the URL instead of calling exit.
 */
class ConcreteOauth extends AbstractOauth
{
    /** @var array{status_code: int, response: array|null} */
    public $exchangeStub = ['status_code' => 200, 'response' => []];

    /** @var string|null URL passed to doHtmlRedirect(), null if not called */
    public $htmlRedirectUrl = null;

    protected function doHtmlRedirect($url)
    {
        $this->htmlRedirectUrl = $url;
    }

    /** @var array{code: string, redirectUri: string} */
    public $lastExchangeCall = ['code' => '', 'redirectUri' => ''];

    public function exchangeCode($code, $redirectUri)
    {
        $this->lastExchangeCall = ['code' => (string) $code, 'redirectUri' => (string) $redirectUri];
        return $this->exchangeStub;
    }

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
        return 'test-client-id';
    }

    protected function getClientSecret()
    {
        return 'test-client-secret';
    }

    protected function getSavedRedirectUri()
    {
        return 'https://example.com/oauth/callback';
    }

    protected function getProviderSlug()
    {
        return 'testprovider';
    }

    /** @var boolean */
    public $createUserEnabled = false;

    protected function isCreateUserEnabled()
    {
        return $this->createUserEnabled;
    }

    protected function getEmailFromTokenResponse($tokenResponse)
    {
        if (empty($tokenResponse['access_token'])) {
            throw new Exception('Provider did not return an access_token.');
        }
        return 'user@example.com';
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
        $this->lastValidatedToken = (string) $token;
        return 'user@example.com';
    }
}
