<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;

class UserApiKeysPage
{
    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

    public function __construct(SimpleJWTLoginSettings $jwtSettings)
    {
        $this->jwtSettings = $jwtSettings;
    }

    public function registerMenuEntry()
    {
        if (!$this->jwtSettings->getApiKeysSettings()->isUserApiKeysEnabled()) {
            return;
        }
        if (current_user_can('manage_options')) {
            return;
        }

        add_menu_page(
            __('My API Keys', 'simple-jwt-login'),
            __('My API Keys', 'simple-jwt-login'),
            'read',
            'sjl-user-api-keys',
            array($this, 'showPage'),
            plugins_url('/assets/images/simple-jwt-login-16x16.png', SIMPLE_JWT_LOGIN_PLUGIN_FILE)
        );
    }

    public function showPage()
    {
        global $wpdb;

        $jwtSettings    = $this->jwtSettings;
        $pluginData     = get_plugin_data(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        $pluginVersion  = isset($pluginData['Version']) ? $pluginData['Version'] : false;
        $pluginDirUrl   = plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        $apiKeyRepo     = new ApiKeyRepository($wpdb);

        wp_enqueue_style(
            'simple-jwt-login-bootstrap',
            $pluginDirUrl . 'assets/vendor/bootstrap/bootstrap.min.css',
            array(),
            $pluginVersion
        );
        wp_enqueue_style(
            'simple-jwt-login-style',
            $pluginDirUrl . 'assets/css/style.css',
            array(),
            $pluginVersion
        );
        wp_enqueue_script(
            'simple-jwt-bootstrap-min',
            $pluginDirUrl . 'assets/vendor/bootstrap/bootstrap.min.js',
            array('jquery'),
            $pluginVersion,
            false
        );
        wp_enqueue_script(
            'simple-jwt-login-scripts',
            $pluginDirUrl . 'assets/js/scripts.js',
            array('simple-jwt-bootstrap-min'),
            $pluginVersion,
            false
        );

        require_once dirname(SIMPLE_JWT_LOGIN_PLUGIN_FILE) . '/views/user-api-keys.php';
    }
}
