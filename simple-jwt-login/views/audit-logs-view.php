<?php

use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SimpleJWTLoginSettings $jwtSettings
 */

$auditLogSettings = $jwtSettings->getAuditLogSettings();
$allEvents        = AuditEvents::all();
$eventLabels      = AuditEvents::labels();
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-list-view"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html(__('Audit Logging', 'simple-jwt-login')); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html(__('Record authentication events to the database for monitoring and compliance.', 'simple-jwt-login')); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <input type="hidden" name="audit_log[enabled]" value="0">
            <label class="sjl-toggle-switch" title="<?php echo esc_attr(__('Enable / Disable audit logging', 'simple-jwt-login')); ?>" style="margin: 0;">
                <input type="checkbox" name="audit_log[enabled]" value="1" <?php echo $auditLogSettings->isEnabled() ? 'checked' : ''; ?>>
                <span class="sjl-toggle-slider"></span>
            </label>
            <span style="font-size: 12px; color: #555; white-space: nowrap;">
                <?php echo esc_html(__('Enable Audit Logging', 'simple-jwt-login')); ?>
            </span>
        </div>

        <hr/>

        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0"><?php echo esc_html(__('Events to Log', 'simple-jwt-login')); ?></h5>
            <button
                type="button"
                id="sjl-toggle-all-events"
                class="btn btn-sm btn-outline-secondary"
                data-all-checked="false"
            >
                <?php echo esc_html(__('Enable All', 'simple-jwt-login')); ?>
            </button>
        </div>
        <div class="row">
            <?php foreach ($allEvents as $event) : ?>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            <input
                                type="checkbox"
                                name="audit_log[enabled_events][]"
                                value="<?php echo esc_attr($event); ?>"
                                <?php echo $auditLogSettings->isEventEnabled($event) ? 'checked' : ''; ?>
                            />
                            <?php echo esc_html($eventLabels[$event] ?? $event); ?>
                            <small class="text-muted">(<?php echo esc_html($event); ?>)</small>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr/>

        <div class="form-group row">
            <label class="col-md-3 col-form-label">
                <?php echo esc_html(__('Retention Period (days)', 'simple-jwt-login')); ?>
            </label>
            <div class="col-md-3">
                <input
                    type="number"
                    name="audit_log[retention_days]"
                    value="<?php echo esc_attr($auditLogSettings->getRetentionDays()); ?>"
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
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('sjl-toggle-all-events');
    if (!btn) { return; }

    var checkboxes = document.querySelectorAll('input[name="audit_log[enabled_events][]"]');

    function syncButtonLabel() {
        var allChecked = Array.prototype.every.call(checkboxes, function (cb) { return cb.checked; });
        btn.dataset.allChecked = allChecked ? 'true' : 'false';
        btn.textContent = allChecked
            ? '<?php echo esc_js(__('Disable All', 'simple-jwt-login')); ?>'
            : '<?php echo esc_js(__('Enable All', 'simple-jwt-login')); ?>';
    }

    checkboxes.forEach(function (cb) { cb.addEventListener('change', syncButtonLabel); });
    syncButtonLabel();

    btn.addEventListener('click', function () {
        var shouldCheck = btn.dataset.allChecked !== 'true';
        checkboxes.forEach(function (cb) { cb.checked = shouldCheck; });
        syncButtonLabel();
    });
}());
</script>
