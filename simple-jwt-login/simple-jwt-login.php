<?php
/*
    Plugin Name: Simple JWT Login
    Plugin URI: https://simplejwtlogin.com
    Description: Simple-JWT-Login REST API Plugin. Allows you to login / register to WordPress using JWT.
    Author: Nicu Micle
    Author URI: https://profiles.wordpress.org/nicu_m/
    Text Domain: simple-jwt-login
    Domain Path: /i18n
    License: GPLv3
    License URI: https://github.com/nicumicle/simple-jwt-login/blob/master/LICENSE
    Version: 4.0.0
*/

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Plugin\AdminUI;
use SimpleJWTLogin\Plugin\CronCleanup;
use SimpleJWTLogin\Plugin\Lifecycle;
use SimpleJWTLogin\Plugin\LoginPageIntegration;
use SimpleJWTLogin\Plugin\OAuthTwoFactorLoginHandler;
use SimpleJWTLogin\Plugin\Shortcodes;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Routes\RouteRegistrar;
use SimpleJWTLogin\Services\Oauth\AbstractOauth;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

define('SIMPLE_JWT_LOGIN_VERSION', '4.0.0');
define('SIMPLE_JWT_LOGIN_DB_VERSION', '1.7');
define('SIMPLE_JWT_LOGIN_PLUGIN_FILE', __FILE__);

include_once 'autoload.php';
require_once(ABSPATH . 'wp-admin/includes/user.php');

global $wpdb;

// Admin UI
$simpleJwtLoginAdminUI = new AdminUI();
add_action('admin_menu', array($simpleJwtLoginAdminUI, 'registerMenuEntry'));
add_filter(
    'plugin_action_links_' . plugin_basename(__FILE__),
    array($simpleJwtLoginAdminUI, 'addPluginActionLinks')
);

// Shared settings (used by LoginPageIntegration and CronCleanup)
$simpleJwtLoginWordPressRepository = WordPressRepository::getInstance();
$simpleJwtLoginJwtSettings = new SimpleJWTLoginSettings($simpleJwtLoginWordPressRepository);

// Login page integration
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$simpleJwtLoginLoginPage = new LoginPageIntegration($_REQUEST, $simpleJwtLoginJwtSettings);
add_action('login_head', array($simpleJwtLoginLoginPage, 'enqueueLoginAssets'));
add_action('login_message', array($simpleJwtLoginLoginPage, 'showLoginMessage'));
add_action('login_footer', array($simpleJwtLoginLoginPage, 'renderLoginFooter'));

// Browser-based OAuth + 2FA page (wp-login.php?action=sjl-oauth-2fa)
//phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.NonceVerification.Missing
$simpleJwtLoginOAuthTwoFactor = new OAuthTwoFactorLoginHandler($_SERVER, $_GET, $_POST, $simpleJwtLoginJwtSettings);
add_action(
    'login_form_' . AbstractOauth::BROWSER_2FA_ACTION,
    array($simpleJwtLoginOAuthTwoFactor, 'handleAction')
);

// Shortcodes
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$simpleJwtLoginShortcodes = new Shortcodes($_REQUEST, $simpleJwtLoginJwtSettings);
add_shortcode('simple-jwt-login:request', array($simpleJwtLoginShortcodes, 'handleRequestShortcode'));
add_shortcode('simple-jwt-login-oauth', array($simpleJwtLoginShortcodes, 'handleOauthShortcode'));

// Shared repositories
$simpleJwtLoginRefreshTokenRepo = new RefreshTokenRepository($wpdb);
$simpleJwtLoginAuditLogRepo = new AuditLogRepository($wpdb);
$simpleJwtLoginWebhookLogRepo = new WebhookLogRepository($wpdb);
$simpleJwtLoginApiKeyRepo = new ApiKeyRepository($wpdb);

// Lifecycle (activation, deactivation, uninstall, migration, i18n)
$simpleJwtLoginLifecycle = new Lifecycle(
    $simpleJwtLoginRefreshTokenRepo,
    $simpleJwtLoginAuditLogRepo,
    $simpleJwtLoginWebhookLogRepo,
    $simpleJwtLoginApiKeyRepo
);
register_activation_hook(__FILE__, array($simpleJwtLoginLifecycle, 'activate'));
register_deactivation_hook(__FILE__, array($simpleJwtLoginLifecycle, 'deactivate'));
register_uninstall_hook(__FILE__, 'SimpleJWTLogin\\Plugin\\Lifecycle::uninstall');
add_action('init', array($simpleJwtLoginLifecycle, 'loadTranslations'));
add_action('plugins_loaded', array($simpleJwtLoginLifecycle, 'checkDbVersion'));

// Cron cleanup
$simpleJwtLoginCron = new CronCleanup(
    $simpleJwtLoginJwtSettings,
    $simpleJwtLoginRefreshTokenRepo,
    $simpleJwtLoginAuditLogRepo,
    $simpleJwtLoginWebhookLogRepo
);
add_action('simple_jwt_login_cleanup_refresh_tokens', array($simpleJwtLoginCron, 'cleanupRefreshTokens'));
add_action('simple_jwt_login_cleanup_audit_logs', array($simpleJwtLoginCron, 'cleanupAuditLogs'));
add_action('simple_jwt_login_cleanup_webhook_logs', array($simpleJwtLoginCron, 'cleanupWebhookLogs'));

// REGISTER REST Routes
add_action(
    'rest_api_init',
    function () use (
        $simpleJwtLoginWordPressRepository,
        $simpleJwtLoginJwtSettings,
        $simpleJwtLoginRefreshTokenRepo,
        $simpleJwtLoginAuditLogRepo,
        $simpleJwtLoginApiKeyRepo,
        $simpleJwtLoginWebhookLogRepo
    ) {
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $registrar = new RouteRegistrar($_SERVER, $_REQUEST, $_COOKIE);
        $registrar
            ->withWordPressRepository($simpleJwtLoginWordPressRepository)
            ->withSettings($simpleJwtLoginJwtSettings)
            ->withRefreshTokenRepo($simpleJwtLoginRefreshTokenRepo)
            ->withAuditLogRepo($simpleJwtLoginAuditLogRepo)
            ->withApiKeyRepo($simpleJwtLoginApiKeyRepo)
            ->withWebhookLogRepo($simpleJwtLoginWebhookLogRepo)
            ->register();
    }
);

// 3rd-party integrations
include_once '3rd-party/force_login.php';
include_once '3rd-party/wp-graphql.php';
