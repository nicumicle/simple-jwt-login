<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Oauth;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\Oauth\AbstractOauthSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\OauthProvider;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressData;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;
use SimpleJWTLogin\Services\Oauth\OauthInterface;

class OauthProviderTest extends TestCase
{
    public function testGettersReturnConstructorValues()
    {
        $provider = new OauthProvider(
            'google',
            'Google',
            'OAuth 2.0',
            \SimpleJWTLogin\Modules\Settings\Oauth\GoogleOauthSettings::class,
            GoogleOauth::class
        );

        $this->assertSame('google', $provider->getSlug());
        $this->assertSame('Google', $provider->getName());
        $this->assertSame('OAuth 2.0', $provider->getDescription());
    }

    public function testCreateSettingsBuildsFreshInstance()
    {
        $provider = new OauthProvider(
            'google',
            'Google',
            'OAuth 2.0',
            \SimpleJWTLogin\Modules\Settings\Oauth\GoogleOauthSettings::class,
            GoogleOauth::class
        );

        $first = $provider->createSettings();
        $second = $provider->createSettings();

        $this->assertInstanceOf(AbstractOauthSettings::class, $first);
        $this->assertNotSame($first, $second);
    }

    public function testCreateServiceBuildsConfiguredServiceInstance()
    {
        $provider = new OauthProvider(
            'google',
            'Google',
            'OAuth 2.0',
            \SimpleJWTLogin\Modules\Settings\Oauth\GoogleOauthSettings::class,
            GoogleOauth::class
        );

        $wordPressData = $this->createStub(WordPressData::class);
        $settings = new SimpleJWTLoginSettings($wordPressData);

        $service = $provider->createService(
            ['code' => 'abc'],
            'GET',
            $settings,
            $wordPressData
        );

        $this->assertInstanceOf(OauthInterface::class, $service);
        $this->assertInstanceOf(GoogleOauth::class, $service);
    }
}
