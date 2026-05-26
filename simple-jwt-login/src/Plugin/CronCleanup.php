<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;

class CronCleanup
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

    public function cleanupRefreshTokens()
    {
        $refreshTokenRepo = new RefreshTokenRepository($this->wpdb);
        $refreshTokenRepo->cleanupExpired();
    }

    public function cleanupAuditLogs()
    {
        $jwtSettings   = new SimpleJWTLoginSettings(new WordPressRepository());
        $retentionDays = $jwtSettings->getAuditLogSettings()->getRetentionDays();
        if ($retentionDays <= 0) {
            return;
        }
        $before = gmdate('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        (new AuditLogRepository($this->wpdb))->deleteOlderThan($before);
    }

    public function cleanupWebhookLogs()
    {
        $jwtSettings   = new SimpleJWTLoginSettings(new WordPressRepository());
        $retentionDays = $jwtSettings->getWebhooksSettings()->getRetentionDays();
        if ($retentionDays <= 0) {
            return;
        }
        $before = gmdate('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        (new WebhookLogRepository($this->wpdb))->deleteOlderThan($before);
    }
}
