<?php
/*
    Plugin Name: Simple-JWT-Login
    Plugin URI: https://simplejwtlogin.com
    Description: Simple-JWT-Login REST API Plugin. Allows you to login / register to WordPress using JWT.
    Author: Nicu Micle
    Author URI: https://profiles.wordpress.org/nicu_m/
    Text Domain: simple-jwt-login
    Domain Path: /i18n
   	Version: 3.5.3
*/

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;
use SimpleJWTLogin\Services\Applications\Google;
use SimpleJWTLogin\Services\RedirectService;
use SimpleJWTLogin\Services\RouteService;

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

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
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

//REST API ROUTES
include_once 'routes/api.php';
include_once '3rd-party/force_login.php';

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
add_action('login_footer', 'simple_jwt_login_login_footer');
function simple_jwt_login_login_footer()
{
    $wordpressData = new WordPressData();
    $jwtSettings   = new SimpleJWTLoginSettings($wordpressData);
    $pluginDirUrl = plugin_dir_url(__FILE__);

    ?>

    <?php
    // GOOGLE
    if ($jwtSettings->getApplicationsSettings()->isGoogleEnabled() && $jwtSettings->getApplicationsSettings()->isOauthEnabled()) {
        ?>
        <form method="POST" action="<?php echo Google::AUTH_URL;?>" class="simple-jwt-login-oauth-app">
            <input type="hidden" name="client_id" value="<?php echo esc_attr($jwtSettings->getApplicationsSettings()->getGoogleClientID());?>" />
            <input type="hidden" name="response_type" value="code" /><br />
            <input type="hidden" name="scope" value="email" /><br />
            <input type="hidden" name="redirect_uri" value="<?php echo $jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, ['provider' => 'google']);?>" />
            <button name="google-auth" class="simple-jwt-login-auth-btn">
                <img src="<?php echo $pluginDirUrl;?>/images/applications/google-60x60.png" alt="google logo"/>
                <span class="simple-jwt-login-auth-txt">
                    Continue with Google
                </span>
            </button>
        </form>
        <?php
    }
}
