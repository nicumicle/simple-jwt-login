<?php

namespace SimpleJWTLogin\Modules\Settings;

use InvalidArgumentException;
use SimpleJWTLogin\Modules\Settings\Oauth\AbstractOauthSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\Auth0OauthSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\FacebookOauthSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\GithubOauthSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\GoogleOauthSettings;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AbstractThirdPartySettings;
use SimpleJWTLogin\Modules\Settings\ThirdParty\WpGraphQLSettings;

/**
 * Registry for OAuth / OIDC providers and 3rd-party integrations.
 *
 * Storage layout (under $settings['integrations']):
 *   ['oauth']['google']     => Google OAuth settings
 *   ['oauth']['auth0']      => Auth0 OAuth settings
 *   ['3rdparty']['wpgraphql'] => WPGraphQL integration settings
 *
 * Adding a new OAuth provider requires only:
 *  1. Create a subclass of AbstractProviderSettings.
 *  2. Add it to buildProviders() below.
 *  3. Optionally expose a typed convenience accessor (like google() / auth0()).
 *
 * Adding a new 3rd-party integration requires only:
 *  1. Create a subclass of AbstractThirdPartySettings.
 *  2. Add it to buildThirdPartyApps() below.
 *  3. Optionally expose a typed convenience accessor (like wpgraphql()).
 */
class IntegrationsSettings extends BaseSettings implements SettingsInterface
{
    const OAUTH_KEY = 'oauth';
    const THIRD_PARTY_KEY = '3rdparty';

    const LAYOUT_STACKED      = 'stacked';
    const LAYOUT_INLINE       = 'inline';
    const LAYOUT_ICON_STACKED = 'icon-stacked';
    const LAYOUT_ICON_INLINE  = 'icon-inline';

    /**
     * @var AbstractOauthSettings[] keyed by provider slug
     */
    private $providers;

    /**
     * @var AbstractThirdPartySettings[] keyed by integration slug
     */
    private $thirdPartyApps;

    public function __construct()
    {
        parent::__construct();
        $this->providers      = $this->buildProviders();
        $this->thirdPartyApps = $this->buildThirdPartyApps();
    }

    protected function getSectionKey()
    {
        return 'integrations';
    }

    // =========================================================================
    // Provider / integration registries - add new entries here
    // =========================================================================

    /**
     * @return AbstractOauthSettings[]
     */
    private function buildProviders()
    {
        return [
            'google'   => new GoogleOauthSettings(),
            'auth0'    => new Auth0OauthSettings(),
            'facebook' => new FacebookOauthSettings(),
            'github'   => new GithubOauthSettings(),
        ];
    }

    /**
     * @return AbstractThirdPartySettings[]
     */
    private function buildThirdPartyApps()
    {
        return [
            'wpgraphql' => new WpGraphQLSettings(),
        ];
    }

    // =========================================================================
    // SettingsInterface implementation
    // =========================================================================

    public function initSettingsFromPost()
    {
        foreach ($this->providers as $slug => $provider) {
            $this->settings[self::OAUTH_KEY][$slug] = $provider->processPost(
                $this->post,
                $this->wordPressData
            );
        }

        foreach ($this->thirdPartyApps as $slug => $app) {
            $this->settings[self::THIRD_PARTY_KEY][$slug] = $app->processPost(
                $this->post,
                $this->wordPressData
            );
        }

        $allowedLayouts = [
            self::LAYOUT_STACKED,
            self::LAYOUT_INLINE,
            self::LAYOUT_ICON_STACKED,
            self::LAYOUT_ICON_INLINE,
        ];
        $layout = isset($this->post['login_button_layout'])
            ? sanitize_text_field($this->post['login_button_layout'])
            : self::LAYOUT_STACKED;
        if (!in_array($layout, $allowedLayouts, true)) {
            $layout = self::LAYOUT_STACKED;
        }
        $this->settings['login_button_layout'] = $layout;
    }

    public function validateSettings()
    {
        foreach ($this->providers as $provider) {
            $provider->validate($this->post);
        }
    }

    /**
     * @return string One of the LAYOUT_* constants.
     */
    public function getLoginButtonLayout()
    {
        $stored = isset($this->settings['login_button_layout'])
            ? $this->settings['login_button_layout']
            : self::LAYOUT_STACKED;
        // Migrate legacy value stored before the stacked/inline split was added.
        if ($stored === 'icon-only') {
            return self::LAYOUT_ICON_STACKED;
        }
        return $stored;
    }

    // =========================================================================
    // OAuth provider access
    // =========================================================================

    /**
     * Return a hydrated settings object for the given OAuth provider slug.
     * The returned object is a clone so callers cannot accidentally share state.
     *
     * @param string $slug
     * @return AbstractOauthSettings
     * @throws \InvalidArgumentException
     */
    public function getProvider($slug)
    {
        if (!isset($this->providers[$slug])) {
            throw new InvalidArgumentException("Unknown OAuth provider: {$slug}");
        }

        $stored = isset($this->settings[self::OAUTH_KEY][$slug])
            ? $this->settings[self::OAUTH_KEY][$slug]
            : [];

        return (clone $this->providers[$slug])->withSettings($stored);
    }

    /**
     * @return GoogleOauthSettings
     */
    public function google()
    {
        /** @var GoogleOauthSettings */
        return $this->getProvider('google');
    }

    /**
     * @return Auth0OauthSettings
     */
    public function auth0()
    {
        /** @var Auth0OauthSettings */
        return $this->getProvider('auth0');
    }

    /**
     * @return FacebookOauthSettings
     */
    public function facebook()
    {
        /** @var FacebookOauthSettings */
        return $this->getProvider('facebook');
    }

    /**
     * @return GithubOauthSettings
     */
    public function github()
    {
        /** @var GithubOauthSettings */
        return $this->getProvider('github');
    }

    // =========================================================================
    // 3rd-party integration access
    // =========================================================================

    /**
     * Return a hydrated settings object for the given 3rd-party integration slug.
     * The returned object is a clone so callers cannot accidentally share state.
     *
     * @param string $slug
     * @return AbstractThirdPartySettings
     * @throws \InvalidArgumentException
     */
    public function getThirdParty($slug)
    {
        if (!isset($this->thirdPartyApps[$slug])) {
            throw new InvalidArgumentException("Unknown 3rd-party integration: {$slug}");
        }

        $stored = isset($this->settings[self::THIRD_PARTY_KEY][$slug])
            ? $this->settings[self::THIRD_PARTY_KEY][$slug]
            : [];

        return (clone $this->thirdPartyApps[$slug])->withSettings($stored);
    }

    /**
     * @return WpGraphQLSettings
     */
    public function wpgraphql()
    {
        /** @var WpGraphQLSettings */
        return $this->getThirdParty('wpgraphql');
    }
}
