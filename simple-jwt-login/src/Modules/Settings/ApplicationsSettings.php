<?php

namespace SimpleJWTLogin\Modules\Settings;

use InvalidArgumentException;
use SimpleJWTLogin\Modules\Settings\Providers\AbstractProviderSettings;
use SimpleJWTLogin\Modules\Settings\Providers\Auth0ProviderSettings;
use SimpleJWTLogin\Modules\Settings\Providers\GoogleProviderSettings;

/**
 * Registry for all OAuth / OIDC provider settings.
 *
 * Adding a new provider requires only:
 *  1. Create a subclass of AbstractProviderSettings.
 *  2. Add it to buildProviders() below.
 *  3. Optionally expose a typed convenience accessor (like google() / auth0()).
 *
 * Everything else - field registration, sanitisation, validation - is handled
 * by the abstract base class automatically.
 */
class ApplicationsSettings extends BaseSettings implements SettingsInterface
{
    /**
     * @var AbstractProviderSettings[] keyed by provider group slug
     */
    private $providers;

    public function __construct()
    {
        parent::__construct();
        $this->providers = $this->buildProviders();
    }

    // =========================================================================
    // Provider registry - add new providers here
    // =========================================================================

    /**
     * @return AbstractProviderSettings[]
     */
    private function buildProviders()
    {
        return [
            'google' => new GoogleProviderSettings(),
            'auth0'  => new Auth0ProviderSettings(),
        ];
    }

    // =========================================================================
    // SettingsInterface implementation
    // =========================================================================

    public function initSettingsFromPost()
    {
        foreach ($this->providers as $slug => $provider) {
            $this->settings[$slug] = $provider->processPost($this->post, $this->wordPressData);
        }
    }

    public function validateSettings()
    {
        foreach ($this->providers as $provider) {
            $provider->validate($this->post);
        }
    }

    // =========================================================================
    // Provider access
    // =========================================================================

    /**
     * Return a hydrated settings object for the given provider slug.
     * The returned object is a clone so callers cannot accidentally share state.
     *
     * @param string $slug
     * @return AbstractProviderSettings
     * @throws \InvalidArgumentException
     */
    public function getProvider($slug)
    {
        if (!isset($this->providers[$slug])) {
            throw new InvalidArgumentException("Unknown OAuth provider: {$slug}");
        }

        return (clone $this->providers[$slug])->withSettings(
            isset($this->settings[$slug]) ? $this->settings[$slug] : []
        );
    }

    /**
     * @return GoogleProviderSettings
     */
    public function google()
    {
        /** @var GoogleProviderSettings */
        return $this->getProvider('google');
    }

    /**
     * @return Auth0ProviderSettings
     */
    public function auth0()
    {
        /** @var Auth0ProviderSettings */
        return $this->getProvider('auth0');
    }
}
