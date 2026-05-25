<?php

namespace SimpleJWTLogin\Services;

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
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
     * @param int|null    $apiKeyId
     * @return void
     */
    /**
     * Register all audit WordPress hooks at once.
     *
     * @return void
     */
    public function registerAuditHooks()
    {
        $auditLogger = $this;

        $successHooks = array(
            SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SUCCESS          => AuditEvents::AUTH_LOGIN_SUCCESS,
            SimpleJWTLoginHooks::AUDIT_AUTH_LOGOUT_SUCCESS         => AuditEvents::AUTH_LOGOUT_SUCCESS,
            SimpleJWTLoginHooks::AUDIT_AUTH_REGISTER_SUCCESS       => AuditEvents::AUTH_REGISTER_SUCCESS,
            SimpleJWTLoginHooks::AUDIT_AUTH_PASSWORD_RESET_REQUEST => AuditEvents::AUTH_PASSWORD_RESET_REQUEST,
            SimpleJWTLoginHooks::AUDIT_AUTH_PASSWORD_RESET_SUCCESS => AuditEvents::AUTH_PASSWORD_RESET_SUCCESS,
            SimpleJWTLoginHooks::AUDIT_AUTH_DELETE_USER_SUCCESS    => AuditEvents::AUTH_DELETE_USER_SUCCESS,
            SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SESSION_SUCCESS  => AuditEvents::AUTH_LOGIN_SESSION_SUCCESS,
            SimpleJWTLoginHooks::AUDIT_AUTH_REFRESH_TOKEN_SUCCESS  => AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS,
            SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_SUCCESS          => AuditEvents::AUTH_OAUTH_SUCCESS,
            SimpleJWTLoginHooks::AUDIT_2FA_CHALLENGE_ISSUED        => AuditEvents::AUTH_2FA_CHALLENGE_ISSUED,
            SimpleJWTLoginHooks::AUDIT_2FA_VERIFY_SUCCESS          => AuditEvents::AUTH_2FA_VERIFY_SUCCESS,
        );

        $failureHooks = array(
            SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_FAILED          => AuditEvents::AUTH_LOGIN_FAILED,
            SimpleJWTLoginHooks::AUDIT_AUTH_LOGOUT_FAILED         => AuditEvents::AUTH_LOGOUT_FAILED,
            SimpleJWTLoginHooks::AUDIT_AUTH_REGISTER_FAILED       => AuditEvents::AUTH_REGISTER_FAILED,
            SimpleJWTLoginHooks::AUDIT_AUTH_PASSWORD_RESET_FAILED => AuditEvents::AUTH_PASSWORD_RESET_FAILED,
            SimpleJWTLoginHooks::AUDIT_AUTH_DELETE_USER_FAILED    => AuditEvents::AUTH_DELETE_USER_FAILED,
            SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SESSION_FAILED  => AuditEvents::AUTH_LOGIN_SESSION_FAILED,
            SimpleJWTLoginHooks::AUDIT_AUTH_REFRESH_TOKEN_FAILED  => AuditEvents::AUTH_REFRESH_TOKEN_FAILED,
            SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_FAILED          => AuditEvents::AUTH_OAUTH_FAILED,
            SimpleJWTLoginHooks::AUDIT_2FA_VERIFY_FAILED          => AuditEvents::AUTH_2FA_VERIFY_FAILED,
        );

        foreach ($successHooks as $hookName => $eventType) {
            add_action($hookName, function ($userId, $userEmail) use ($auditLogger, $eventType) {
                $auditLogger->log($eventType, $userId, $userEmail, 'success');
            }, 10, 2);
        }

        foreach ($failureHooks as $hookName => $eventType) {
            add_action($hookName, function ($userId, $userEmail, $message) use ($auditLogger, $eventType) {
                $auditLogger->log($eventType, $userId, $userEmail, 'failure', $message);
            }, 10, 3);
        }
    }

    public function log($eventType, $userId, $userEmail, $status, $message = null, $apiKeyId = null)
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
            $message,
            $apiKeyId
        );
    }
}
