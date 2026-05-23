<?php

namespace SimpleJWTLogin\Modules\Settings\Oauth;

use SimpleJWTLogin\Modules\Settings\BaseSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;

class FacebookOauthSettings extends AbstractOauthSettings
{
    public function getGroup()
    {
        return 'facebook';
    }

    public function getName()
    {
        return 'Facebook';
    }

    protected function getExtraFields()
    {
        return [
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
            ['client_id',     SettingsErrors::ERR_FACEBOOK_CLIENT_ID_REQUIRED,     'Facebook App ID'],
            ['client_secret', SettingsErrors::ERR_FACEBOOK_CLIENT_SECRET_REQUIRED, 'Facebook App Secret'],
        ];
    }

    protected function getAtLeastOneEnabledErrorCode()
    {
        return SettingsErrors::ERR_FACEBOOK_AT_LEAST_ONE_OPTION_ENABLED;
    }

    protected function getRedirectUriRequiredErrorCode()
    {
        return SettingsErrors::ERR_FACEBOOK_REDIRECT_URI_REQUIRED;
    }

    // -------------------------------------------------------------------------
    // Facebook-specific getter
    // -------------------------------------------------------------------------

    /** @return bool */
    public function isExchangeTokenEnabled()
    {
        return $this->isFieldEnabled('enable_exchange_token');
    }
}
