<?php
/*
    Plugin Name: Simple-JWT-Login
    Plugin URI: https://simplejwtlogin.com
    Description: Simple-JWT-Login REST API Plugin. Allows you to login / register to WordPress using JWT.
    Author: Nicu Micle
    Author URI: https://profiles.wordpress.org/nicu_m/
    Text Domain: simple-jwt-login
    Domain Path: /i18n
    Version: 3.4.2
*/

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

include_once 'autoload.php';

// it inserts the entry in the admin menu
add_action('admin_menu', 'simple_jwt_login_plugin_create_menu_entry');
add_action('plugins_loaded', 'simple_jwt_login_plugin_load_translations');

// creating the menu entries
function simple_jwt_login_plugin_create_menu_entry()
{
    // icon image path that will appear in the menu
    $icon = plugins_url('/images/simple-jwt-login-16x16.png', __FILE__);

    // adding the main menu entry
    add_menu_page(
        'Simple-JWT-Login Plugin',
        'Simple JWT Login',
        'manage_options',
        'main-page-simple-jwt-login-plugin',
        'simple_jwt_login_plugin_show_main_page',
        $icon
    );
}

/**
 * Load translations
 * @since  1.3
 */
function simple_jwt_login_plugin_load_translations()
{
    load_plugin_textdomain(
        'simple-jwt-login',
        false,
        plugin_basename(dirname(__FILE__)) . '/i18n/'
    );
}

// function triggered in add_menu_page
function simple_jwt_login_plugin_show_main_page()
{
    $pluginData    = get_plugin_data(__FILE__);
    $pluginVersion = isset($pluginData['Version'])
        ? $pluginData['Version']
        : false;
    $pluginDirUrl = plugin_dir_url(__FILE__);
    $loadScriptsInFooter = false;
    wp_enqueue_style(
        'simple-jwt-login-bootstrap',
        $pluginDirUrl . 'vendor/bootstrap/bootstrap.min.css',
        [],
        $pluginVersion
    );
    wp_enqueue_style(
        'simple-jwt-login-style',
        $pluginDirUrl . 'css/style.css',
        [],
        $pluginVersion
    );

    wp_enqueue_script(
        'simple-jwt-bootstrap-min',
        $pluginDirUrl . 'vendor/bootstrap/bootstrap.min.js',
        [ 'jquery' ],
        $pluginVersion,
        $loadScriptsInFooter
    );

    wp_enqueue_script(
        'simple-jwt-login-scripts',
        $pluginDirUrl . 'js/scripts.js',
        [ 'simple-jwt-bootstrap-min' ],
        $pluginVersion,
        $loadScriptsInFooter
    );


    require_once('views/layout.php');
}

// plugin deactivation
register_uninstall_hook(__FILE__, 'simple_jwt_plugin_uninstall');

/**
 * Delete options on plugin uninstall
 * @since 1.3
 */
function simple_jwt_plugin_uninstall()
{
    delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'simple_jwt_login_add_plugin_action_links');

function simple_jwt_login_add_plugin_action_links($links)
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

    return $links;
}

//REST API ROUTES
include_once 'routes/api.php';
include_once '3rd-party/force_login.php';
