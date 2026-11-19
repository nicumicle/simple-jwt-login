<?php

namespace SimpleJWTLogin\Modules\Settings\Oauth;

use SimpleJWTLogin\Modules\Settings\BaseSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;

class GoogleOauthSettings extends AbstractOauthSettings
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
            ['allow_tokens_from_other_clients', BaseSettings::SETTINGS_TYPE_BOL],
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

    /** @return bool */
    public function allowTokensFromOtherClients()
    {
        return $this->isFieldEnabled('allow_tokens_from_other_clients');
    }

    /**
     * Client ID a sent id_token must be issued for, or GoogleOauth::ANY_CLIENT when the admin opted out.
     *
     * @return string|null
     */
    public function getAcceptedTokenClientId()
    {
        return $this->allowTokensFromOtherClients() ? GoogleOauth::ANY_CLIENT : $this->getClientId();
    }
}
