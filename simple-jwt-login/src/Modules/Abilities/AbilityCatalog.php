<?php

namespace SimpleJWTLogin\Modules\Abilities;

use SimpleJWTLogin\Modules\Abilities\ApiKeys\CreateApiKeyAbility;
use SimpleJWTLogin\Modules\Abilities\ApiKeys\ListApiKeysAbility;
use SimpleJWTLogin\Modules\Abilities\ApiKeys\RevokeApiKeyAbility;
use SimpleJWTLogin\Modules\Abilities\AuditLog\AnalyzeAuditLogAbility;
use SimpleJWTLogin\Modules\Abilities\AuditLog\GetAuditLogAbility;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\DiagnosticAbilities;
use SimpleJWTLogin\Modules\Abilities\Settings\GetSettingsAbility;
use SimpleJWTLogin\Modules\Abilities\Settings\UpdateSettingsAbility;
use SimpleJWTLogin\Modules\Abilities\Webhooks\AddWebhookAbility;
use SimpleJWTLogin\Modules\Abilities\Webhooks\GetWebhookLogsAbility;
use SimpleJWTLogin\Modules\Abilities\Webhooks\ListWebhooksAbility;
use SimpleJWTLogin\Modules\Abilities\Webhooks\RemoveWebhookAbility;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepositoryInterface;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;

/**
 * Builds every Simple JWT Login ability. Instances are created lazily, only
 * when the Abilities API asks for them.
 */
class AbilityCatalog
{
    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

    /**
     * @var ApiKeyRepositoryInterface
     */
    private $apiKeyRepository;

    /**
     * @var AuditLogRepositoryInterface
     */
    private $auditLogRepository;

    /**
     * @var WebhookLogRepositoryInterface
     */
    private $webhookLogRepository;

    /**
     * @var RevokedTokenRepositoryInterface
     */
    private $revokedTokens;

    /**
     * @param SimpleJWTLoginSettings          $jwtSettings
     * @param ApiKeyRepositoryInterface       $apiKeyRepository
     * @param AuditLogRepositoryInterface     $auditLogRepository
     * @param WebhookLogRepositoryInterface   $webhookLogRepository
     * @param RevokedTokenRepositoryInterface $revokedTokens
     */
    public function __construct(
        SimpleJWTLoginSettings $jwtSettings,
        ApiKeyRepositoryInterface $apiKeyRepository,
        AuditLogRepositoryInterface $auditLogRepository,
        WebhookLogRepositoryInterface $webhookLogRepository,
        RevokedTokenRepositoryInterface $revokedTokens
    ) {
        $this->jwtSettings          = $jwtSettings;
        $this->apiKeyRepository     = $apiKeyRepository;
        $this->auditLogRepository   = $auditLogRepository;
        $this->webhookLogRepository = $webhookLogRepository;
        $this->revokedTokens        = $revokedTokens;
    }

    /**
     * @return AbilityInterface[]
     */
    public function all()
    {
        $policy = new AbilitySettingsPolicy();

        return array_merge(
            [
                new GetSettingsAbility($this->jwtSettings, $policy),
                new UpdateSettingsAbility($this->jwtSettings, $policy),
                new ListApiKeysAbility($this->jwtSettings, $this->apiKeyRepository),
                new CreateApiKeyAbility($this->jwtSettings, $this->apiKeyRepository),
                new RevokeApiKeyAbility($this->jwtSettings, $this->apiKeyRepository),
                new GetAuditLogAbility($this->jwtSettings, $this->auditLogRepository),
                new AnalyzeAuditLogAbility($this->jwtSettings, $this->auditLogRepository),
            ],
            DiagnosticAbilities::create($this->jwtSettings, $policy, $this->revokedTokens),
            [
                new ListWebhooksAbility($this->jwtSettings),
                new GetWebhookLogsAbility($this->jwtSettings, $this->webhookLogRepository),
                new AddWebhookAbility($this->jwtSettings),
                new RemoveWebhookAbility($this->jwtSettings),
            ]
        );
    }
}
