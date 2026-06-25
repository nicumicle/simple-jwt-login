<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Services\Oauth\OauthInterface;
use SimpleJWTLogin\Services\Oauth\Auth0Oauth;
use SimpleJWTLogin\Services\Oauth\FacebookOauth;
use SimpleJWTLogin\Services\Oauth\GithubOauth;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;

class OAuthService extends BaseService implements ServiceInterface
{
    const GOOGLE_PROVIDER    = 'google';
    const AUTH0_PROVIDER     = 'auth0';
    const FACEBOOK_PROVIDER  = 'facebook';
    const GITHUB_PROVIDER    = 'github';

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
                return new GoogleOauth($request, $method, $settings, $wpData);
            },
            self::AUTH0_PROVIDER => function ($request, $method, $settings, $wpData) {
                return new Auth0Oauth($request, $method, $settings, $wpData);
            },
            self::FACEBOOK_PROVIDER => function ($request, $method, $settings, $wpData) {
                return new FacebookOauth($request, $method, $settings, $wpData);
            },
            self::GITHUB_PROVIDER => function ($request, $method, $settings, $wpData) {
                return new GithubOauth($request, $method, $settings, $wpData);
            },
        ];
    }

    public function makeAction()
    {
        $provider = $this->resolveProvider();

        $this->assertProviderEnabled($provider);

        /** @var callable $factory */
        $factory = $this->providerFactories[$provider];
        $app = $factory(
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
                esc_html(__('The Oauth provider is invalid.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_OAUTH_INVALID_PROVIDER)
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
        $isEnabled = $this->jwtSettings->getIntegrationsSettings()->getProvider($provider)->isEnabled();

        if (!$isEnabled) {
            throw new Exception(
                esc_html(__('This Oauth provider is not available.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_OAUTH_PROVIDER_NOT_ACTIVE)
            );
        }
    }
}
