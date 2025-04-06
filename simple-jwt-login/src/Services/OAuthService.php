<?php
namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Services\Applications\Google;

class OAuthService extends BaseService implements ServiceInterface
{
    const GOOGLE_PROVIDER = 'google';
    /**
     * @var string[]
     */
    private $providers = [
        self::GOOGLE_PROVIDER,
    ];

    public function makeAction()
    {
        if (!isset($this->request['provider'])
            || !in_array(strtolower($this->request['provider']), $this->providers)) {
            throw new Exception(
                __('The Oauth provider is invalid.', 'simple-jwt-login'),
                ErrorCodes::ERR_OAUTH_INVALID_PROVIDER
            );
        }

        switch (strtolower($this->request['provider'])) {
            case self::GOOGLE_PROVIDER:
                if (!$this->jwtSettings->getApplicationsSettings()->isGoogleEnabled()) {
                    throw new Exception(
                        __('This Oauth provider is not available.', 'simple-jwt-login'),
                        ErrorCodes::ERR_OAUTH_PROVIDER_NOT_ACTIVE
                    );
                }
                $provider = new Google($this->request, $this->requestMetod, $this->jwtSettings, $this->wordPressData);
                $provider->validate();

                return $this->wordPressData->createResponse($provider->call());
            default:
                throw new Exception(
                    __('The Oauth provider is invalid.', 'simple-jwt-login'),
                    ErrorCodes::ERR_OAUTH_INVALID_PROVIDER
                );
        }
    }
}
