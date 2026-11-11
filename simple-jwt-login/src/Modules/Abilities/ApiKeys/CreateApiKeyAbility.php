<?php

namespace SimpleJWTLogin\Modules\Abilities\ApiKeys;

use SimpleJWTLogin\Modules\Abilities\AuditableAbilityInterface;
use SimpleJWTLogin\Helpers\ApiKeyPermissions;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;
use SimpleJWTLogin\Services\ApiKeys\CreateApiKeyService;

class CreateApiKeyAbility extends AbstractApiKeyAbility implements AuditableAbilityInterface
{
    protected function getSlug()
    {
        return 'create-api-key';
    }

    public function isReadOnly()
    {
        return false;
    }

    /**
     * The raw key is a live credential handed to the AI agent.
     *
     * @return string
     */
    public function getRequiredPermission()
    {
        return AiAbilitiesSettings::PERMISSION_CREATE_API_KEYS;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Create a Simple JWT Login API key', 'simple-jwt-login'),
            'description'   => __(
                'Creates an API key owned by the current user. The raw key is returned only once in the "key" field and cannot be retrieved later (only its hash is stored), so hand it to the user right away and do not store it elsewhere.',
                'simple-jwt-login'
            ),
            'input_schema'  => [
                'type'                 => 'object',
                'properties'           => [
                    'name'        => ['type' => 'string', 'minLength' => 1],
                    'permissions' => [
                        'type'     => 'array',
                        'minItems' => 1,
                        'items'    => ['type' => 'string', 'enum' => ApiKeyPermissions::$all],
                    ],
                    'expires_at'  => [
                        'type'        => 'string',
                        'description' => __('Optional expiry date (Y-m-d H:i:s, UTC). Omit for a key that never expires.', 'simple-jwt-login'),
                    ],
                ],
                'required'             => ['name', 'permissions'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'id'          => ['type' => 'integer'],
                    'key'         => ['type' => 'string'],
                    'name'        => ['type' => 'string'],
                    'key_prefix'  => ['type' => 'string'],
                    'permissions' => ['type' => 'array'],
                    'expires_at'  => ['type' => ['string', 'null']],
                ],
            ],
            'annotations'   => $this->annotations(false, false, false),
        ];
    }

    public function auditDetails(array $input, array $result)
    {
        return [
            'api_key_id'  => isset($result['id']) ? (int) $result['id'] : null,
            'name'        => (string) $this->inputValue($input, 'name', ''),
            'permissions' => (array) $this->inputValue($input, 'permissions', []),
        ];
    }

    public function execute(array $input)
    {
        $result = $this->runService(new CreateApiKeyService(), [
            'name'        => (string) $this->inputValue($input, 'name', ''),
            'permissions' => (array) $this->inputValue($input, 'permissions', []),
            'expires_at'  => $this->inputValue($input, 'expires_at'),
        ]);
        if (isset($result['id'])) {
            $result['id'] = (int) $result['id'];
        }

        return $result;
    }
}
