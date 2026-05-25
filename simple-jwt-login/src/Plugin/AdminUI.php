<?php

namespace SimpleJWTLogin\Plugin;

class AdminUI
{
    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function registerMenuEntry()
    {
        $icon = plugins_url('/images/simple-jwt-login-16x16.png', SIMPLE_JWT_LOGIN_PLUGIN_FILE);

        add_menu_page(
            'Simple-JWT-Login Plugin',
            'Simple JWT Login',
            'manage_options',
            'main-page-simple-jwt-login-plugin',
            array($this, 'showMainPage'),
            $icon
        );
    }

    public function showMainPage()
    {
        $pluginData    = get_plugin_data(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        $pluginVersion = isset($pluginData['Version'])
            ? $pluginData['Version']
            : false;
        $pluginDirUrl = plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        $loadScriptsInFooter = false;
        wp_enqueue_style(
            'simple-jwt-login-bootstrap',
            $pluginDirUrl . 'vendor/bootstrap/bootstrap.min.css',
            array(),
            $pluginVersion
        );
        wp_enqueue_style(
            'simple-jwt-login-style',
            $pluginDirUrl . 'css/style.css',
            array(),
            $pluginVersion
        );
        wp_enqueue_script(
            'simple-jwt-bootstrap-min',
            $pluginDirUrl . 'vendor/bootstrap/bootstrap.min.js',
            array('jquery'),
            $pluginVersion,
            $loadScriptsInFooter
        );

        wp_enqueue_script(
            'simple-jwt-login-scripts',
            $pluginDirUrl . 'js/scripts.js',
            array('simple-jwt-bootstrap-min'),
            $pluginVersion,
            $loadScriptsInFooter
        );

        require_once dirname(SIMPLE_JWT_LOGIN_PLUGIN_FILE) . '/views/layout.php';
    }

    public function addPluginActionLinks($links)
    {
        $links['donate'] = sprintf(
            '<a href="%1$s" target="_blank" style="color: rgb(166, 146, 25); font-weight: bold;">%2$s</a>',
            'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PK9BCD6AYF58Y&source=url',
            'Buy me a coffee'
        );

        $links['documentation'] = sprintf(
            '<a href="%1$s" target="_blank" style="color: #42b983; font-weight: bold;">%2$s</a>',
            'https://simplejwtlogin.com?utm_source=plugin_page',
            'Documentation'
        );
        $links['github'] = sprintf(
            '<a href="%1$s" target="_blank" style="color: #24292f; font-weight: bold;">%2$s</a>',
            'https://github.com/nicumicle/simple-jwt-login',
            'GitHub'
        );

        return $links;
    }
}
