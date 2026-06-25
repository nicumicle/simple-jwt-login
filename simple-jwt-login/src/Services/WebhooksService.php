<?php

namespace SimpleJWTLogin\Services;

use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;

class WebhooksService
{
    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

    /**
     * @var WebhookLogRepositoryInterface|null
     */
    private $logRepository;

    /**
     * @param SimpleJWTLoginSettings             $jwtSettings
     * @param WebhookLogRepositoryInterface|null $logRepository
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings, $logRepository = null)
    {
        $this->jwtSettings   = $jwtSettings;
        $this->logRepository = $logRepository;
    }

    /**
     * @param array  $webhook
     * @param string $event
     * @param array  $payload
     * @return string
     */
    protected function buildBody($webhook, $event, array $payload)
    {
        $template = isset($webhook['payload_template']) ? trim((string)$webhook['payload_template']) : '';
        $vars = array_merge($payload, ['event' => $event]);

        if (empty($template)) {
            return wp_json_encode($vars);
        }

        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string)$value, $template);
        }
        return $template;
    }

    /**
     * @param string $event
     * @param array  $payload
     */
    public function dispatch($event, array $payload = [])
    {
        if (!$this->jwtSettings->getWebhooksSettings()->isEnabled()) {
            return;
        }

        $webhooks = $this->jwtSettings->getWebhooksSettings()->getEnabledWebhooksForEvent($event);

        foreach ($webhooks as $webhook) {
            $url = isset($webhook['url']) ? $webhook['url'] : '';
            if (empty($url)) {
                continue;
            }

            $method = isset($webhook['method']) && in_array($webhook['method'], WebhooksSettings::$allowedMethods, true)
                ? $webhook['method']
                : WebhooksSettings::DEFAULT_METHOD;

            $headers = [];
            if (isset($webhook['headers']) && is_array($webhook['headers'])) {
                foreach ($webhook['headers'] as $header) {
                    if (!empty($header['key'])) {
                        $headers[$header['key']] = isset($header['value']) ? $header['value'] : '';
                    }
                }
            }

            $blocking = $this->logRepository !== null;

            $response = wp_remote_request($url, [
                'method'   => $method,
                'body'     => $this->buildBody($webhook, $event, $payload),
                'headers'  => $headers,
                'timeout'  => 5,
                'blocking' => $blocking,
            ]);

            if ($blocking) {
                $this->logResponse($url, $event, $method, $response);
            }
        }
    }

    /**
     * @param string          $url
     * @param string          $event
     * @param string          $method
     * @param array|\WP_Error $response
     */
    private function logResponse($url, $event, $method, $response)
    {
        if ($this->logRepository === null) {
            return;
        }

        if (is_wp_error($response)) {
            $this->logRepository->insert($url, $event, $method, null, $response->get_error_message());
            return;
        }

        $statusCode   = (int) wp_remote_retrieve_response_code($response);
        $isError      = $statusCode < 200 || $statusCode >= 300;
        $responseBody = $isError ? (string) wp_remote_retrieve_body($response) : null;

        $this->logRepository->insert($url, $event, $method, $statusCode, $responseBody);
    }
}
