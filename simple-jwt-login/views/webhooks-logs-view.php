<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SimpleJWTLoginSettings $jwtSettings
 */

$webhookLogsEnabled  = $jwtSettings->getWebhooksSettings()->isWebhookLogsEnabled();
$webhookLogRetention = $jwtSettings->getWebhooksSettings()->getRetentionDays();

// Handle "Clear All Webhook Logs" action
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
if (isset($_GET['sjl_webhook_log_action']) && $_GET['sjl_webhook_log_action'] === 'clear') {
    if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sjl_webhook_clear_logs')) {
        global $wpdb;
        (new WebhookLogRepository($wpdb))->deleteAll();
        $redirectUrl = remove_query_arg(['sjl_webhook_log_action', '_wpnonce']);
        wp_safe_redirect($redirectUrl);
        exit;
    }
}

// Pagination & filters
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wlPage       = isset($_GET['wl_page']) ? max(1, (int) $_GET['wl_page']) : 1;
$wlPerPage    = 20;
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wlFilterEvent  = isset($_GET['wl_filter_event'])  ? sanitize_text_field(wp_unslash($_GET['wl_filter_event']))  : '';
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wlFilterStatus = isset($_GET['wl_filter_status']) ? sanitize_text_field(wp_unslash($_GET['wl_filter_status'])) : '';
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wlFilterFrom   = isset($_GET['wl_filter_from'])   ? sanitize_text_field(wp_unslash($_GET['wl_filter_from']))   : '';
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$wlFilterTo     = isset($_GET['wl_filter_to'])     ? sanitize_text_field(wp_unslash($_GET['wl_filter_to']))     : '';

global $wpdb;
$webhookLogRepo = new WebhookLogRepository($wpdb);

$wlFilters = array_filter([
    'event'       => $wlFilterEvent,
    'status'      => $wlFilterStatus,
    'date_from'   => $wlFilterFrom,
    'date_to'     => $wlFilterTo,
]);

$wlResult     = $webhookLogRepo->findPaginated($wlFilters, $wlPage, $wlPerPage);
$wlItems      = $wlResult['items'];
$wlTotal      = $wlResult['total'];
$wlTotalPages = $wlTotal > 0 ? (int) ceil($wlTotal / $wlPerPage) : 1;

