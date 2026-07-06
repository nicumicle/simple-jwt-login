<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

class Lifecycle
{
    /**
     * @var RefreshTokenRepository
     */
    private $refreshTokenRepo;

    /**
     * @var AuditLogRepository
     */
    private $auditLogRepository;

    /**
     * @var WebhookLogRepository
     */
    private $webhookLogRepository;

    /**
     * @var ApiKeyRepository
     */
    private $apiKeyRepository;

    /**
     * @var RevokedTokenRepository
     */
    private $revokedTokenRepo;

    /**
     * @var WordPressRepository
     */
    private $wordPressRepository;

    public function __construct(
        RefreshTokenRepository $refreshTokenRepo,
        AuditLogRepository $auditLogRepository,
        WebhookLogRepository $webhookLogRepository,
        ApiKeyRepository $apiKeyRepository,
        RevokedTokenRepository $revokedTokenRepo,
        WordPressRepository $wordPressRepository
    ) {
        $this->refreshTokenRepo = $refreshTokenRepo;
        $this->auditLogRepository = $auditLogRepository;
        $this->webhookLogRepository = $webhookLogRepository;
        $this->apiKeyRepository = $apiKeyRepository;
        $this->revokedTokenRepo = $revokedTokenRepo;
        $this->wordPressRepository = $wordPressRepository;
    }

    public function activate()
    {
        $this->createAllTables();
        $this->ensureRefreshTokenKey();
        $this->migrateRevokedTokens();
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
        if (!wp_next_scheduled('simple_jwt_login_cleanup_revoked_tokens')) {
            wp_schedule_event(time(), 'daily', 'simple_jwt_login_cleanup_revoked_tokens');
        }
    }

    public function deactivate()
    {
        wp_clear_scheduled_hook('simple_jwt_login_cleanup_refresh_tokens');
        wp_clear_scheduled_hook('simple_jwt_login_cleanup_audit_logs');
        wp_clear_scheduled_hook('simple_jwt_login_cleanup_webhook_logs');
        wp_clear_scheduled_hook('simple_jwt_login_cleanup_revoked_tokens');
    }

    // WordPress requires a static callable for register_uninstall_hook — injection not possible here.
    public static function uninstall()
    {
        global $wpdb;
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        delete_option('simple_jwt_login_db_version');
        (new RefreshTokenRepository($wpdb))->dropTable();
        (new AuditLogRepository($wpdb))->dropTable();
        (new WebhookLogRepository($wpdb))->dropTable();
        (new ApiKeyRepository($wpdb))->dropTable();
        (new RevokedTokenRepository($wpdb))->dropTable();
    }

    public function checkDbVersion()
    {
        if (get_option('simple_jwt_login_db_version') !== SIMPLE_JWT_LOGIN_DB_VERSION) {
            $this->createAllTables();
            $this->ensureRefreshTokenKey();
            $this->migrateRevokedTokens();
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
        $this->refreshTokenRepo->createTable();
        $this->auditLogRepository->createTable();
        $this->webhookLogRepository->createTable();
        $this->apiKeyRepository->createTable();
        $this->revokedTokenRepo->createTable();
    }

    protected function migrateRevokedTokens()
    {
        (new RevokedTokenMigrator($this->wordPressRepository, $this->revokedTokenRepo))->migrate();
    }

    protected function ensureRefreshTokenKey()
    {
        $raw = get_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        $settings = is_string($raw) ? json_decode($raw, true) : array();
        if (!is_array($settings)) {
            $settings = array();
        }
        if (empty($settings['authorization']['refresh_token_key'])) {
            $settings['authorization']['refresh_token_key'] = bin2hex(openssl_random_pseudo_bytes(32));
            update_option(SimpleJWTLoginSettings::OPTIONS_KEY, json_encode($settings));
        }
    }
}
