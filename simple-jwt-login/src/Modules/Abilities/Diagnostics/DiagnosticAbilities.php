<?php

namespace SimpleJWTLogin\Modules\Abilities\Diagnostics;

use SimpleJWTLogin\Modules\Abilities\AbilityInterface;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;

/**
 * Builds the troubleshooting abilities: diagnose, security-report,
 * explain-error and inspect-jwt.
 */
class DiagnosticAbilities
{
    /**
     * @param SimpleJWTLoginSettings          $jwtSettings
     * @param AbilitySettingsPolicy           $policy
     * @param RevokedTokenRepositoryInterface $revokedTokens
     * @return AbilityInterface[]
     */
    public static function create(
        SimpleJWTLoginSettings $jwtSettings,
        AbilitySettingsPolicy $policy,
        RevokedTokenRepositoryInterface $revokedTokens
    ) {
        $twoFactorBridge = new TwoFactorBridge();

        return [
            new DiagnoseAbility($jwtSettings, $twoFactorBridge),
            new SecurityReportAbility($jwtSettings, $twoFactorBridge),
            new ExplainErrorAbility($jwtSettings, $policy),
            new InspectJwtAbility($jwtSettings, $revokedTokens),
        ];
    }
}
