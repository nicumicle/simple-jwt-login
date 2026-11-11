<?php

namespace SimpleJWTLogin\Modules\Abilities\Webhooks;

use SimpleJWTLogin\Modules\Settings\WebhooksSettings;

class ListWebhooksAbility extends AbstractWebhookAbility
{
    protected function getSlug()
    {
        return 'list-webhooks';
    }

    public function isReadOnly()
    {
        return true;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('List Simple JWT Login webhooks', 'simple-jwt-login'),
            'description'   => __(
                'Lists the configured outgoing webhooks (optionally only those subscribed to one event) with their index, URL, method, events, headers (values masked), payload template and timeout, plus whether webhooks and webhook logs are enabled.',
                'simple-jwt-login'
            ),
            'input_schema'  => $this->objectSchema([
                'event' => [
                    'type'        => 'string',
                    'enum'        => WebhooksSettings::$allowedEvents,
                    'description' => __('Optional: only return webhooks subscribed to this event.', 'simple-jwt-login'),
                ],
            ]),
            'output_schema' => [
                'type'       => 'object',
                'properties' => [
                    'enabled'        => ['type' => 'boolean'],
                    'logs_enabled'   => ['type' => 'boolean'],
                    'retention_days' => ['type' => 'integer'],
                    'items'          => ['type' => 'array', 'items' => $this->webhookOutputSchema()],
                ],
            ],
            'annotations'   => $this->annotations(true, false, true),
        ];
    }

    public function execute(array $input)
    {
        $webhooksSettings = $this->jwtSettings->getWebhooksSettings();

        $event = $this->inputValue($input, 'event');

        $items = [];
        foreach ($this->storedItems($this->jwtSettings->getStoredSettings()) as $index => $item) {
            $item   = (array) $item;
            $events = isset($item['events']) ? (array) $item['events'] : [];
            if ($event !== null && !in_array($event, $events, true)) {
                continue;
            }
            $items[] = $this->presentWebhook($item, $index);
        }

        return [
            'enabled'        => $webhooksSettings->isEnabled(),
            'logs_enabled'   => $webhooksSettings->isWebhookLogsEnabled(),
            'retention_days' => (int) $webhooksSettings->getRetentionDays(),
            'items'          => $items,
        ];
    }
}
