<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\Oauth\OauthProviderRegistry;

class OAuthService extends BaseService implements ServiceInterface
{
    public function makeAction()
    {
        $provider = $this->resolveProvider();

        $this->assertProviderEnabled($provider);

        $app = OauthProviderRegistry::get($provider)->createService(
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

        if (empty($raw) || !OauthProviderRegistry::has($raw)) {
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
