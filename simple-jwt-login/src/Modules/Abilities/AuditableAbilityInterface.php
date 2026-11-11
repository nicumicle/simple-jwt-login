<?php

namespace SimpleJWTLogin\Modules\Abilities;

/**
 * A write ability that adds identifiers to its audit log entry.
 */
interface AuditableAbilityInterface extends AbilityInterface
{
    /**
     * Non-secret identifiers recorded in the audit log (e.g. webhook URL,
     * API key id). Never include secrets or raw keys.
     *
     * @param array $input
     * @param array $result Ability result; empty when the ability failed.
     * @return array
     */
    public function auditDetails(array $input, array $result);
}
