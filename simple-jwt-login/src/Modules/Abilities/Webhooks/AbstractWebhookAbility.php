<?php

namespace SimpleJWTLogin\Modules\Abilities\Webhooks;

use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\AbstractAbility;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;

abstract class AbstractWebhookAbility extends AbstractAbility
{
    /**
     * Raw stored webhook items (payload templates still base64-encoded).
     *
     * @param array $settings
     * @return array
     */
    protected function storedItems(array $settings)
    {
        return isset($settings['webhooks']['items']) && is_array($settings['webhooks']['items'])
            ? array_values($settings['webhooks']['items'])
            : [];
    }

    /**
     * @param array $settings
     * @param array $items
     * @return array New settings array with the webhook items replaced.
     */
    protected function withItems(array $settings, array $items)
    {
        $webhooks          = isset($settings['webhooks']) && is_array($settings['webhooks']) ? $settings['webhooks'] : [];
        $webhooks['items'] = array_values($items);
        $settings['webhooks'] = $webhooks;

        return $settings;
    }

    /**
     * Webhook URL with query-string values always masked (audit log and
     * remove-webhook comparison), whatever the AI permissions are.
     *
     * @param string $url
     * @return string
     */
    protected function maskedUrl($url)
    {
        return (new SettingsRedactor(new AbilitySettingsPolicy()))->maskUrlQuery((string) $url);
    }

    /**
     * Decoded, redacted webhook ready to hand to an AI agent.
     *
     * @param array $storedItem
     * @param int   $index
     * @return array
     */
    protected function presentWebhook(array $storedItem, $index)
    {
        if (isset($storedItem['payload_template'])) {
            $storedItem['payload_template'] = (string) base64_decode((string) $storedItem['payload_template']);
        }
        $webhook          = (new SettingsRedactor($this->permissionPolicy()))->redactWebhook($storedItem);
        $webhook['index'] = (int) $index;

        return $webhook;
    }

    /**
     * @return array
     */
    protected function webhookOutputSchema()
    {
        return [
            'type'       => 'object',
            'properties' => [
                'index'            => ['type' => 'integer'],
                'url'              => ['type' => 'string'],
                'enabled'          => ['type' => 'boolean'],
                'method'           => ['type' => 'string'],
                'events'           => ['type' => 'array', 'items' => ['type' => 'string']],
                'headers'          => ['type' => 'array'],
                'payload_template' => ['type' => 'string'],
                'timeout'          => ['type' => 'integer'],
            ],
        ];
    }
}
