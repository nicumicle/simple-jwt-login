<?php
/*
    Plugin Name: Simple-JWT-Login
    Plugin URI: https://simplejwtlogin.com
    Description: Simple-JWT-Login REST API Plugin. Allows you to login / register to WordPress using JWT.
    Author: Nicu Micle
    Author URI: https://profiles.wordpress.org/nicu_m/
    Text Domain: simple-jwt-login
    Domain Path: /i18n
    Version: 3.6.4
*/

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
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

add_shortcode('simple-jwt-login:request', 'simple_jwt_login_request_shortcode');

/**
 * @SuppressWarnings(PHPMD.Superglobals)
 * @param array|null $parameter
 * @return string
 */
function simple_jwt_login_request_shortcode($parameter = null)
{
    $parameter = $parameter !== null && isset($parameter['key'])
        ? $parameter['key']
        : null;

    if ($parameter === null) {
        return '';
    }

    if (!isset($_REQUEST[$parameter])) {
        return '';
    }

    return esc_html($_REQUEST[$parameter]);
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
    $links['github'] = sprintf(
        '<a href="%1$s" target="_blank" style="color: #24292f; font-weight: bold;">%2$s</a>',
        'https://github.com/nicumicle/simple-jwt-login',
        'GitHub'
    );

    return $links;
}

add_action('login_head', 'simple_jwt_login_assets');
function simple_jwt_login_assets()
{
    $pluginDirUrl = plugin_dir_url(__FILE__);
    wp_enqueue_style(
        'simple-jwt-login-login_header_css',
        $pluginDirUrl . 'css/login.css'
    );
}

// Register Oauth Providers
add_action('login_message', 'simple_jwt_login_login_message');
/**
 * @SuppressWarnings(PHPMD.Superglobals)
 * @return void
 * @throws Exception
 */
function simple_jwt_login_login_message()
{
    $wordpressData = new WordPressData();
    $jwtSettings   = new SimpleJWTLoginSettings($wordpressData);
    $hasError = false;
    // GOOGLE
    if ($jwtSettings->getApplicationsSettings()->isGoogleEnabled() && $jwtSettings->getApplicationsSettings()->isOauthEnabled()) {
        if (isset($_REQUEST['error'])) {
            $hasError = true;
        }
    }

    if ($hasError) {
        ?>
        <div class="notice notice-error">
            <?php echo esc_html(__("OAuth Error: ", 'simple-jwt-login') . $_REQUEST['error']);?>
        </div>
        <?php
    }
}

add_action('login_footer', 'simple_jwt_login_login_footer');
/**
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 * @SuppressWarnings(PHPMD.Superglobals)
 * @return void
 */
function simple_jwt_login_login_footer()
{
    $wordpressData = new WordPressData();
    $jwtSettings = new SimpleJWTLoginSettings($wordpressData);
    $pluginDirUrl = plugin_dir_url(__FILE__);
    switch (true) {
        // GOOGLE
        case $jwtSettings->getApplicationsSettings()->isGoogleEnabled()
            && $jwtSettings->getApplicationsSettings()->isOauthEnabled():
            include_once "views/applications/google-form.php";
            break;
    }
}

add_shortcode('simple-jwt-login-oauth', 'simple_jwt_login_oauth_shortcode');
/**
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 * @param ?array $parameter
 * @return string
 */
function simple_jwt_login_oauth_shortcode($parameter = null)
{
    $wordpressData = new WordPressData();
    $jwtSettings   = new SimpleJWTLoginSettings($wordpressData);
    $pluginDirUrl = plugin_dir_url(__FILE__);

    if (!isset($parameter['provider'])) {
        return '';
    }

    if ($jwtSettings->getWordPressData()->isUserLoggedIn()) {
        return '';
    }

    $background = "#fff";
    $color = "#000";
    $imgwidth = "30px";
    $imgheight = "30px";
    $border = "1px solid #ccc";

    if (isset($parameter['background'])) {
        $background = $parameter['background'];
    }
    if (isset($parameter['color'])) {
        $color = $parameter['color'];
    }
    if (isset($parameter['width'])) {
        $imgwidth = $parameter['width'];
    }
    if (isset($parameter['height'])) {
        $imgheight = $parameter['height'];
    }
    if (isset($parameter['border'])) {
        $border = $parameter['border'];
    }
    $html = "<style>.simple-jwt-login-oauth-code .simple-jwt-login-auth-btn{
        color: $color;
        background-color: $background;
        border: $border;
        cursor: pointer;
        }
        .simple-jwt-login-oauth-code .simple-jwt-login-auth-btn img {
        width: $imgwidth;
        height: $imgheight;
        }
        </style>";
    $haveProvider = false;
    switch ($parameter['provider']) {
        case 'google':
            if ($jwtSettings->getApplicationsSettings()->isGoogleEnabled()
                && $jwtSettings->getApplicationsSettings()->isOauthEnabled()) {
                $haveProvider = true;
                ob_start();
                include_once "views/applications/google-form.php";
                $html .= ob_get_clean();
            }
            break;
    }

    if (!$haveProvider) {
        return "";
    }

    return "<span class='simple-jwt-login-oauth-code'>" . $html . "</span>";
}

//REST API ROUTES
include_once 'routes/api.php';
include_once '3rd-party/force_login.php';
include_once "3rd-party/wp-graphql.php";