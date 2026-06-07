<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Helpers\ViewLoader;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Oauth\Auth0Oauth;
use SimpleJWTLogin\Services\Oauth\FacebookOauth;
use SimpleJWTLogin\Services\Oauth\GithubOauth;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;

class Shortcodes
{
    /**
     * @var array
     */
    protected $request;

    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

    /**
     * @param array $request
     */
    public function __construct($request, SimpleJWTLoginSettings $jwtSettings)
    {
        $this->request = $request;
        $this->jwtSettings = $jwtSettings;
    }

    /**
     * @param array|null $parameter
     * @return string
     */
    public function handleRequestShortcode($parameter = null)
    {
        $parameter = $parameter !== null && isset($parameter['key'])
            ? $parameter['key']
            : null;

        if ($parameter === null) {
            return '';
        }

        if (!isset($this->request[$parameter])) {
            return '';
        }

        return esc_html($this->request[$parameter]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @param array|null $parameter
     * @return string
     */
    public function handleOauthShortcode($parameter = null)
    {
        $pluginDirUrl = plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE);

        if (!isset($parameter['provider'])) {
            return '';
        }

        if ($this->jwtSettings->getWordPressData()->isUserLoggedIn()) {
            return '';
        }

        $background = '#fff';
        $color = '#000';
        $imgwidth = '30px';
        $imgheight = '30px';
        $border = '1px solid #ccc';

        if (isset($parameter['background'])) {
            $sanitized = self::sanitizeColor($parameter['background']);
            if ($sanitized !== '') {
                $background = $sanitized;
            }
        }
        if (isset($parameter['color'])) {
            $sanitized = self::sanitizeColor($parameter['color']);
            if ($sanitized !== '') {
                $color = $sanitized;
            }
        }
        if (isset($parameter['width'])) {
            $sanitized = self::sanitizeDimension($parameter['width']);
            if ($sanitized !== '') {
                $imgwidth = $sanitized;
            }
        }
        if (isset($parameter['height'])) {
            $sanitized = self::sanitizeDimension($parameter['height']);
            if ($sanitized !== '') {
                $imgheight = $sanitized;
            }
        }
        if (isset($parameter['border'])) {
            $border = self::sanitizeBorder($parameter['border']);
        }
        $html = '<style>.simple-jwt-login-oauth-code .simple-jwt-login-auth-btn{
        color: ' . $color . ';
        background-color: ' . $background . ';
        border: ' . $border . ';
        cursor: pointer;
        }
        .simple-jwt-login-oauth-code .simple-jwt-login-auth-btn img {
        width: ' . $imgwidth . ';
        height: ' . $imgheight . ';
        }
        </style>';
        $haveProvider = false;
        $pluginDir = dirname(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        $integrationsSettings = $this->jwtSettings->getIntegrationsSettings();
        $viewLoader = new ViewLoader($pluginDir . '/views/integrations/oauth/');
        $viewData = array(
            'jwtSettings'  => $this->jwtSettings,
            'pluginDirUrl' => plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE),
        );
        switch ($parameter['provider']) {
            case GoogleOauth::PROVIDER_SLUG:
                if ($integrationsSettings->google()->isEnabled()
                    && $integrationsSettings->google()->isOauthEnabled()) {
                    $haveProvider = true;
                    $html .= $viewLoader->fetch('google-form.php', $viewData);
                }
                break;
            case Auth0Oauth::PROVIDER_SLUG:
                if ($integrationsSettings->auth0()->isEnabled()
                    && $integrationsSettings->auth0()->isOauthEnabled()) {
                    $haveProvider = true;
                    $html .= $viewLoader->fetch('auth0-form.php', $viewData);
                }
                break;
            case FacebookOauth::PROVIDER_SLUG:
                if ($integrationsSettings->facebook()->isEnabled()
                    && $integrationsSettings->facebook()->isOauthEnabled()) {
                    $haveProvider = true;
                    $html .= $viewLoader->fetch('facebook-form.php', $viewData);
                }
                break;
            case GithubOauth::PROVIDER_SLUG:
                if ($integrationsSettings->github()->isEnabled()
                    && $integrationsSettings->github()->isOauthEnabled()) {
                    $haveProvider = true;
                    $html .= $viewLoader->fetch('github-form.php', $viewData);
                }
                break;
        }

        if (!$haveProvider) {
            return '';
        }

        return "<span class='simple-jwt-login-oauth-code'>" . $html . '</span>';
    }

    /**
     * @param string $value
     * @return string
     */
    public static function sanitizeCssValue($value)
    {
        $value = wp_strip_all_tags($value);
        $value = preg_replace('/[<>"\';{}()\\\\\/`]/', '', $value);
        $value = substr($value, 0, 100);

        return $value;
    }

    /**
     * Accepts only hex colors (#rgb / #rrggbb) or whitelisted CSS named colors.
     *
     * @param string $value
     * @return string Sanitized color, or empty string when input is not a valid color.
     */
    public static function sanitizeColor($value)
    {
        $trimmed = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $trimmed)) {
            return $trimmed;
        }
        $allowed = array(
            'transparent', 'inherit', 'initial', 'unset', 'currentcolor',
            'black', 'white', 'red', 'green', 'blue', 'yellow', 'orange',
            'purple', 'pink', 'gray', 'grey', 'navy', 'teal', 'silver',
            'gold', 'lime', 'aqua', 'cyan', 'magenta', 'fuchsia', 'maroon',
            'olive', 'coral', 'salmon', 'indigo', 'violet', 'brown',
        );
        $normalized = strtolower($trimmed);
        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }
        return '';
    }

    /**
     * Accepts only numeric values followed by a safe CSS unit.
     *
     * @param string $value
     * @return string Sanitized dimension, or empty string when input is invalid.
     */
    public static function sanitizeDimension($value)
    {
        if (preg_match('/^(\d+(?:\.\d+)?)(px|em|rem|%|vh|vw)$/', trim($value), $matches)) {
            return $matches[1] . $matches[2];
        }
        return '';
    }

    /**
     * Accepts only the CSS border shorthand: <width> <style> <color>.
     * Falls back to the default border when the value does not match.
     *
     * @param string $value
     * @return string
     */
    public static function sanitizeBorder($value)
    {
        $widthPart = '(?:\d+(?:\.\d+)?(?:px|em|rem)|thin|medium|thick)';
        $stylePart = '(?:none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset)';
        $colorPart = '(?:#[0-9a-fA-F]{3,6}|[a-zA-Z]+)';
        $pattern = '/^' . $widthPart . '\s+' . $stylePart . '\s+' . $colorPart . '$/i';
        if (preg_match($pattern, trim($value))) {
            return trim($value);
        }
        return '1px solid #ccc';
    }
}
