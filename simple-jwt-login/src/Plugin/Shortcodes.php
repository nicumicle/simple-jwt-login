<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

class Shortcodes
{
    /**
     * @var array
     */
    protected $request;

    /**
     * @param array $request
     */
    public function __construct($request)
    {
        $this->request = $request;
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
        $wordpressData = new WordPressRepository();
        $jwtSettings   = new SimpleJWTLoginSettings($wordpressData);
        $pluginDirUrl = plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE);

        if (!isset($parameter['provider'])) {
            return '';
        }

        if ($jwtSettings->getWordPressData()->isUserLoggedIn()) {
            return '';
        }

        $background = '#fff';
        $color = '#000';
        $imgwidth = '30px';
        $imgheight = '30px';
        $border = '1px solid #ccc';

        if (isset($parameter['background'])) {
            $background = self::sanitizeCssValue($parameter['background']);
        }
        if (isset($parameter['color'])) {
            $color = self::sanitizeCssValue($parameter['color']);
        }
        if (isset($parameter['width'])) {
            $imgwidth = self::sanitizeCssValue($parameter['width']);
        }
        if (isset($parameter['height'])) {
            $imgheight = self::sanitizeCssValue($parameter['height']);
        }
        if (isset($parameter['border'])) {
            $border = self::sanitizeCssValue($parameter['border']);
        }
        $html = '<style>.simple-jwt-login-oauth-code .simple-jwt-login-auth-btn{
        color: ' . esc_attr($color) . ';
        background-color: ' . esc_attr($background) . ';
        border: ' . esc_attr($border) . ';
        cursor: pointer;
        }
        .simple-jwt-login-oauth-code .simple-jwt-login-auth-btn img {
        width: ' . esc_attr($imgwidth) . ';
        height: ' . esc_attr($imgheight) . ';
        }
        </style>';
        $haveProvider = false;
        $pluginDir = dirname(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        switch ($parameter['provider']) {
            case 'google':
                if ($jwtSettings->getIntegrationsSettings()->google()->isEnabled()
                    && $jwtSettings->getIntegrationsSettings()->google()->isOauthEnabled()) {
                    $haveProvider = true;
                    ob_start();
                    include_once $pluginDir . '/views/integrations/oauth/google-form.php';
                    $html .= ob_get_clean();
                }
                break;
            case 'auth0':
                if ($jwtSettings->getIntegrationsSettings()->auth0()->isEnabled()
                    && $jwtSettings->getIntegrationsSettings()->auth0()->isOauthEnabled()) {
                    $haveProvider = true;
                    ob_start();
                    include_once $pluginDir . '/views/integrations/oauth/auth0-form.php';
                    $html .= ob_get_clean();
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
}
