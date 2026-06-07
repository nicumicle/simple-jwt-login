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

        <div style="margin-bottom: 12px; position: relative;">
            <span class="dashicons dashicons-search" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #888; font-size: 16px; line-height: 1;"></span>
            <input
                type="text"
                id="sjl-event-search"
                placeholder="<?php echo esc_attr(__('Search events...', 'simple-jwt-login')); ?>"
                style="width: 100%; padding: 6px 8px 6px 30px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; box-sizing: border-box;"
            />
        </div>
        <p id="sjl-event-no-results" style="display: none; color: #888; font-size: 13px;">
            <?php echo esc_html(__('No events match your search.', 'simple-jwt-login')); ?>
        </p>

        <div class="row" id="sjl-events-list">
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
                            <?php echo esc_html(isset($eventLabels[$event]) ? $eventLabels[$event] : $event); ?>
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
    var btn       = document.getElementById('sjl-toggle-all-events');
    var searchInput = document.getElementById('sjl-event-search');
    var noResults   = document.getElementById('sjl-event-no-results');
    var eventRows   = document.querySelectorAll('#sjl-events-list .col-md-6');

    if (!btn) { return; }

    var checkboxes = document.querySelectorAll('input[name="audit_log[enabled_events][]"]');

    function syncButtonLabel() {
        var visibleCheckboxes = Array.prototype.filter.call(
            checkboxes,
            function (cb) { return cb.closest('.col-md-6').style.display !== 'none'; }
        );
        var allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(function (cb) { return cb.checked; });
        btn.dataset.allChecked = allChecked ? 'true' : 'false';
        btn.textContent = allChecked
            ? '<?php echo esc_js(__('Disable All', 'simple-jwt-login')); ?>'
            : '<?php echo esc_js(__('Enable All', 'simple-jwt-login')); ?>';
    }

    function filterEvents() {
        var term    = searchInput.value.toLowerCase().trim();
        var visible = 0;

        eventRows.forEach(function (row) {
            var text = row.textContent.toLowerCase();
            if (!term || text.indexOf(term) !== -1) {
                row.style.display = '';
                visible++;
            } else {
                row.style.display = 'none';
            }
        });

        noResults.style.display = visible === 0 ? '' : 'none';
        syncButtonLabel();
    }

    checkboxes.forEach(function (cb) { cb.addEventListener('change', syncButtonLabel); });
    searchInput.addEventListener('input', filterEvents);
    syncButtonLabel();

    btn.addEventListener('click', function () {
        var shouldCheck = btn.dataset.allChecked !== 'true';
        Array.prototype.filter.call(
            checkboxes,
            function (cb) { return cb.closest('.col-md-6').style.display !== 'none'; }
        ).forEach(function (cb) { cb.checked = shouldCheck; });
        syncButtonLabel();
    });
}());
</script>
