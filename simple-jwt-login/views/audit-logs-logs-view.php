<?php

use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;

if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SimpleJWTLoginSettings $jwtSettings
 */

// Handle "Clear All Logs" action
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
if (isset($_GET['sjl_audit_action']) && $_GET['sjl_audit_action'] === 'clear') {
    if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sjl_audit_clear_logs')) {
        global $wpdb;
        $repo = new AuditLogRepository($wpdb);
        $repo->deleteAll();
        $redirectUrl = remove_query_arg(['sjl_audit_action', '_wpnonce']);
        wp_safe_redirect($redirectUrl);
        exit;
    }
}

// Pagination & filters
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$logsPage    = isset($_GET['logs_page']) ? max(1, (int) $_GET['logs_page']) : 1;
$perPage     = 20;
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filterEvent  = isset($_GET['filter_event'])  ? sanitize_text_field(wp_unslash($_GET['filter_event']))  : '';
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filterStatus = isset($_GET['filter_status']) ? sanitize_text_field(wp_unslash($_GET['filter_status'])) : '';
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filterFrom   = isset($_GET['filter_from'])   ? sanitize_text_field(wp_unslash($_GET['filter_from']))   : '';
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filterTo     = isset($_GET['filter_to'])     ? sanitize_text_field(wp_unslash($_GET['filter_to']))     : '';
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filterUser   = isset($_GET['filter_user'])   ? sanitize_text_field(wp_unslash($_GET['filter_user']))   : '';

global $wpdb;
$auditRepo = new AuditLogRepository($wpdb);

$filters = array_filter([
    'event_type' => $filterEvent,
    'status'     => $filterStatus,
    'date_from'  => $filterFrom,
    'date_to'    => $filterTo,
    'user_email' => $filterUser,
]);

$result     = $auditRepo->findPaginated($filters, $logsPage, $perPage);
$logItems   = $result['items'];
$totalLogs  = $result['total'];
$totalPages = $totalLogs > 0 ? (int) ceil($totalLogs / $perPage) : 1;

$allEvents   = AuditEvents::all();
$eventLabels = AuditEvents::labels();

