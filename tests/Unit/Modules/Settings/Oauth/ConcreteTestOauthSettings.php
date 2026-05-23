<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Oauth;

use SimpleJWTLogin\Modules\Settings\Oauth\AbstractOauthSettings;

class ConcreteTestOauthSettings extends AbstractOauthSettings
{
    public function getGroup()
    {
        return 'test';
    }

    public function getName()
    {
        return 'TestProvider';
    }

    protected function getRequiredFieldValidations()
    {
        return [
            ['client_id', 100, 'Test Client ID'],
        ];
    }

    protected function getAtLeastOneEnabledErrorCode()
    {
        return 99;
    }

    protected function getRedirectUriRequiredErrorCode()
    {
        return 101;
    }
}
