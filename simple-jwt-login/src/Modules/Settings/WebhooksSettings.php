<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

class WebhooksSettings extends BaseSettings implements SettingsInterface
{
    const EVENT_LOGIN                   = 'login';
    const EVENT_REGISTER                = 'register';
    const EVENT_AUTH                    = 'auth';
    const EVENT_DELETE_USER             = 'delete_user';
    const EVENT_RESET_PASSWORD_REQUEST  = 'reset_password_request';
    const EVENT_RESET_PASSWORD          = 'reset_password';

    /** @var string[] */
    public static $allowedEvents = array(
        self::EVENT_LOGIN,
        self::EVENT_REGISTER,
        self::EVENT_AUTH,
        self::EVENT_DELETE_USER,
        self::EVENT_RESET_PASSWORD_REQUEST,
        self::EVENT_RESET_PASSWORD,
    );

    const METHOD_GET   = 'GET';
    const METHOD_POST  = 'POST';
    const METHOD_PUT   = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    /** @var string[] */
    public static $allowedMethods = array(
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_PATCH,
        self::METHOD_DELETE,
    );

    const DEFAULT_METHOD = self::METHOD_POST;

    const SETTING_RETENTION_DAYS = 'webhook_logs_retention_days';

    const DEFAULT_RETENTION_DAYS = 90;

    protected function getSectionKey()
    {
        return 'webhooks';
    }

    public function initSettingsFromPost()
    {
        $this->settings['enabled'] = !empty($this->post['webhooks_enabled']);

        $this->settings['logs'] = [
            'enabled'   => !empty($this->post['webhook_logs_enabled']),
            'retention' => isset($this->post['webhook_logs_retention_days'])
                ? max(1, (int) $this->post['webhook_logs_retention_days'])
                : self::DEFAULT_RETENTION_DAYS,
        ];

        if (!isset($this->post['webhooks_json'])) {
            if (!isset($this->settings['items'])) {
                $this->settings['items'] = [];
            }
            return;
        }

        $raw = $this->post['webhooks_json'];
        if (is_string($raw)) {
            $decoded = json_decode(stripslashes($raw), true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            $this->settings['items'] = [];
            return;
        }

        $webhooks = [];
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $url = $this->wordPressData->sanitizeTextField(
                isset($entry['url']) ? (string)$entry['url'] : ''
            );

            if (empty($url)) {
                continue;
            }

            $enabled = !empty($entry['enabled']);

            $events = [];
            if (isset($entry['events']) && is_array($entry['events'])) {
                foreach ($entry['events'] as $event) {
                    $sanitized = $this->wordPressData->sanitizeTextField((string)$event);
                    if (in_array($sanitized, self::$allowedEvents, true)) {
                        $events[] = $sanitized;
                    }
                }
            }

            $rawMethod = $this->wordPressData->sanitizeTextField(
                isset($entry['method']) ? strtoupper((string)$entry['method']) : ''
            );
            $method = in_array($rawMethod, self::$allowedMethods, true)
                ? $rawMethod
                : self::DEFAULT_METHOD;

            $headers = [];
            if (isset($entry['headers']) && is_array($entry['headers'])) {
                foreach ($entry['headers'] as $header) {
                    if (!is_array($header)) {
                        continue;
                    }
                    $key   = $this->wordPressData->sanitizeTextField(isset($header['key']) ? (string)$header['key'] : '');
                    $value = $this->wordPressData->sanitizeTextField(isset($header['value']) ? (string)$header['value'] : '');
                    if (empty($key)) {
                        continue;
                    }
                    $headers[] = ['key' => $key, 'value' => $value];
                }
            }

            $payloadTemplate = $this->wordPressData->sanitizeTextField(
                isset($entry['payload_template']) ? (string)$entry['payload_template'] : ''
            );

            $webhooks[] = [
                'url'              => $url,
                'enabled'          => $enabled,
                'method'           => $method,
                'events'           => $events,
                'headers'          => $headers,
                'payload_template' => $payloadTemplate,
            ];
        }

        $this->settings['items'] = $webhooks;
    }

    /**
     * @throws Exception
     */
    public function validateSettings()
    {
        if (isset($this->post['webhook_logs_retention_days'])) {
            $retention = (int) $this->post['webhook_logs_retention_days'];
            if ($retention < 1) {
                throw new Exception(
                    esc_html__('Webhook log retention days must be at least 1.', 'simple-jwt-login'),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_WEBHOOKS,
                        SettingsErrors::ERR_WEBHOOKS_INVALID_URL + 1
                    ))
                );
            }
        }

        foreach ($this->getWebhooks() as $i => $webhook) {
            if (empty($webhook['enabled'])) {
                continue;
            }
            $url = isset($webhook['url']) ? $webhook['url'] : '';
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                throw new Exception(
                    esc_html(
                        sprintf(
                            /* translators: %d: webhook index number */
                            __('Webhook #%d: invalid URL.', 'simple-jwt-login'),
                            $i + 1
                        )
                    ),
                    absint($this->settingsErrors->generateCode(
                        SettingsErrors::PREFIX_WEBHOOKS,
                        SettingsErrors::ERR_WEBHOOKS_INVALID_URL
                    ))
                );
            }
        }
    }

    /**
     * @return array
     */
    public function getWebhooks()
    {
        return isset($this->settings['items']) && is_array($this->settings['items'])
            ? $this->settings['items']
            : [];
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return !empty($this->settings['enabled']);
    }

    /**
     * @return bool
     */
    public function isWebhookLogsEnabled()
    {
        return !isset($this->settings['logs']['enabled']) || !empty($this->settings['logs']['enabled']);
    }

    /**
     * @return int
     */
    public function getRetentionDays()
    {
        return isset($this->settings['logs']['retention'])
            ? (int) $this->settings['logs']['retention']
            : self::DEFAULT_RETENTION_DAYS;
    }

    /**
     * @param string $event
     * @return array
     */
    public function getEnabledWebhooksForEvent($event)
    {
        $result = [];
        foreach ($this->getWebhooks() as $webhook) {
            if (empty($webhook['enabled'])) {
                continue;
            }
            $events = isset($webhook['events']) && is_array($webhook['events'])
                ? $webhook['events']
                : [];
            if (in_array($event, $events, true)) {
                $result[] = $webhook;
            }
        }
        return $result;
    }
}
