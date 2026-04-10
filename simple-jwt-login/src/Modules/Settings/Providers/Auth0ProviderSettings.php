<?php

namespace SimpleJWTLogin\Modules\Settings\Providers;

use SimpleJWTLogin\Modules\Settings\BaseSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;

class Auth0ProviderSettings extends AbstractProviderSettings
{
    public function getGroup()
    {
        return 'auth0';
    }

    public function getName()
    {
        return 'Auth0';
    }

    protected function getExtraFields()
    {
        return [
            ['domain',                BaseSettings::SETTINGS_TYPE_STRING],
            ['enable_exchange_token', BaseSettings::SETTINGS_TYPE_BOL],
        ];
    }

    protected function getToggleableFeatures()
    {
        return array_merge(parent::getToggleableFeatures(), ['enable_exchange_token']);
    }

    protected function getRequiredFieldValidations()
    {
        return [
            ['domain',        SettingsErrors::ERR_AUTH0_DOMAIN_REQUIRED,        'Auth0 Domain'],
            ['client_id',     SettingsErrors::ERR_AUTH0_CLIENT_ID_REQUIRED,     'Auth0 Client ID'],
            ['client_secret', SettingsErrors::ERR_AUTH0_CLIENT_SECRET_REQUIRED, 'Auth0 Client Secret'],
        ];
    }

    protected function getAtLeastOneEnabledErrorCode()
    {
        return SettingsErrors::ERR_AUTH0_AT_LEAST_ONE_OPTION_ENABLED;
    }

    protected function getRedirectUriRequiredErrorCode()
    {
        return SettingsErrors::ERR_AUTH0_REDIRECT_URI_REQUIRED;
    }

    // -------------------------------------------------------------------------
    // Auth0-specific getters
    // -------------------------------------------------------------------------

    /** @return string */
    public function getDomain()
    {
        return $this->get('domain');
    }

    /** @return bool */
    public function isExchangeTokenEnabled()
    {
        return $this->is('enable_exchange_token');
    }
}
