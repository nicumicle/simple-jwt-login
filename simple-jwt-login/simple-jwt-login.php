<?php

/*
    Plugin Name: Simple JWT Login - JWT Authentication for REST API
    Plugin URI: https://simplejwtlogin.com
    Description: Simple-JWT-Login REST API Plugin. Allows you to login / register to WordPress using JWT.
    Author: Nicu Micle
    Author URI: https://profiles.wordpress.org/nicu_m/
    Text Domain: simple-jwt-login
    Domain Path: /i18n
    License: GPLv3
    License URI: https://github.com/nicumicle/simple-jwt-login/blob/master/LICENSE
    Version: 4.2.1
    Requires at least: 6.2
*/

use SimpleJWTLogin\Cli\CliRegistrar;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Abilities\AbilitiesRegistrar;
use SimpleJWTLogin\Modules\Abilities\AbilityCatalog;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Plugin\AdminUI;
use SimpleJWTLogin\Plugin\CronCleanup;
use SimpleJWTLogin\Plugin\Lifecycle;
use SimpleJWTLogin\Plugin\LoginPageIntegration;
use SimpleJWTLogin\Plugin\OAuthTwoFactorLoginHandler;
use SimpleJWTLogin\Plugin\PasswordChangeListener;
use SimpleJWTLogin\Plugin\Shortcodes;
use SimpleJWTLogin\Plugin\UserApiKeysPage;
use SimpleJWTLogin\Plugin\UserDeletionListener;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Routes\RouteRegistrar;
use SimpleJWTLogin\Services\AuditLoggerService;
use SimpleJWTLogin\Services\Oauth\AbstractOauth;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

define('SIMPLE_JWT_LOGIN_VERSION', '4.2.1');
define('SIMPLE_JWT_LOGIN_DB_VERSION', '1.10');
define('SIMPLE_JWT_LOGIN_PLUGIN_FILE', __FILE__);

include_once 'autoload.php';

global $wpdb;

// Shared settings (used by LoginPageIntegration and CronCleanup)
$simpleJwtLoginWordPressRepository = WordPressRepository::getInstance();
$simpleJwtLoginJwtSettings = new SimpleJWTLoginSettings($simpleJwtLoginWordPressRepository);

// User-facing API Keys menu (for non-admin users when the setting is enabled).
// Only relevant inside wp-admin, so skip building it on front-end/REST/cron requests.
if (is_admin()) {
    $simpleJwtLoginUserApiKeysPage = new UserApiKeysPage($simpleJwtLoginJwtSettings);
    add_action('admin_menu', array($simpleJwtLoginUserApiKeysPage, 'registerMenuEntry'));
}

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
$simpleJwtLoginRevokedTokenRepo = new RevokedTokenRepository($wpdb);

// When enabled, a password change revokes the user's refresh tokens and every JWT issued before it.
$simpleJwtLoginPasswordChange = new PasswordChangeListener(
    $simpleJwtLoginWordPressRepository,
    $simpleJwtLoginRefreshTokenRepo,
    $simpleJwtLoginJwtSettings->getAuthenticationSettings()
);
add_action('after_password_reset', array($simpleJwtLoginPasswordChange, 'onPasswordReset'));
add_action('profile_update', array($simpleJwtLoginPasswordChange, 'onProfileUpdate'), 10, 3);
add_action('wp_set_password', array($simpleJwtLoginPasswordChange, 'onPasswordSet'), 10, 2);

// A user deleted in wp-admin must not keep working API keys or refresh tokens.
$simpleJwtLoginUserDeletion = new UserDeletionListener($simpleJwtLoginApiKeyRepo, $simpleJwtLoginRefreshTokenRepo);
add_action('deleted_user', array($simpleJwtLoginUserDeletion, 'onUserDeleted'));

// Admin UI - only needed inside wp-admin, so skip building it on front-end/REST/cron requests.
if (is_admin()) {
    //phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.NonceVerification.Missing
    $simpleJwtLoginAdminUI = new AdminUI(
        $_SERVER,
        $_POST,
        $simpleJwtLoginJwtSettings,
        $simpleJwtLoginAuditLogRepo,
        $simpleJwtLoginWebhookLogRepo,
        $simpleJwtLoginApiKeyRepo,
        $simpleJwtLoginRevokedTokenRepo
    );
    add_action('admin_menu', array($simpleJwtLoginAdminUI, 'registerMenuEntry'));
    add_filter(
        'plugin_action_links_' . plugin_basename(__FILE__),
        array($simpleJwtLoginAdminUI, 'addPluginActionLinks')
    );
}

