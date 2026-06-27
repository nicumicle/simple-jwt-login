<?php

namespace SimpleJWTLogin\Services;

use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

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
     * @var WordPressDataInterface
     */
    private $wordPressData;

    /**
     * Webhook jobs queued during the request, processed after the response is
     * flushed to the client.
     *
     * @var array
     */
    private $pendingJobs = [];

    /**
     * @var boolean
     */
    private $deferralRegistered = false;

    /**
     * @param SimpleJWTLoginSettings             $jwtSettings
     * @param WebhookLogRepositoryInterface|null $logRepository
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings, $logRepository = null)
    {
        $this->jwtSettings   = $jwtSettings;
        $this->logRepository = $logRepository;
        $this->wordPressData = $jwtSettings->getWordPressData();
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
     * Fire the webhooks for an event.
     *
     * On PHP-FPM the work is queued and run on the `shutdown` hook, after the
     * response has been flushed to the client, so the HTTP calls and log writes
     * never add to request latency. When the SAPI cannot flush the response
     * early, deferring gives no benefit, so the webhooks are processed inline.
     *
     * @param string $event
     * @param array  $payload
     */
    public function dispatch($event, array $payload = [])
    {
        if (!$this->jwtSettings->getWebhooksSettings()->isEnabled()) {
            return;
        }

        $webhooks = $this->jwtSettings->getWebhooksSettings()->getEnabledWebhooksForEvent($event);
        if (empty($webhooks)) {
            return;
        }

        if (!$this->wordPressData->canFinishRequest()) {
            $this->process($webhooks, $event, $payload);
            return;
        }

        $this->pendingJobs[] = [
            'webhooks' => $webhooks,
            'event'    => $event,
            'payload'  => $payload,
        ];

        if ($this->deferralRegistered) {
            return;
        }
        $this->deferralRegistered = true;
        $this->wordPressData->addAction('shutdown', array($this, 'runPendingJobs'));
    }

    /**
     * Flush the response to the client, then process every queued webhook job.
     * Runs on the WordPress `shutdown` hook.
     */
    public function runPendingJobs()
    {
        $this->wordPressData->finishRequest();

        $jobs              = $this->pendingJobs;
        $this->pendingJobs = [];
        foreach ($jobs as $job) {
            $this->process($job['webhooks'], $job['event'], $job['payload']);
        }
    }

    /**
     * @param array  $webhooks
     * @param string $event
     * @param array  $payload
     */
    protected function process(array $webhooks, $event, array $payload)
    {
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

            $response = wp_safe_remote_request($url, [
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