$baseUrl = add_query_arg([
    'active_tab'    => SettingsErrors::PREFIX_AUDIT_LOG_LOGS,
    'filter_event'  => $filterEvent,
    'filter_status' => $filterStatus,
    'filter_from'   => $filterFrom,
    'filter_to'     => $filterTo,
    'filter_user'   => $filterUser,
]);
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="dashicons dashicons-backup"></span>
            <div>
                <h3 class="sjl-gen-card-title"><?php echo esc_html(__('Activity Log', 'simple-jwt-login')); ?></h3>
                <p class="sjl-gen-card-desc">
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: %d: number of total log entries */
                            __('Showing %d total entries.', 'simple-jwt-login'),
                            $totalLogs
                        )
                    );
                    ?>
                </p>
            </div>
        </div>
        <a
            href="<?php echo esc_url(wp_nonce_url(add_query_arg(['sjl_audit_action' => 'clear']), 'sjl_audit_clear_logs')); ?>"
            class="btn btn-sm btn-outline-danger"
            style="white-space: nowrap; align-self: center;"
            onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete all audit logs?', 'simple-jwt-login')); ?>');"
        >
            <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px; vertical-align: middle;"></span>
            <?php echo esc_html(__('Clear All Logs', 'simple-jwt-login')); ?>
        </a>
    </div>
    <div class="sjl-gen-card-body">

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="row" id="sjl-audit-filters">
                    <div class="col-md-3">
                        <select id="sjl-filter-event" class="form-control">
                            <option value=""><?php echo esc_html(__('All Events', 'simple-jwt-login')); ?></option>
                            <?php foreach ($allEvents as $event) : ?>
                                <option value="<?php echo esc_attr($event); ?>" <?php echo $filterEvent === $event ? 'selected' : ''; ?>>
                                    <?php echo esc_html(isset($eventLabels[$event]) ? $eventLabels[$event] : $event); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="sjl-filter-status" class="form-control">
                            <option value=""><?php echo esc_html(__('All Statuses', 'simple-jwt-login')); ?></option>
                            <option value="success" <?php echo $filterStatus === 'success' ? 'selected' : ''; ?>><?php echo esc_html(__('Success', 'simple-jwt-login')); ?></option>
                            <option value="failure" <?php echo $filterStatus === 'failure' ? 'selected' : ''; ?>><?php echo esc_html(__('Failure', 'simple-jwt-login')); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" id="sjl-filter-user" value="<?php echo esc_attr($filterUser); ?>" class="form-control" placeholder="<?php echo esc_attr(__('User (email)', 'simple-jwt-login')); ?>"/>
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="sjl-filter-from" value="<?php echo esc_attr($filterFrom); ?>" class="form-control"/>
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="sjl-filter-to" value="<?php echo esc_attr($filterTo); ?>" class="form-control"/>
                    </div>
                    <div class="col-md-1">
                        <button type="button" id="sjl-audit-filter-btn" class="btn btn-secondary btn-block"><?php echo esc_html(__('Filter', 'simple-jwt-login')); ?></button>
                    </div>
                </div>
                <script>
                (function () {
                    document.getElementById('sjl-audit-filter-btn').addEventListener('click', function () {
                        var params = new URLSearchParams();
                        <?php
                        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        if (isset($_GET['page'])) {
                            echo 'params.set("page", ' . wp_json_encode(sanitize_text_field(wp_unslash($_GET['page']))) . ');';
                        }
                        ?>
                        params.set('active_tab', '<?php echo esc_js((string) SettingsErrors::PREFIX_AUDIT_LOG_LOGS); ?>');
                        var event  = document.getElementById('sjl-filter-event').value;
                        var status = document.getElementById('sjl-filter-status').value;
                        var user   = document.getElementById('sjl-filter-user').value;
                        var from   = document.getElementById('sjl-filter-from').value;
                        var to     = document.getElementById('sjl-filter-to').value;
                        if (event)  { params.set('filter_event',  event); }
                        if (status) { params.set('filter_status', status); }
                        if (user)   { params.set('filter_user',   user); }
                        if (from)   { params.set('filter_from',   from); }
                        if (to)     { params.set('filter_to',     to); }
                        window.location.href = window.location.pathname + '?' + params.toString();
                    });
                }());
                </script>
            </div>
        </div>

        <!-- Log Table -->
        <div class="table-responsive">
            <table id="sjl-audit-logs-table" class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th><?php echo esc_html(__('Date / Time', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('Event', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('User', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('API Key', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('IP Address', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('Status', 'simple-jwt-login')); ?></th>
                        <th><?php echo esc_html(__('Message', 'simple-jwt-login')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logItems)) : ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                <?php echo esc_html(__('No log entries found.', 'simple-jwt-login')); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($logItems as $log) : ?>
                            <tr>
                                <td><small><?php echo esc_html($log->created_at); ?></small></td>
                                <td>
                                    <code><?php echo esc_html($log->event_type); ?></code>
                                </td>
                                <td>
                                    <?php if (!empty($log->user_id)) : ?>
                                        <small><?php echo esc_html(isset($log->user_email) ? $log->user_email : ''); ?></small>
                                        <br/><small class="text-muted">#<?php echo esc_html($log->user_id); ?></small>
                                    <?php else : ?>
                                        <small class="text-muted"><?php echo esc_html(isset($log->user_email) ? $log->user_email : '-'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log->api_key_id)) : ?>
                                        <small class="text-muted">#<?php echo esc_html($log->api_key_id); ?></small>
                                    <?php else : ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo esc_html(isset($log->ip_address) ? $log->ip_address : '-'); ?></small></td>
                                <td>
                                    <?php if ($log->status === 'success') : ?>
                                        <span class="badge badge-success"><?php echo esc_html(__('Success', 'simple-jwt-login')); ?></span>
                                    <?php else : ?>
                                        <span class="badge badge-danger"><?php echo esc_html(__('Failure', 'simple-jwt-login')); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo esc_html(isset($log->message) ? $log->message : ''); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1) : ?>
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center">
                            <li class="page-item <?php echo $logsPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo esc_url(add_query_arg('logs_page', (string) max(1, $logsPage - 1), $baseUrl)); ?>">
                                    &laquo; <?php echo esc_html(__('Prev', 'simple-jwt-login')); ?>
                                </a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++) : ?>
                                <li class="page-item <?php echo $p === $logsPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('logs_page', (string) $p, $baseUrl)); ?>">
                                        <?php echo esc_html($p); ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $logsPage >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo esc_url(add_query_arg('logs_page', (string) min($totalPages, $logsPage + 1), $baseUrl)); ?>">
                                    <?php echo esc_html(__('Next', 'simple-jwt-login')); ?> &raquo;
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
                                    $logsPage,
                                    $totalPages,
                                    $totalLogs
                                )
                            );
                            ?>
                        </small>
                    </p>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
