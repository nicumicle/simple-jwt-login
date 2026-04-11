<?php

namespace SimpleJWTLogin\Modules\Settings\Providers;

use SimpleJWTLogin\Modules\Settings\BaseSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;

class GoogleProviderSettings extends AbstractProviderSettings
{
    public function getGroup()
    {
        return 'google';
    }

    public function getName()
    {
        return 'Google';
    }

    protected function getExtraFields()
    {
        return [
            ['enable_exchange_id_token', BaseSettings::SETTINGS_TYPE_BOL],
        ];
    }

    protected function getToggleableFeatures()
    {
        return array_merge(parent::getToggleableFeatures(), ['enable_exchange_id_token']);
    }

    protected function getRequiredFieldValidations()
    {
        return [
            ['client_id',     SettingsErrors::ERR_GOOGLE_CLIENT_ID_REQUIRED,     'Google Client ID'],
            ['client_secret', SettingsErrors::ERR_GOOGLE_CLIENT_SECRET_REQUIRED, 'Google Client Secret'],
        ];
    }

    protected function getAtLeastOneEnabledErrorCode()
    {
        return SettingsErrors::ERR_GOOGLE_AT_LEAST_ONE_OPTION_ENABLED;
    }

    protected function getRedirectUriRequiredErrorCode()
    {
        return SettingsErrors::ERR_GOOGLE_REDIRECT_URI_REQUIRED_FOR_EXCHANGE_CODE;
    }

    // -------------------------------------------------------------------------
    // Google-specific getter
    // -------------------------------------------------------------------------

    /** @return bool */
    public function isExchangeIdTokenEnabled()
    {
        return $this->isFieldEnabled('enable_exchange_id_token');
    }
}
