<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;

class Lifecycle
{
    /** @var \wpdb */
    private $wpdb;
    
    /**
     * @param  \wpdb $wpdb
    */
    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function activate()
    {
        $this->createAllTables();
        $this->ensureRefreshTokenKey();
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

    public function deactivate()
    {
        wp_clear_scheduled_hook('simple_jwt_login_cleanup_refresh_tokens');
        wp_clear_scheduled_hook('simple_jwt_login_cleanup_audit_logs');
        wp_clear_scheduled_hook('simple_jwt_login_cleanup_webhook_logs');
    }

    public static function uninstall()
    {
        global $wpdb;
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        delete_option('simple_jwt_login_db_version');
        (new RefreshTokenRepository($wpdb))->dropTable();
        (new AuditLogRepository($wpdb))->dropTable();
        (new WebhookLogRepository($wpdb))->dropTable();
        (new ApiKeyRepository($wpdb))->dropTable();
    }

    public function checkDbVersion()
    {
        if (get_option('simple_jwt_login_db_version') !== SIMPLE_JWT_LOGIN_DB_VERSION) {
            $this->createAllTables();
            $this->ensureRefreshTokenKey();
            update_option('simple_jwt_login_db_version', SIMPLE_JWT_LOGIN_DB_VERSION);
        }
    }

    public function loadTranslations()
    {
        load_plugin_textdomain(
            'simple-jwt-login',
            false,
            plugin_basename(dirname(SIMPLE_JWT_LOGIN_PLUGIN_FILE)) . '/i18n/'
        );
    }

    protected function createAllTables()
    {
        (new RefreshTokenRepository($this->wpdb))->createTable();
        (new AuditLogRepository($this->wpdb))->createTable();
        (new WebhookLogRepository($this->wpdb))->createTable();
        (new ApiKeyRepository($this->wpdb))->createTable();
    }

    protected function ensureRefreshTokenKey()
    {
        $raw = get_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        $settings = is_string($raw) ? json_decode($raw, true) : array();
        if (!is_array($settings)) {
            $settings = array();
        }
        if (empty($settings['refresh_token_key'])) {
            $settings['refresh_token_key'] = bin2hex(random_bytes(32));
            update_option(SimpleJWTLoginSettings::OPTIONS_KEY, json_encode($settings));
        }
    }
}