// Lifecycle (activation, deactivation, uninstall, migration, i18n)
$simpleJwtLoginLifecycle = new Lifecycle(
    $simpleJwtLoginRefreshTokenRepo,
    $simpleJwtLoginAuditLogRepo,
    $simpleJwtLoginWebhookLogRepo,
    $simpleJwtLoginApiKeyRepo,
    $simpleJwtLoginRevokedTokenRepo,
    $simpleJwtLoginWordPressRepository
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
    $simpleJwtLoginWebhookLogRepo,
    $simpleJwtLoginRevokedTokenRepo
);
add_action('simple_jwt_login_cleanup_refresh_tokens', array($simpleJwtLoginCron, 'cleanupRefreshTokens'));
add_action('simple_jwt_login_cleanup_refresh_tokens', array($simpleJwtLoginCron, 'cleanupRotatedKeys'));
add_action('simple_jwt_login_cleanup_audit_logs', array($simpleJwtLoginCron, 'cleanupAuditLogs'));
add_action('simple_jwt_login_cleanup_webhook_logs', array($simpleJwtLoginCron, 'cleanupWebhookLogs'));
add_action('simple_jwt_login_cleanup_revoked_tokens', array($simpleJwtLoginCron, 'cleanupRevokedTokens'));

// REGISTER REST Routes
add_action(
    'rest_api_init',
    function () use (
        $simpleJwtLoginWordPressRepository,
        $simpleJwtLoginJwtSettings,
        $simpleJwtLoginRefreshTokenRepo,
        $simpleJwtLoginAuditLogRepo,
        $simpleJwtLoginApiKeyRepo,
        $simpleJwtLoginWebhookLogRepo,
        $simpleJwtLoginRevokedTokenRepo
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
            ->withRevokedTokenRepo($simpleJwtLoginRevokedTokenRepo)
            ->register();
    }
);

// WordPress Abilities API (AI agents / MCP). Requires WordPress 6.9+: on older
// versions these hooks never fire, so nothing is registered.
$simpleJwtLoginServerHelper = $simpleJwtLoginJwtSettings->getGeneralSettings()->isTrustIpHeadersEnabled()
    ? ServerHelper::withTrustedProxyHeaders($_SERVER)
    : new ServerHelper($_SERVER);
$simpleJwtLoginAbilities = new AbilitiesRegistrar(
    $simpleJwtLoginJwtSettings,
    new AbilityCatalog(
        $simpleJwtLoginJwtSettings,
        $simpleJwtLoginApiKeyRepo,
        $simpleJwtLoginAuditLogRepo,
        $simpleJwtLoginWebhookLogRepo,
        $simpleJwtLoginRevokedTokenRepo
    ),
    new AuditLoggerService(
        $simpleJwtLoginAuditLogRepo,
        $simpleJwtLoginJwtSettings->getAuditLogSettings(),
        $simpleJwtLoginServerHelper,
        $simpleJwtLoginWordPressRepository
    )
);
add_action('wp_abilities_api_categories_init', array($simpleJwtLoginAbilities, 'registerCategory'));
add_action('wp_abilities_api_init', array($simpleJwtLoginAbilities, 'registerAbilities'));
// Non-administrators must not see (list, describe or probe) abilities they cannot run.
add_filter('rest_pre_dispatch', array($simpleJwtLoginAbilities, 'filterRestPreDispatch'));

// WP-CLI commands (wp jwt ...), when enabled in General > CLI and the old CLI add-on is not active.
$simpleJwtLoginCli = new CliRegistrar($simpleJwtLoginJwtSettings->getGeneralSettings());
add_action('plugins_loaded', array($simpleJwtLoginCli, 'register'));

// 3rd-party integrations
include_once '3rd-party/force_login.php';
include_once '3rd-party/wp-graphql.php';
include_once '3rd-party/woocommerce.php';
