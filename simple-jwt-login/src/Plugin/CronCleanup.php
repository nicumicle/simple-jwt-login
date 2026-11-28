<?php

namespace SimpleJWTLogin\Plugin;

use Exception;
use SimpleJWTLogin\Helpers\Jwt\KeyRotation;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
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

    /**
     * @var RevokedTokenRepository
     */
    private $revokedTokenRepo;

    public function __construct(
        SimpleJWTLoginSettings $jwtSettings,
        RefreshTokenRepository $refreshTokenRepo,
        AuditLogRepository $auditLogRepository,
        WebhookLogRepository $webhookLogRepository,
        RevokedTokenRepository $revokedTokenRepo
    ) {
        $this->jwtSettings = $jwtSettings;
        $this->refreshTokenRepo = $refreshTokenRepo;
        $this->auditLogRepository = $auditLogRepository;
        $this->webhookLogRepository = $webhookLogRepository;
        $this->revokedTokenRepo = $revokedTokenRepo;
    }

    public function cleanupRefreshTokens()
    {
        $this->refreshTokenRepo->cleanupExpired();
    }

    public function cleanupRevokedTokens()
    {
        $this->revokedTokenRepo->deleteExpired();
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

    /**
     * Remove the signing keys kept by `wp jwt key rotate` once every token they signed has expired.
     *
     * @return void
     */
    public function cleanupRotatedKeys()
    {
        $settings = $this->jwtSettings->getStoredSettings();
        $kept = KeyRotation::removeExpired($settings, KeyRotation::graceSeconds($this->jwtSettings), time());
        if ($kept === $settings) {
            return;
        }
        try {
            $this->jwtSettings->saveSettingsArray($kept);
        } catch (Exception $exception) {
            // Only when the stored settings are already invalid (an admin save fails the same way and
            // shows why). A cron run must not fail: the keys are removed by the next run that can save.
            return;
        }
    }
}
