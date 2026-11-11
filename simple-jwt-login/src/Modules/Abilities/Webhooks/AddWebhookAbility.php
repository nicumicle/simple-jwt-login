<?php

namespace SimpleJWTLogin\Modules\Abilities\Webhooks;

use SimpleJWTLogin\Modules\Abilities\AuditableAbilityInterface;
use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;

class AddWebhookAbility extends AbstractWebhookAbility implements AuditableAbilityInterface
{
    protected function getSlug()
    {
        return 'add-webhook';
    }

    public function isReadOnly()
    {
        return false;
    }

    /**
     * A webhook sends plugin events (logins, registrations, ...) to any URL.
     *
     * @return string
     */
    public function getRequiredPermission()
    {
        return AiAbilitiesSettings::PERMISSION_ADD_WEBHOOKS;
    }

    public function getDefinition()
    {
        return [
            'label'         => __('Add a Simple JWT Login webhook', 'simple-jwt-login'),
            'description'   => __(
                'Adds an outgoing webhook that is called when the selected plugin events happen. Only HTTPS URLs are accepted. Webhooks only fire while webhooks are enabled (simple-jwt-login/update-settings, section "webhooks", key "enabled").',
                'simple-jwt-login'
            ),
            'input_schema'  => [
                'type'                 => 'object',
                'properties'           => [
                    'url'              => ['type' => 'string', 'format' => 'uri'],
                    'events'           => [
                        'type'     => 'array',
                        'minItems' => 1,
                        'items'    => ['type' => 'string', 'enum' => WebhooksSettings::$allowedEvents],
                    ],
                    'method'           => [
                        'type'    => 'string',
                        'enum'    => WebhooksSettings::$allowedMethods,
                        'default' => WebhooksSettings::DEFAULT_METHOD,
                    ],
                    'headers'          => [
                        'type'  => 'array',
                        'items' => [
                            'type'                 => 'object',
                            'properties'           => [
                                'key'   => ['type' => 'string'],
                                'value' => ['type' => 'string'],
                            ],
                            'required'             => ['key', 'value'],
                            'additionalProperties' => false,
                        ],
                    ],
                    'payload_template' => ['type' => 'string'],
                    'timeout'          => ['type' => 'integer', 'minimum' => 1, 'maximum' => WebhooksSettings::MAX_TIMEOUT],
                    'enabled'          => ['type' => 'boolean', 'default' => true],
                ],
                'required'             => ['url', 'events'],
                'additionalProperties' => false,
            ],
            'output_schema' => $this->webhookOutputSchema(),
            'annotations'   => $this->annotations(false, false, false),
        ];
    }

    public function auditDetails(array $input, array $result)
    {
        return [
            'url'   => $this->maskedUrl($this->inputValue($input, 'url', '')),
            'index' => isset($result['index']) ? (int) $result['index'] : null,
        ];
    }

    public function execute(array $input)
    {
        $input['enabled'] = (bool) $this->inputValue($input, 'enabled', true);

        $webhook = (new WebhooksSettings())
            ->withWordPressData($this->jwtSettings->getWordPressData())
            ->normalizeWebhookEntry($input);
        if ($webhook === null || empty($webhook['events'])) {
            throw new Exception(
                esc_html__('A webhook needs a URL and at least one valid event.', 'simple-jwt-login'),
                absint(ErrorCodes::ERR_ABILITY_INVALID_WEBHOOK)
            );
        }
        $webhook['payload_template'] = base64_encode($webhook['payload_template']);

        $settings   = $this->jwtSettings->getStoredSettings();
        $items      = $this->storedItems($settings);
        $items[]    = $webhook;
        $this->jwtSettings->saveSettingsArray($this->withItems($settings, $items));

        return $this->presentWebhook($webhook, count($items) - 1);
    }
}