$wlBaseUrl = add_query_arg([
    'active_tab'       => SettingsErrors::PREFIX_WEBHOOK_LOGS,
    'wl_filter_event'  => $wlFilterEvent,
    'wl_filter_status' => $wlFilterStatus,
    'wl_filter_from'   => $wlFilterFrom,
    'wl_filter_to'     => $wlFilterTo,
]);
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="dashicons dashicons-backup"></span>
            <div>
                <h3 class="sjl-gen-card-title"><?php echo esc_html(__('Webhook Call Log', 'simple-jwt-login')); ?></h3>
                <p class="sjl-gen-card-desc">
                    <?php if ($webhookLogsEnabled) : ?>
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %d: number of total log entries */
                                __('Showing %d total entries.', 'simple-jwt-login'),
                                $wlTotal
                            )
                        );
                        ?>
                    <?php else : ?>
                        <?php echo esc_html(__('Logging is disabled. Enable it to record webhook calls.', 'simple-jwt-login')); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php if ($webhookLogsEnabled) : ?>
        <a
            href="<?php echo esc_url(wp_nonce_url(add_query_arg(['sjl_webhook_log_action' => 'clear']), 'sjl_webhook_clear_logs')); ?>"
            class="btn btn-sm btn-outline-danger"
            style="white-space: nowrap; align-self: center;"
            onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete all webhook logs?', 'simple-jwt-login')); ?>');"
        >
            <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px; vertical-align: middle;"></span>
            <?php echo esc_html(__('Clear All Logs', 'simple-jwt-login')); ?>
        </a>
        <?php endif; ?>
    </div>
    <div class="sjl-gen-card-body">

        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <input type="hidden" name="webhook_logs_enabled" value="0">
            <label class="sjl-toggle-switch" title="<?php echo esc_attr(__('Enable / Disable webhook logs', 'simple-jwt-login')); ?>" style="margin: 0;">
                <input type="checkbox" id="sjl-webhook-logs-toggle" name="webhook_logs_enabled" value="1" <?php echo $webhookLogsEnabled ? 'checked' : ''; ?>>
                <span class="sjl-toggle-slider"></span>
            </label>
            <span style="font-size: 12px; color: #555; white-space: nowrap;">
                <?php echo esc_html(__('Enable Webhook Logs', 'simple-jwt-login')); ?>
            </span>
        </div>

        <hr/>

        <div class="form-group row" style="margin-bottom: 16px;">
            <label class="col-md-3 col-form-label">
                <?php echo esc_html(__('Retention Period (days)', 'simple-jwt-login')); ?>
            </label>
            <div class="col-md-3">
                <input
                    type="number"
                    name="<?php echo esc_attr(WebhooksSettings::SETTING_RETENTION_DAYS); ?>"
                    value="<?php echo esc_attr($webhookLogRetention); ?>"
                    min="1"
                    class="form-control"
                />
            </div>
            <div class="col-md-6">
                <small class="form-text text-muted">
                    <?php echo esc_html(__('Log entries older than this many days are automatically deleted. Minimum: 1.', 'simple-jwt-login')); ?>
                </small>
            </div>
        </div>

        <?php if (!$webhookLogsEnabled) : ?>
        <div class="sjl-logs-disabled-notice" style="display: flex; align-items: center; gap: 10px; padding: 16px; background: #f9f9f9; border: 1px dashed #ccc; border-radius: 6px; color: #555;">
            <span class="dashicons dashicons-info" style="font-size: 20px; color: #aaa;"></span>
            <span><?php echo esc_html(__('Webhook call logging is currently disabled. Toggle "Enable Logs" above and save settings to start recording calls.', 'simple-jwt-login')); ?></span>
        </div>
        <?php else : ?>
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="row" id="sjl-wl-filters">
                    <div class="col-md-3">
                        <select id="sjl-wl-filter-event" class="form-control">
                            <option value=""><?php echo esc_html(__('All Events', 'simple-jwt-login')); ?></option>
                            <?php foreach (WebhooksSettings::ALLOWED_EVENTS as $ev) : ?>
                                <option value="<?php echo esc_attr($ev); ?>" <?php echo $wlFilterEvent === $ev ? 'selected' : ''; ?>>
                                    <?php echo esc_html(ucfirst($ev)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="sjl-wl-filter-status" class="form-control">
                            <option value=""><?php echo esc_html(__('All Statuses', 'simple-jwt-login')); ?></option>
                            <option value="success" <?php echo $wlFilterStatus === 'success' ? 'selected' : ''; ?>><?php echo esc_html(__('Success', 'simple-jwt-login')); ?></option>
                            <option value="failure" <?php echo $wlFilterStatus === 'failure' ? 'selected' : ''; ?>><?php echo esc_html(__('Failure', 'simple-jwt-login')); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="sjl-wl-filter-from" value="<?php echo esc_attr($wlFilterFrom); ?>" class="form-control"/>
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="sjl-wl-filter-to" value="<?php echo esc_attr($wlFilterTo); ?>" class="form-control"/>
                    </div>
                    <div class="col-md-1">
                        <button type="button" id="sjl-wl-filter-btn" class="btn btn-secondary btn-block"><?php echo esc_html(__('Filter', 'simple-jwt-login')); ?></button>
                    </div>
                </div>
                <script>
                (function () {
                    document.getElementById('sjl-wl-filter-btn').addEventListener('click', function () {
                        var params = new URLSearchParams();
                        <?php
                        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        if (isset($_GET['page'])) {
                            echo 'params.set("page", ' . wp_json_encode(sanitize_text_field(wp_unslash($_GET['page']))) . ');';
                        }
                        ?>
                        params.set('active_tab', '<?php echo esc_js((string) SettingsErrors::PREFIX_WEBHOOK_LOGS); ?>');
                        var event  = document.getElementById('sjl-wl-filter-event').value;
                        var status = document.getElementById('sjl-wl-filter-status').value;
                        var from   = document.getElementById('sjl-wl-filter-from').value;
                        var to     = document.getElementById('sjl-wl-filter-to').value;
                        if (event)  { params.set('wl_filter_event',  event); }
                        if (status) { params.set('wl_filter_status', status); }
                        if (from)   { params.set('wl_filter_from',   from); }
                        if (to)     { params.set('wl_filter_to',     to); }
                        window.location.href = window.location.pathname + '?' + params.toString();
                    });
                }());
                </script>
            </div>
        </div>

        <!-- Log Table -->
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th><?php echo esc_html(__('Date / Time', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('Webhook URL', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('Event', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('Method', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('Status', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('Error Body', 'simple-jwt-login')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($wlItems)) : ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                <?php echo esc_html(__('No webhook log entries found.', 'simple-jwt-login')); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($wlItems as $wlLog) :
                            $wlCode      = isset($wlLog->status_code) ? (int) $wlLog->status_code : null;
                            $wlIsSuccess = $wlCode !== null && $wlCode >= 200 && $wlCode < 300;
                            $wlHasBody   = !empty($wlLog->response_body);
                            ?>
                            <tr>
                                <td><small><?php echo esc_html($wlLog->created_at); ?></small></td>
                                <td>
                                    <small class="text-break" style="word-break:break-all; max-width:200px; display:block;">
                                        <?php echo esc_html($wlLog->webhook_url); ?>
                                    </small>
                                </td>
                                <td><code><?php echo esc_html($wlLog->event); ?></code></td>
                                <td>
                                    <span class="sjl-method-badge sjl-method-<?php echo esc_attr(strtolower($wlLog->method)); ?>">
                                        <?php echo esc_html($wlLog->method); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($wlCode === null) : ?>
                                        <span class="badge badge-danger"><?php echo esc_html(__('Error', 'simple-jwt-login')); ?></span>
                                    <?php elseif ($wlIsSuccess) : ?>
                                        <span class="badge badge-success"><?php echo esc_html($wlCode); ?></span>
                                    <?php else : ?>
                                        <span class="badge badge-danger"><?php echo esc_html($wlCode); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($wlHasBody) : ?>
                                        <details>
                                            <summary style="cursor:pointer; color:#0073aa;">
                                                <small><?php echo esc_html(__('View', 'simple-jwt-login')); ?></small>
                                            </summary>
                                            <pre style="font-size:11px; max-height:120px; overflow:auto; background:#f6f7f7; padding:6px; border-radius:4px; margin-top:4px;"><?php echo esc_html($wlLog->response_body); ?></pre>
                                        </details>
                                    <?php else : ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($wlTotalPages > 1) : ?>
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center">
                            <li class="page-item <?php echo $wlPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo esc_url(add_query_arg('wl_page', '1', $wlBaseUrl)); ?>">
                                    &laquo; <?php echo esc_html(__('First', 'simple-jwt-login')); ?>
                                </a>
                            </li>
                            <li class="page-item <?php echo $wlPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo esc_url(add_query_arg('wl_page', (string) max(1, $wlPage - 1), $wlBaseUrl)); ?>">
                                    &lsaquo; <?php echo esc_html(__('Prev', 'simple-jwt-login')); ?>
                                </a>
                            </li>
                            <?php
                            $wlWindow = 4;
                            $wlStart  = max(1, min($wlPage - (int) ($wlWindow / 2), $wlTotalPages - $wlWindow + 1));
                            $wlEnd    = min($wlTotalPages, $wlStart + $wlWindow - 1);
                            for ($p = $wlStart; $p <= $wlEnd; $p++) :
                                ?>
                                <li class="page-item <?php echo $p === $wlPage ? 'active' : ''; ?>">
                                    <?php if ($p === $wlPage) : ?>
                                        <span class="page-link"><?php echo (int) $p; ?></span>
                                    <?php else : ?>
                                        <a class="page-link" href="<?php echo esc_url(add_query_arg('wl_page', (string) $p, $wlBaseUrl)); ?>">
                                            <?php echo (int) $p; ?>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $wlPage >= $wlTotalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo esc_url(add_query_arg('wl_page', (string) min($wlTotalPages, $wlPage + 1), $wlBaseUrl)); ?>">
                                    <?php echo esc_html(__('Next', 'simple-jwt-login')); ?> &rsaquo;
                                </a>
                            </li>
                            <li class="page-item <?php echo $wlPage >= $wlTotalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo esc_url(add_query_arg('wl_page', (string) $wlTotalPages, $wlBaseUrl)); ?>">
                                    <?php echo esc_html(__('Last', 'simple-jwt-login')); ?> &raquo;
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <p class="text-muted">
                        <small>
                            <?php
                            echo esc_html(
                                sprintf(
                                    /* translators: %1$d current page, %2$d total pages, %3$d total entries */
                                    __('Page %1$d of %2$d (%3$d entries total)', 'simple-jwt-login'),
                                    $wlPage,
                                    $wlTotalPages,
                                    $wlTotal
                                )
                            );
                            ?>
                        </small>
                    </p>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; // end webhook_logs_enabled ?>

    </div>
</div>
