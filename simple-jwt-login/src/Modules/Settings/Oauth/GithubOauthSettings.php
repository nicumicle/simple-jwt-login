<?php

namespace SimpleJWTLogin\Modules\Settings\Oauth;

use SimpleJWTLogin\Modules\Settings\BaseSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;

class GithubOauthSettings extends AbstractOauthSettings
{
    public function getGroup()
    {
        return 'github';
    }

    public function getName()
    {
        return 'GitHub';
    }

    protected function getExtraFields()
    {
        return [
            ['enable_exchange_token',  BaseSettings::SETTINGS_TYPE_BOL],
            ['allow_unverified_email', BaseSettings::SETTINGS_TYPE_BOL],
        ];
    }

    protected function getToggleableFeatures()
    {
        return array_merge(parent::getToggleableFeatures(), ['enable_exchange_token']);
    }

    protected function getRequiredFieldValidations()
    {
        return [
            ['client_id',     SettingsErrors::ERR_GITHUB_CLIENT_ID_REQUIRED,     'GitHub Client ID'],
            ['client_secret', SettingsErrors::ERR_GITHUB_CLIENT_SECRET_REQUIRED, 'GitHub Client Secret'],
        ];
    }

    protected function getAtLeastOneEnabledErrorCode()
    {
        return SettingsErrors::ERR_GITHUB_AT_LEAST_ONE_OPTION_ENABLED;
    }

    protected function getRedirectUriRequiredErrorCode()
    {
        return SettingsErrors::ERR_GITHUB_REDIRECT_URI_REQUIRED;
    }

    // -------------------------------------------------------------------------
    // GitHub-specific getter
    // -------------------------------------------------------------------------

    /** @return bool */
    public function isExchangeTokenEnabled()
    {
        return $this->isFieldEnabled('enable_exchange_token');
    }

    /** @return bool */
    public function allowUnverifiedEmail()
    {
        return $this->isFieldEnabled('allow_unverified_email');
    }
}
