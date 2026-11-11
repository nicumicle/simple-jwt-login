<?php

namespace SimpleJWTLogin\Modules\Abilities\ApiKeys;

use SimpleJWTLogin\Modules\Abilities\AuditableAbilityInterface;
use SimpleJWTLogin\Services\ApiKeys\RevokeApiKeyService;

class RevokeApiKeyAbility extends AbstractApiKeyAbility implements AuditableAbilityInterface
{
    protected function getSlug()
    {
        return 'revoke-api-key';
    }

    public function isReadOnly()
    {
        return false;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Revoke a Simple JWT Login API key', 'simple-jwt-login'),
            'description'   => __(
                'Revokes an API key by id (see simple-jwt-login/list-api-keys). Requests using the key are rejected immediately. This cannot be undone.',
                'simple-jwt-login'
            ),
            'input_schema'  => [
                'type'                 => 'object',
                'properties'           => [
                    'id' => ['type' => 'integer', 'minimum' => 1],
                ],
                'required'             => ['id'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'id'      => ['type' => 'integer'],
                    'revoked' => ['type' => 'boolean'],
                ],
            ],
            'annotations'   => $this->annotations(false, true, true),
        ];
    }

    public function auditDetails(array $input, array $result)
    {
        return [
            'api_key_id' => isset($result['id']) ? (int) $result['id'] : (int) $this->inputValue($input, 'id', 0),
        ];
    }

    public function execute(array $input)
    {
        $keyId = (int) $this->inputValue($input, 'id', 0);
        $this->runService(new RevokeApiKeyService(), ['id' => $keyId]);

        return ['id' => $keyId, 'revoked' => true];
    }
}
