<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Services\Oauth\OauthApplicationInterface;
use SimpleJWTLogin\Services\Oauth\Auth0OauthApplication;
use SimpleJWTLogin\Services\Oauth\GoogleOauthApplication;

class OAuthService extends BaseService implements ServiceInterface
{
    const GOOGLE_PROVIDER = 'google';
    const AUTH0_PROVIDER  = 'auth0';

    /**
     * Maps provider slugs to their factory callbacks.
     * Each callback receives ($request, $method, $settings, $wpData) and returns ApplicationInterface.
     *
     * @var array<string, callable>
     */
    private $providerFactories = [];

    public function __construct()
    {
        $this->providerFactories = [
            self::GOOGLE_PROVIDER => function ($request, $method, $settings, $wpData) {
                return new GoogleOauthApplication($request, $method, $settings, $wpData);
            },
            self::AUTH0_PROVIDER => function ($request, $method, $settings, $wpData) {
                return new Auth0OauthApplication($request, $method, $settings, $wpData);
            },
        ];
    }

    public function makeAction()
    {
        $provider = $this->resolveProvider();

        $this->assertProviderEnabled($provider);

        /** @var OauthApplicationInterface $app */
        $app = ($this->providerFactories[$provider])(
            $this->request,
            $this->requestMethod,
            $this->jwtSettings,
            $this->wordPressData
        );

        $app->validate();

        return $this->wordPressData->createResponse($app->call());
    }

    /**
     * @return string Validated provider slug.
     * @throws Exception
     */
    private function resolveProvider()
    {
        $raw = isset($this->request['provider'])
            ? strtolower($this->request['provider'])
            : '';

        if (empty($raw) || !isset($this->providerFactories[$raw])) {
            throw new Exception(
                __('The Oauth provider is invalid.', 'simple-jwt-login'),
                ErrorCodes::ERR_OAUTH_INVALID_PROVIDER
            );
        }

        return $raw;
    }

    /**
     * @param string $provider
     * @return void
     * @throws Exception
     */
    private function assertProviderEnabled($provider)
    {
        $isEnabled = $this->jwtSettings->getApplicationsSettings()->getProvider($provider)->isEnabled();

        if (!$isEnabled) {
            throw new Exception(
                __('This Oauth provider is not available.', 'simple-jwt-login'),
                ErrorCodes::ERR_OAUTH_PROVIDER_NOT_ACTIVE
            );
        }
    }
}
