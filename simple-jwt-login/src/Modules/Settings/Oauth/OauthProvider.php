<?php

namespace SimpleJWTLogin\Modules\Settings\Oauth;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

/**
 * Immutable descriptor for a single OAuth / OIDC provider.
 *
 * Every place that needs to know about a provider (settings registry, REST service,
 * admin UI, login page, shortcode) derives what it needs from these descriptors,
 * so a provider is declared exactly once - in OauthProviderRegistry.
 */
class OauthProvider
{
    /** @var string */
    private $slug;

    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var string Fully-qualified AbstractOauthSettings subclass name. */
    private $settingsClass;

    /** @var string Fully-qualified AbstractOauth service subclass name. */
    private $serviceClass;

    /**
     * @param string $slug          Provider slug, also the HTML field prefix (e.g. "google").
     * @param string $name          Human-readable name (e.g. "Google").
     * @param string $description   Short label shown in the admin catalog (e.g. "OAuth 2.0").
     * @param string $settingsClass AbstractOauthSettings subclass, e.g. GoogleOauthSettings::class.
     * @param string $serviceClass  AbstractOauth subclass, e.g. GoogleOauth::class.
     */
    public function __construct($slug, $name, $description, $settingsClass, $serviceClass)
    {
        $this->slug          = $slug;
        $this->name          = $name;
        $this->description   = $description;
        $this->settingsClass = $settingsClass;
        $this->serviceClass  = $serviceClass;
    }

    /** @return string */
    public function getSlug()
    {
        return $this->slug;
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @return string */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Build a fresh settings object for this provider.
     *
     * @return \SimpleJWTLogin\Modules\Settings\Oauth\AbstractOauthSettings
     */
    public function createSettings()
    {
        $class = $this->settingsClass;

        return new $class();
    }

    /**
     * Build the REST service handler for this provider.
     *
     * @param array $request
     * @param string $requestMethod
     * @param SimpleJWTLoginSettings $settings
     * @param WordPressDataInterface $wordPressData
     * @return \SimpleJWTLogin\Services\Oauth\OauthInterface
     */
    public function createService($request, $requestMethod, SimpleJWTLoginSettings $settings, WordPressDataInterface $wordPressData)
    {
        $class = $this->serviceClass;

        return new $class($request, $requestMethod, $settings, $wordPressData);
    }
}
