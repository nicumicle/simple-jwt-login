<?php

namespace SimpleJWTLogin\Services;

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepositoryInterface;

class AuditLoggerService
{
    /**
     * @var AuditLogRepositoryInterface
     */
    private $repository;

    /**
     * @var AuditLogSettings
     */
    private $settings;

    /**
     * @var ServerHelper
     */
    private $serverHelper;

    /**
     * @param AuditLogRepositoryInterface $repository
     * @param AuditLogSettings            $settings
     * @param ServerHelper                $serverHelper
     */
    public function __construct(
        AuditLogRepositoryInterface $repository,
        AuditLogSettings $settings,
        ServerHelper $serverHelper
    ) {
        $this->repository   = $repository;
        $this->settings     = $settings;
        $this->serverHelper = $serverHelper;
    }

    /**
     * Write an audit log entry if logging is enabled and the event type is enabled.
     *
     * @param string      $eventType
     * @param int|null    $userId
     * @param string|null $userEmail
     * @param string      $status    'success' or 'failure'
     * @param string|null $message
     * @return void
     */
    public function log($eventType, $userId, $userEmail, $status, $message = null)
    {
        if (!$this->settings->isEnabled()) {
            return;
        }

        if (!$this->settings->isEventEnabled($eventType)) {
            return;
        }

        $this->repository->insert(
            $eventType,
            $userId,
            $userEmail,
            $this->serverHelper->getClientIP(),
            $status,
            $message
        );
    }
}
