<?php
/*
    Plugin Name: Simple-JWT-Login
    Plugin URI: https://simplejwtlogin.com
    Description: Simple-JWT-Login REST API Plugin. Allows you to login / register to WordPress using JWT.
    Author: Nicu Micle
    Author URI: https://profiles.wordpress.org/nicu_m/
    Text Domain: simple-jwt-login
    Domain Path: /i18n
    License: GPLv3
    License URI: https://github.com/nicumicle/simple-jwt-login/blob/master/LICENSE
    Version: 3.6.5
*/

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

define('SIMPLE_JWT_LOGIN_DB_VERSION', '1.6');

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
 * Delete options and custom table on plugin uninstall
 * @since 1.3
 */
function simple_jwt_plugin_uninstall()
{
    delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
    delete_option('simple_jwt_login_db_version');
    global $wpdb;
    (new RefreshTokenRepository($wpdb))->dropTable();
    (new AuditLogRepository($wpdb))->dropTable();
    (new WebhookLogRepository($wpdb))->dropTable();
    (new ApiKeyRepository($wpdb))->dropTable();
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
    $wordpressData = new WordPressRepository();
    $jwtSettings   = new SimpleJWTLoginSettings($wordpressData);
    $hasError = false;
    // GOOGLE
    if ($jwtSettings->getIntegrationsSettings()->google()->isEnabled() && $jwtSettings->getIntegrationsSettings()->google()->isOauthEnabled()) {
        if (isset($_REQUEST['error'])) {
            $hasError = true;
        }
    }
    // AUTH0
    if ($jwtSettings->getIntegrationsSettings()->auth0()->isEnabled()
        && $jwtSettings->getIntegrationsSettings()->auth0()->isOauthEnabled()) {
        if (isset($_REQUEST['error'])) {
            $hasError = true;
        }
    }

    if ($hasError) {
        ?>
        <div class="notice notice-error">
            <?php echo esc_html(__("OAuth Error:", 'simple-jwt-login') . ' ' . $_REQUEST['error']);?>
        </div>
        <?php
    }
}

add_action('login_footer', 'simple_jwt_login_login_footer');
/**
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 * @return void
 */
function simple_jwt_login_login_footer()
{
    $wordpressData = new WordPressRepository();
    $jwtSettings = new SimpleJWTLoginSettings($wordpressData);
    $pluginDirUrl = plugin_dir_url(__FILE__);

    $googleEnabled = $jwtSettings->getIntegrationsSettings()->google()->isEnabled()
        && $jwtSettings->getIntegrationsSettings()->google()->isOauthEnabled();
    $auth0Enabled  = $jwtSettings->getIntegrationsSettings()->auth0()->isEnabled()
        && $jwtSettings->getIntegrationsSettings()->auth0()->isOauthEnabled();

    if (!$googleEnabled && !$auth0Enabled) {
        return;
    }

    $layout = $jwtSettings->getIntegrationsSettings()->getLoginButtonLayout();
    echo '<div class="sjl-oauth-buttons-wrapper layout-' . esc_attr($layout) . '">';

    if ($googleEnabled) {
        include_once "views/integrations/oauth/google-form.php";
    }

    if ($auth0Enabled) {
        include_once "views/integrations/oauth/auth0-form.php";
    }

    echo '</div>';
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
    $wordpressData = new WordPressRepository();
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
            if ($jwtSettings->getIntegrationsSettings()->google()->isEnabled()
                && $jwtSettings->getIntegrationsSettings()->google()->isOauthEnabled()) {
                $haveProvider = true;
                ob_start();
                include_once "views/integrations/oauth/google-form.php";
                $html .= ob_get_clean();
            }
            break;
        case 'auth0':
            if ($jwtSettings->getIntegrationsSettings()->auth0()->isEnabled()
                && $jwtSettings->getIntegrationsSettings()->auth0()->isOauthEnabled()) {
                $haveProvider = true;
                ob_start();
                include_once "views/integrations/oauth/auth0-form.php";
                $html .= ob_get_clean();
            }
            break;
    }

    if (!$haveProvider) {
        return "";
    }

    return "<span class='simple-jwt-login-oauth-code'>" . $html . "</span>";
}

// Plugin activation hook
register_activation_hook(__FILE__, 'simple_jwt_login_activate_plugin');

/**
 * Plugin activation: create table, generate key if needed, schedule cleanup cron
 */
function simple_jwt_login_activate_plugin()
{
    simple_jwt_login_create_refresh_tokens_table();
    simple_jwt_login_create_audit_logs_table();
    simple_jwt_login_create_webhook_logs_table();
    simple_jwt_login_create_api_keys_table();
    simple_jwt_login_ensure_refresh_token_key();
    update_option('simple_jwt_login_db_version', SIMPLE_JWT_LOGIN_DB_VERSION);
    if (!wp_next_scheduled('simple_jwt_login_cleanup_refresh_tokens')) {
        wp_schedule_event(time(), 'daily', 'simple_jwt_login_cleanup_refresh_tokens');
    }
    if (!wp_next_scheduled('simple_jwt_login_cleanup_audit_logs')) {
        wp_schedule_event(time(), 'daily', 'simple_jwt_login_cleanup_audit_logs');
    }
    if (!wp_next_scheduled('simple_jwt_login_cleanup_webhook_logs')) {
        wp_schedule_event(time(), 'daily', 'simple_jwt_login_cleanup_webhook_logs');
    }
}

