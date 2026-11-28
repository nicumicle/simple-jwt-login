<?php

namespace SimpleJWTLogin\Cli;

use Exception;
use SimpleJWTLogin\Modules\Abilities\Webhooks\GetWebhookLogsAbility;
use SimpleJWTLogin\Modules\Abilities\Webhooks\ListWebhooksAbility;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Services\WebhooksService;

/**
 * List, test and read the logs of webhooks.
 */
class WebhookCommand extends AbstractCommand
{
    /**
     * List the webhooks. Header values are hidden.
     *
     * ## OPTIONS
     *
     * [--event=<event>]
     * : Only webhooks of this event, for example login.
     *
     * [--fields=<fields>]
     * : Comma separated fields to show.
     *
     * [--format=<format>]
     * : table, csv, json, yaml or count. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp jwt webhook list
     *
     * @subcommand list
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function listWebhooks(array $args, array $assocArgs)
    {
        $this->rejectPositionalArgs($args);
        $input = isset($assocArgs['event']) ? array('event' => $assocArgs['event']) : array();
        $result = $this->runAbility(new ListWebhooksAbility($this->createSettings()), $input);
        if (!$result['enabled']) {
            \WP_CLI::warning(__('Webhooks are disabled.', 'simple-jwt-login'));
        }
        $this->outputItems($result['items'], $assocArgs, array('index', 'enabled', 'method', 'url', 'events'));
    }

    /**
     * Call a webhook now with a test payload and show the response.
     *
     * The webhook is called even when it or webhooks are disabled. The call is logged when webhook
     * logs are enabled.
     *
     * ## OPTIONS
     *
     * <index>
     * : The webhook index (from `wp jwt webhook list`).
     *
     * [--event=<event>]
     * : Event to send. Default: the first event of the webhook.
     *
     * ## EXAMPLES
     *
     *     wp jwt webhook test 0
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function test(array $args, array $assocArgs)
    {
        $jwtSettings = $this->createSettings();
        $webhooks = $jwtSettings->getWebhooksSettings()->getWebhooks();
        $index = isset($args[0]) && ctype_digit((string) $args[0]) ? (int) $args[0] : -1;
        if (!isset($webhooks[$index])) {
            \WP_CLI::error(__('Webhook not found. See wp jwt webhook list.', 'simple-jwt-login'));
            return;
        }
        $webhook = (array) $webhooks[$index];
        $events = isset($webhook['events']) ? (array) $webhook['events'] : array();
        $event = isset($assocArgs['event']) ? $assocArgs['event'] : (string) reset($events);

        try {
            $status = $this->audited('webhook test', function () use ($jwtSettings, $webhook, $event) {
                return $this->checkResponse($this->sendWebhook($jwtSettings, $webhook, $event));
            }, array('index' => $index, 'event' => $event));
        } catch (Exception $exception) {
            \WP_CLI::error($exception->getMessage());
            return;
        }
        /* translators: %d is the HTTP status code */
        \WP_CLI::success(sprintf(__('The webhook answered with HTTP %d.', 'simple-jwt-login'), $status));
    }

    /**
     * List webhook call results, newest first (when webhook logs are enabled).
     *
     * ## OPTIONS
     *
     * [--event=<event>]
     * : Only calls of this event.
     *
     * [--page=<page>]
     * : Page number. Default: 1.
     *
     * [--per-page=<count>]
     * : Entries per page. Default: 20.
     *
     * [--fields=<fields>]
     * : Comma separated fields to show.
     *
     * [--format=<format>]
     * : table, csv, json, yaml or count. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp jwt webhook logs --event=login
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function logs(array $args, array $assocArgs)
    {
        global $wpdb;

        $this->rejectPositionalArgs($args);
        $input = array();
        foreach (array('event', 'page', 'per-page') as $option) {
            if (isset($assocArgs[$option])) {
                $input[str_replace('-', '_', $option)] = $assocArgs[$option];
            }
        }
        $result = $this->runAbility(
            new GetWebhookLogsAbility($this->createSettings(), new WebhookLogRepository($wpdb)),
            $input
        );
        if (!$result['logs_enabled']) {
            \WP_CLI::warning(__('Webhook logs are disabled.', 'simple-jwt-login'));
        }
        $this->outputItems($result['items'], $assocArgs, array('id', 'created_at', 'event', 'method', 'url', 'status_code'));
    }

    /**
     * Print the answer; an error or a non-2xx status throws.
     *
     * @param array|\WP_Error|null $response
     * @return int The HTTP status.
     * @throws Exception
     */
    protected function checkResponse($response)
    {
        if (is_wp_error($response)) {
            throw new Exception(esc_html($response->get_error_message()));
        }
        if ($response === null) {
            throw new Exception(esc_html__('The webhook has no URL.', 'simple-jwt-login'));
        }
        $status = (int) wp_remote_retrieve_response_code($response);
        \WP_CLI::line((string) wp_remote_retrieve_body($response));
        if ($status < 200 || $status >= 300) {
            throw new Exception(sprintf(
                /* translators: %d is the HTTP status code */
                esc_html__('The webhook answered with HTTP %d.', 'simple-jwt-login'),
                absint($status)
            ));
        }

        return $status;
    }

    /**
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param array                  $webhook
     * @param string                 $event
     * @return array|\WP_Error|null
     */
    protected function sendWebhook(SimpleJWTLoginSettings $jwtSettings, array $webhook, $event)
    {
        global $wpdb;

        $logRepository = $jwtSettings->getWebhooksSettings()->isWebhookLogsEnabled() ? new WebhookLogRepository($wpdb) : null;
        $service = new WebhooksService($jwtSettings, $logRepository);

        return $service->sendNow($webhook, $event, array('user_id' => 0, 'user_email' => '', 'test' => true));
    }
}
