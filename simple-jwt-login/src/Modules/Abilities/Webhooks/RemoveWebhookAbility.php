<?php

namespace SimpleJWTLogin\Modules\Abilities\Webhooks;

use SimpleJWTLogin\Modules\Abilities\AuditableAbilityInterface;
use Exception;
use SimpleJWTLogin\ErrorCodes;

class RemoveWebhookAbility extends AbstractWebhookAbility implements AuditableAbilityInterface
{
    protected function getSlug()
    {
        return 'remove-webhook';
    }

    public function isReadOnly()
    {
        return false;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Remove a Simple JWT Login webhook', 'simple-jwt-login'),
            'description'   => __(
                'Removes a webhook. Pass both its index and its URL as returned by simple-jwt-login/list-webhooks; the call fails when they do not match, so a stale index never removes the wrong webhook. Indexes of the following webhooks shift down by one.',
                'simple-jwt-login'
            ),
            'input_schema'  => [
                'type'                 => 'object',
                'properties'           => [
                    'index' => ['type' => 'integer', 'minimum' => 0],
                    'url'   => ['type' => 'string'],
                ],
                'required'             => ['index', 'url'],
                'additionalProperties' => false,
            ],
            'output_schema' => $this->webhookOutputSchema(),
            'annotations'   => $this->annotations(false, true, false),
        ];
    }

    public function auditDetails(array $input, array $result)
    {
        return [
            'url'   => $this->maskedUrl($this->inputValue($input, 'url', '')),
            'index' => isset($result['index']) ? (int) $result['index'] : (int) $this->inputValue($input, 'index', -1),
        ];
    }

    public function execute(array $input)
    {
        $index    = (int) $this->inputValue($input, 'index', -1);
        $url      = (string) $this->inputValue($input, 'url', '');
        $settings = $this->jwtSettings->getStoredSettings();
        $items    = $this->storedItems($settings);

        // list-webhooks masks query values, so compare the masked forms.
        if (!isset($items[$index]['url'])
            || $this->maskedUrl($items[$index]['url']) !== $this->maskedUrl($url)
        ) {
            throw new Exception(
                esc_html__('No webhook matches this index and URL. List the webhooks again and retry.', 'simple-jwt-login'),
                absint(ErrorCodes::ERR_ABILITY_WEBHOOK_NOT_FOUND)
            );
        }

        $removed = (array) $items[$index];
        unset($items[$index]);
        $this->jwtSettings->saveSettingsArray($this->withItems($settings, $items));

        return $this->presentWebhook($removed, $index);
    }
}