// Plugin deactivation: clear scheduled cron
register_deactivation_hook(__FILE__, 'simple_jwt_login_deactivate_plugin');

function simple_jwt_login_deactivate_plugin()
{
    wp_clear_scheduled_hook('simple_jwt_login_cleanup_refresh_tokens');
    wp_clear_scheduled_hook('simple_jwt_login_cleanup_audit_logs');
    wp_clear_scheduled_hook('simple_jwt_login_cleanup_webhook_logs');
}

// Backward-compatible migration for existing installs upgrading from older versions
add_action('plugins_loaded', 'simple_jwt_login_check_db_version');

function simple_jwt_login_check_db_version()
{
    if (get_option('simple_jwt_login_db_version') !== SIMPLE_JWT_LOGIN_DB_VERSION) {
        simple_jwt_login_create_refresh_tokens_table();
        simple_jwt_login_create_audit_logs_table();
        simple_jwt_login_create_webhook_logs_table();
        simple_jwt_login_create_api_keys_table();
        simple_jwt_login_ensure_refresh_token_key();
        update_option('simple_jwt_login_db_version', SIMPLE_JWT_LOGIN_DB_VERSION);
    }
}

// Daily cron: remove expired refresh tokens
add_action('simple_jwt_login_cleanup_refresh_tokens', 'simple_jwt_login_run_cleanup_refresh_tokens');

function simple_jwt_login_run_cleanup_refresh_tokens()
{
    global $wpdb;
    $refreshTokenRepo = new RefreshTokenRepository($wpdb);
    $refreshTokenRepo->cleanupExpired();
}

// Daily cron: remove old audit log entries
add_action('simple_jwt_login_cleanup_audit_logs', 'simple_jwt_login_run_cleanup_audit_logs');

function simple_jwt_login_run_cleanup_audit_logs()
{
    global $wpdb;
    $jwtSettings   = new SimpleJWTLoginSettings(new WordPressRepository());
    $retentionDays = $jwtSettings->getAuditLogSettings()->getRetentionDays();
    if ($retentionDays <= 0) {
        return;
    }
    $before = gmdate('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
    (new AuditLogRepository($wpdb))->deleteOlderThan($before);
}

// Daily cron: remove old webhook log entries
add_action('simple_jwt_login_cleanup_webhook_logs', 'simple_jwt_login_run_cleanup_webhook_logs');

function simple_jwt_login_run_cleanup_webhook_logs()
{
    global $wpdb;
    $jwtSettings   = new SimpleJWTLoginSettings(new WordPressRepository());
    $retentionDays = $jwtSettings->getWebhooksSettings()->getRetentionDays();
    if ($retentionDays <= 0) {
        return;
    }
    $before = gmdate('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
    (new WebhookLogRepository($wpdb))->deleteOlderThan($before);
}

/**
 * Auto-generate a refresh token key if one is not already configured.
 * This ensures existing installations are not silently falling back
 * to the JWT decryption key for refresh token encryption.
 */
function simple_jwt_login_ensure_refresh_token_key()
{
    $raw = get_option(SimpleJWTLoginSettings::OPTIONS_KEY);
    $settings = is_string($raw) ? json_decode($raw, true) : [];
    if (!is_array($settings)) {
        $settings = [];
    }
    if (empty($settings['refresh_token_key'])) {
        $settings['refresh_token_key'] = bin2hex(random_bytes(32));
        update_option(SimpleJWTLoginSettings::OPTIONS_KEY, json_encode($settings));
    }
}

/**
 * Create (or update) the audit logs table via dbDelta
 */
function simple_jwt_login_create_audit_logs_table()
{
    global $wpdb;
    (new AuditLogRepository($wpdb))->createTable();
}

/**
 * Create (or update) the refresh tokens table via dbDelta
 */
function simple_jwt_login_create_refresh_tokens_table()
{
    global $wpdb;
    (new RefreshTokenRepository($wpdb))->createTable();
}

/**
 * Create (or update) the webhook logs table via dbDelta
 */
function simple_jwt_login_create_webhook_logs_table()
{
    global $wpdb;
    (new WebhookLogRepository($wpdb))->createTable();
}

/**
 * Create (or update) the API keys table via dbDelta
 */
function simple_jwt_login_create_api_keys_table()
{
    global $wpdb;
    (new ApiKeyRepository($wpdb))->createTable();
}

//REST API ROUTES
include_once 'routes/api.php';
include_once '3rd-party/force_login.php';
include_once "3rd-party/wp-graphql.php";