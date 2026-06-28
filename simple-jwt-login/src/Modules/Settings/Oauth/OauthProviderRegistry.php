<?php

namespace SimpleJWTLogin\Modules\Settings\Oauth;

use InvalidArgumentException;
use SimpleJWTLogin\Services\Oauth\Auth0Oauth;
use SimpleJWTLogin\Services\Oauth\FacebookOauth;
use SimpleJWTLogin\Services\Oauth\GithubOauth;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;

/**
 * The single source of truth for the available OAuth / OIDC providers.
 *
 * To add a new provider:
 *  1. Create its settings class (subclass of AbstractOauthSettings).
 *  2. Create its service class (subclass of AbstractOauth).
 *  3. Create its view templates ({slug}.php and {slug}-form.php).
 *  4. Add one OauthProvider row to all() below.
 *
 * Everything else - the settings registry (IntegrationsSettings), the REST handler
 * (OAuthService), the admin catalog (oauth-apps.php), the login page
 * (LoginPageIntegration) and the shortcode (Shortcodes) - is derived automatically.
 */
class OauthProviderRegistry
{
    /**
     * All registered providers, keyed by slug.
     *
     * @return OauthProvider[]
     */
    public static function all()
    {
        $definitions = array(
            new OauthProvider(
                'google',
                'Google',
                'OAuth 2.0',
                GoogleOauthSettings::class,
                GoogleOauth::class
            ),
            new OauthProvider(
                'auth0',
                'Auth0',
                'OAuth 2.0 / OIDC',
                Auth0OauthSettings::class,
                Auth0Oauth::class
            ),
            new OauthProvider(
                'facebook',
                'Facebook',
                'OAuth 2.0',
                FacebookOauthSettings::class,
                FacebookOauth::class
            ),
            new OauthProvider(
                'github',
                'GitHub',
                'OAuth 2.0',
                GithubOauthSettings::class,
                GithubOauth::class
            ),
        );

        $keyed = array();
        foreach ($definitions as $definition) {
            $keyed[$definition->getSlug()] = $definition;
        }

        return $keyed;
    }

    /**
     * @param string $slug
     * @return bool
     */
    public static function has($slug)
    {
        $all = self::all();

        return isset($all[$slug]);
    }

    /**
     * @param string $slug
     * @return OauthProvider
     * @throws InvalidArgumentException
     */
    public static function get($slug)
    {
        $all = self::all();
        if (!isset($all[$slug])) {
            throw new InvalidArgumentException(esc_html("Unknown OAuth provider: {$slug}"));
        }

        return $all[$slug];
    }
}
