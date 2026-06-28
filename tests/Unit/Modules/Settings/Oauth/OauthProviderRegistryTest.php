<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Oauth;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\Oauth\AbstractOauthSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\OauthProvider;
use SimpleJWTLogin\Modules\Settings\Oauth\OauthProviderRegistry;

class OauthProviderRegistryTest extends TestCase
{
    public function testAllIsKeyedBySlug(): void
    {
        $all = OauthProviderRegistry::all();

        $this->assertSame(
            ['google', 'auth0', 'facebook', 'github'],
            array_keys($all)
        );
        foreach ($all as $slug => $provider) {
            $this->assertInstanceOf(OauthProvider::class, $provider);
            $this->assertSame($slug, $provider->getSlug());
        }
    }

    public function testHasReturnsTrueForKnownProvider(): void
    {
        $this->assertTrue(OauthProviderRegistry::has('google'));
    }

    public function testHasReturnsFalseForUnknownProvider(): void
    {
        $this->assertFalse(OauthProviderRegistry::has('does-not-exist'));
    }

    public function testGetReturnsProviderDefinition(): void
    {
        $provider = OauthProviderRegistry::get('github');

        $this->assertSame('github', $provider->getSlug());
        $this->assertSame('GitHub', $provider->getName());
    }

    public function testGetThrowsOnUnknownProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OauthProviderRegistry::get('does-not-exist');
    }

    public function testCreateSettingsReturnsAFreshOauthSettingsInstance(): void
    {
        $provider = OauthProviderRegistry::get('google');

        $first  = $provider->createSettings();
        $second = $provider->createSettings();

        $this->assertInstanceOf(AbstractOauthSettings::class, $first);
        $this->assertNotSame($first, $second);
    }
}
