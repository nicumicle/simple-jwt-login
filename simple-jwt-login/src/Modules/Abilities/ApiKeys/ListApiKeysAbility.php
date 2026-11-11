<?php

namespace SimpleJWTLogin\Modules\Abilities\ApiKeys;

use SimpleJWTLogin\Services\ApiKeys\ListApiKeysService;

class ListApiKeysAbility extends AbstractApiKeyAbility
{
    protected function getSlug()
    {
        return 'list-api-keys';
    }

    public function isReadOnly()
    {
        return true;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('List Simple JWT Login API keys', 'simple-jwt-login'),
            'description'   => __(
                'Lists API keys (newest first) with their id, name, masked prefix, permissions, owner, expiry, last use and revocation date. Raw key values are never returned.',
                'simple-jwt-login'
            ),
            'input_schema'  => $this->objectSchema([
                'page'     => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20],
            ]),
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'items'    => ['type' => 'array'],
                    'total'    => ['type' => 'integer'],
                    'page'     => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                ],
            ],
            'annotations'   => $this->annotations(true, false, true),
        ];
    }

    public function execute(array $input)
    {
        return $this->runService(new ListApiKeysService(), [
            'page'     => (int) $this->inputValue($input, 'page', 1),
            'per_page' => (int) $this->inputValue($input, 'per_page', 20),
        ]);
    }
}
