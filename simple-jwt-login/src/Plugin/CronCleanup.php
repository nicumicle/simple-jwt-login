<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;

class CronCleanup
{
    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

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

    public function __construct(
        SimpleJWTLoginSettings $jwtSettings,
        RefreshTokenRepository $refreshTokenRepo,
        AuditLogRepository $auditLogRepository,
        WebhookLogRepository $webhookLogRepository
    ) {
        $this->jwtSettings = $jwtSettings;
        $this->refreshTokenRepo = $refreshTokenRepo;
        $this->auditLogRepository = $auditLogRepository;
        $this->webhookLogRepository = $webhookLogRepository;
    }

    public function cleanupRefreshTokens()
    {
        $this->refreshTokenRepo->cleanupExpired();
    }

    public function cleanupAuditLogs()
    {
        $retentionDays = $this->jwtSettings->getAuditLogSettings()->getRetentionDays();
        if ($retentionDays <= 0) {
            return;
        }
        $before = gmdate('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        $this->auditLogRepository->deleteOlderThan($before);
    }

    public function cleanupWebhookLogs()
    {
        $retentionDays = $this->jwtSettings->getWebhooksSettings()->getRetentionDays();
        if ($retentionDays <= 0) {
            return;
        }
        $before = gmdate('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        $this->webhookLogRepository->deleteOlderThan($before);
    }
}
