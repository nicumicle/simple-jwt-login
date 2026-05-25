<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;

class CronCleanup
{
    public function cleanupRefreshTokens()
    {
        global $wpdb;
        $refreshTokenRepo = new RefreshTokenRepository($wpdb);
        $refreshTokenRepo->cleanupExpired();
    }

    public function cleanupAuditLogs()
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

    public function cleanupWebhookLogs()
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
}
