<?php

namespace SimpleJWTLogin\Cli;

use SimpleJWTLogin\Plugin\CronCleanup;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;

/**
 * Run the daily cleanup now.
 */
class CleanupCommand extends AbstractCommand
{
    /**
     * Run the daily cleanup now: expired refresh and revoked tokens, log entries older than
     * their retention period, and signing keys kept by `wp jwt key rotate` once their tokens expired.
     *
     * ## EXAMPLES
     *
     *     wp jwt cleanup
     *
     * @param array $args
     * @return void
     */
    public function __invoke(array $args)
    {
        $this->rejectPositionalArgs($args);
        $cleanup = $this->createCleanup();
        $this->audited('cleanup', function () use ($cleanup) {
            $cleanup->cleanupRefreshTokens();
            $cleanup->cleanupRevokedTokens();
            $cleanup->cleanupAuditLogs();
            $cleanup->cleanupWebhookLogs();
            $cleanup->cleanupRotatedKeys();
        });
        \WP_CLI::success(__('Cleanup done.', 'simple-jwt-login'));
    }

    /**
     * @return CronCleanup
     */
    protected function createCleanup()
    {
        global $wpdb;

        return new CronCleanup(
            $this->createSettings(),
            new RefreshTokenRepository($wpdb),
            new AuditLogRepository($wpdb),
            new WebhookLogRepository($wpdb),
            new RevokedTokenRepository($wpdb)
        );
    }
}
