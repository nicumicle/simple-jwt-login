<?php
/*
    Plugin Name: Simple-JWT-Login
    Plugin URI: https://simplejwtlogin.com
    Description: Simple-JWT-Login REST API Plugin. Allows you to login / register to WordPress using JWT.
    Author: Nicu Micle
    Author URI: https://profiles.wordpress.org/nicu_m/
    Text Domain: simple-jwt-login
    Domain Path: /i18n
    Version: 3.6.7
*/

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

include_once 'autoload.php';

const SIMPLE_JWT_LOGIN_V3_EOL_NOTICE_ACTION = 'simple_jwt_login_dismiss_v3_eol_notice';
const SIMPLE_JWT_LOGIN_V3_EOL_NOTICE_OPTION = 'simple_jwt_login_v3_eol_notice_dismissed';

// it inserts the entry in the admin menu
add_action('admin_menu', 'simple_jwt_login_plugin_create_menu_entry');
add_action('plugins_loaded', 'simple_jwt_login_plugin_load_translations');
add_action('admin_notices', 'simple_jwt_login_v3_eol_notice');
add_action('admin_enqueue_scripts', 'simple_jwt_login_enqueue_eol_notice_assets');
add_action('wp_ajax_' . SIMPLE_JWT_LOGIN_V3_EOL_NOTICE_ACTION, 'simple_jwt_login_dismiss_v3_eol_notice');

/**
 * Displays a global admin notice announcing v3 end-of-life and linking to the v4 migration guide.
 */
function simple_jwt_login_v3_eol_notice()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (get_option(SIMPLE_JWT_LOGIN_V3_EOL_NOTICE_OPTION)) {
        return;
    }
    ?>
    <div class="notice notice-info is-dismissible simple-jwt-login-eol-notice"
         data-nonce="<?php echo esc_attr(wp_create_nonce(SIMPLE_JWT_LOGIN_V3_EOL_NOTICE_ACTION)); ?>">
        <p>
            <strong><?php echo esc_html__('Simple-JWT-Login v4 is here', 'simple-jwt-login'); ?></strong>
            <?php echo esc_html__('with new features, performance improvements, and stronger security. We recommend upgrading when you\'re ready.', 'simple-jwt-login'); ?>
            <a href="https://simplejwtlogin.com/v4?utm_source=plugin&amp;utm_medium=admin_notice&amp;utm_campaign=v4_upgrade" target="_blank" rel="noopener noreferrer">
                <?php echo esc_html__('See what\'s new in v4', 'simple-jwt-login'); ?>
            </a>
        </p>
    </div>
    <?php
}

/**
 * Loads the EOL notice dismiss script/style on admin pages where the notice is visible.
 */
function simple_jwt_login_enqueue_eol_notice_assets()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (get_option(SIMPLE_JWT_LOGIN_V3_EOL_NOTICE_OPTION)) {
        return;
    }

    $pluginDirUrl = plugin_dir_url(__FILE__);

    wp_enqueue_style(
        'simple-jwt-login-eol-notice',
        $pluginDirUrl . 'css/eol-notice.css'
    );

    wp_enqueue_script(
        'simple-jwt-login-eol-notice',
        $pluginDirUrl . 'js/eol-notice.js',
        [ 'jquery' ],
        false,
        true
    );

    wp_localize_script(
        'simple-jwt-login-eol-notice',
        'simpleJwtLoginEol',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'action'  => SIMPLE_JWT_LOGIN_V3_EOL_NOTICE_ACTION,
        ]
    );
}

/**
 * Persists the EOL notice dismissal via AJAX so it does not show again for any admin.
 */
function simple_jwt_login_dismiss_v3_eol_notice()
{
    if (!current_user_can('manage_options')) {
        wp_die('', '', [ 'response' => 403 ]);
    }

    check_ajax_referer(SIMPLE_JWT_LOGIN_V3_EOL_NOTICE_ACTION, 'nonce');

    update_option(SIMPLE_JWT_LOGIN_V3_EOL_NOTICE_OPTION, 1);

    wp_die();
}

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
    $links['get_v4'] = sprintf(
        '<a href="%1$s" target="_blank" style="color: #d63638; font-weight: bold;">%2$s</a>',
        'https://simplejwtlogin.com/v4?utm_source=plugin&utm_medium=action_links&utm_campaign=v4_upgrade',
        'Get v4'
    );

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
 * Sanitize CSS property values to prevent XSS attacks.
 * Removes characters that could break out of CSS context or inject malicious code.
 *
 * @param string $value The CSS value to sanitize
 * @return string The sanitized CSS value
 */
function simple_jwt_login_sanitize_css_value($value)
{
    // Remove any HTML tags
    $value = wp_strip_all_tags($value);

    // Remove characters that could break out of CSS/HTML context or inject code
    // This includes: < > " ' ; { } ( ) \ / and backticks
    $value = preg_replace('/[<>"\';{}()\\\\\/`]/', '', $value);

    // Limit length to prevent abuse
    $value = substr($value, 0, 100);

    return $value;
}

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
        $background = simple_jwt_login_sanitize_css_value($parameter['background']);
    }
    if (isset($parameter['color'])) {
        $color = simple_jwt_login_sanitize_css_value($parameter['color']);
    }
    if (isset($parameter['width'])) {
        $imgwidth = simple_jwt_login_sanitize_css_value($parameter['width']);
    }
    if (isset($parameter['height'])) {
        $imgheight = simple_jwt_login_sanitize_css_value($parameter['height']);
    }
    if (isset($parameter['border'])) {
        $border = simple_jwt_login_sanitize_css_value($parameter['border']);
    }
    $html = "<style>.simple-jwt-login-oauth-code .simple-jwt-login-auth-btn{
        color: " . esc_attr($color) . ";
        background-color: " . esc_attr($background) . ";
        border: " . esc_attr($border) . ";
        cursor: pointer;
        }
        .simple-jwt-login-oauth-code .simple-jwt-login-auth-btn img {
        width: " . esc_attr($imgwidth) . ";
        height: " . esc_attr($imgheight) . ";
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