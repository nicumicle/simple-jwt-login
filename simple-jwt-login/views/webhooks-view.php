<?php

use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SimpleJWTLoginSettings $jwtSettings
 */

$webhooksEnabled = $jwtSettings->getWebhooksSettings()->isEnabled();
$webhooks        = $jwtSettings->getWebhooksSettings()->getWebhooks();
$allowedMethods  = WebhooksSettings::ALLOWED_METHODS;
$allowedEvents   = WebhooksSettings::ALLOWED_EVENTS;
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-rest-api"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Webhooks', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'Fire HTTP requests on login, register, or auth events. '
                    . 'Customize the method, headers, and payload with dynamic variables.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
            <input type="hidden" name="webhooks_enabled" value="0">
            <label class="sjl-toggle-switch" title="<?php echo esc_attr__('Enable / Disable Webhooks', 'simple-jwt-login'); ?>" style="margin: 0;">
                <input type="checkbox" name="webhooks_enabled" value="1" <?php echo $webhooksEnabled ? 'checked' : ''; ?>>
                <span class="sjl-toggle-slider"></span>
            </label>
            <span style="font-size: 12px; color: #555; white-space: nowrap;">
                <?php echo esc_html__('Enable Webhooks', 'simple-jwt-login'); ?>
            </span>
        </div>

        <hr/>

        <input type="hidden" id="webhooks_json" name="webhooks_json" value="">

        <div id="sjl-webhooks">
            <?php foreach ($webhooks as $webhook) :
                $url             = isset($webhook['url']) ? $webhook['url'] : '';
                $enabled         = !empty($webhook['enabled']);
                $method          = isset($webhook['method']) ? $webhook['method'] : WebhooksSettings::DEFAULT_METHOD;
                $events          = isset($webhook['events']) && is_array($webhook['events']) ? $webhook['events'] : [];
                $headers         = isset($webhook['headers']) && is_array($webhook['headers']) ? $webhook['headers'] : [];
                $payloadTemplate = isset($webhook['payload_template']) ? $webhook['payload_template'] : '';
                ?>

            <div class="sjl-webhook-item" data-open="false">
                <div class="sjl-webhook-item-header">
                    <button type="button" class="sjl-webhook-toggle" aria-expanded="false">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                    <span class="sjl-webhook-url-preview"><?php echo esc_html($url ?: __('New Webhook', 'simple-jwt-login')); ?></span>
                    <span class="sjl-method-badge sjl-method-<?php echo esc_attr(strtolower($method)); ?>"><?php echo esc_html($method); ?></span>
                    <span class="sjl-event-tags">
                        <?php foreach ($allowedEvents as $ev) : ?>
                        <span class="sjl-event-tag <?php echo in_array($ev, $events, true) ? 'active' : ''; ?>"
                              data-event="<?php echo esc_attr($ev); ?>">
                            <?php echo esc_html(ucfirst($ev)); ?>
                        </span>
                        <?php endforeach; ?>
                    </span>
                    <label class="sjl-toggle-switch" title="<?php echo esc_attr(__('Enable / Disable', 'simple-jwt-login')); ?>">
                        <input type="checkbox" class="sjl-webhook-enabled" <?php echo $enabled ? 'checked' : ''; ?>>
                        <span class="sjl-toggle-slider"></span>
                    </label>
                    <button type="button" class="sjl-webhook-remove"
                            title="<?php echo esc_attr(__('Remove webhook', 'simple-jwt-login')); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>

                <div class="sjl-webhook-item-body">

                    <div class="sjl-webhook-field-row">
                        <div class="sjl-webhook-field">
                            <label><?php echo esc_html__('Endpoint URL', 'simple-jwt-login'); ?></label>
                            <input type="text"
                                   class="form-control sjl-webhook-url"
                                   placeholder="https://example.com/webhook"
                                   value="<?php echo esc_attr($url); ?>">
                        </div>
                        <div class="sjl-webhook-field sjl-webhook-field-method">
                            <label><?php echo esc_html__('Method', 'simple-jwt-login'); ?></label>
                            <select class="form-control sjl-webhook-method">
                                <?php foreach ($allowedMethods as $m) : ?>
                                <option value="<?php echo esc_attr($m); ?>" <?php echo $method === $m ? 'selected' : ''; ?>>
                                    <?php echo esc_html($m); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="sjl-webhook-field-row">
                        <div class="sjl-webhook-field">
                            <label><?php echo esc_html__('Trigger on Events', 'simple-jwt-login'); ?></label>
                            <div class="sjl-webhook-events">
                                <?php foreach ($allowedEvents as $ev) :
                                    $checked = in_array($ev, $events, true);
                                    ?>
                                <label class="sjl-event-checkbox-label <?php echo $checked ? 'active' : ''; ?>">
                                    <input type="checkbox"
                                           class="sjl-webhook-event"
                                           value="<?php echo esc_attr($ev); ?>"
                                           <?php echo $checked ? 'checked' : ''; ?>>
                                    <span class="sjl-event-dot"></span>
                                    <?php echo esc_html(ucfirst($ev)); ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="sjl-webhook-subsection">
                        <div class="sjl-webhook-subsection-header">
                            <span class="dashicons dashicons-tag"></span>
                            <?php echo esc_html__('Custom Headers', 'simple-jwt-login'); ?>
                            <span class="sjl-header-count"><?php echo count($headers); ?></span>
                        </div>
                        <div class="sjl-webhook-headers-rows">
                            <?php foreach ($headers as $header) :
                                $hKey   = isset($header['key'])   ? $header['key']   : '';
                                $hValue = isset($header['value']) ? $header['value'] : '';
                                ?>
                            <div class="sjl-webhook-header-row">
                                <input type="text"
                                       class="form-control form-control-sm sjl-header-key"
                                       placeholder="<?php echo esc_attr(__('Header name', 'simple-jwt-login')); ?>"
                                       value="<?php echo esc_attr($hKey); ?>">
                                <span class="sjl-kv-sep">:</span>
                                <input type="text"
                                       class="form-control form-control-sm sjl-header-value"
                                       placeholder="<?php echo esc_attr(__('Value', 'simple-jwt-login')); ?>"
                                       value="<?php echo esc_attr($hValue); ?>">
                                <button type="button" class="sjl-header-remove"
                                        title="<?php echo esc_attr(__('Remove header', 'simple-jwt-login')); ?>">
                                    <span class="dashicons dashicons-minus"></span>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="sjl-btn-add sjl-add-header">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php echo esc_html__('Add Header', 'simple-jwt-login'); ?>
                        </button>
                    </div>

                    <div class="sjl-webhook-subsection">
                        <div class="sjl-webhook-subsection-header">
                            <span class="dashicons dashicons-editor-code"></span>
                            <?php echo esc_html__('Custom Payload', 'simple-jwt-login'); ?>
                        </div>
                        <div class="sjl-vars-bar">
                            <span class="sjl-vars-label"><?php echo esc_html__('Variables:', 'simple-jwt-login'); ?></span>
                            <span class="sjl-var-chip" data-var="{{user_id}}">{{user_id}}</span>
                            <span class="sjl-var-chip" data-var="{{user_email}}">{{user_email}}</span>
                            <span class="sjl-var-chip" data-var="{{event}}">{{event}}</span>
                        </div>
                        <textarea class="sjl-webhook-payload-template"
                                  rows="4"
                                  placeholder='{"user_id": "{{user_id}}", "email": "{{user_email}}", "event": "{{event}}"}'><?php echo esc_textarea($payloadTemplate); ?></textarea>
                        <p class="sjl-field-hint">
                            <?php echo esc_html__(
                                'Leave empty to send the default payload. Click a variable to insert it at the cursor.',
                                'simple-jwt-login'
                            ); ?>
                        </p>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-plus-alt2"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Add Webhook', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Register a new HTTP endpoint to receive event notifications.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <button type="button" id="sjl-add-webhook" class="btn btn-outline-secondary">
            <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
            <?php echo esc_html__('Add Webhook', 'simple-jwt-login'); ?>
        </button>
    </div>
</div>

<!-- Hidden template for JS row cloning -->
<div id="sjl-webhook-row-template" style="display:none;">
    <div class="sjl-webhook-item" data-open="true">
        <div class="sjl-webhook-item-header">
            <button type="button" class="sjl-webhook-toggle" aria-expanded="true">
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <span class="sjl-webhook-url-preview"><?php echo esc_html__('New Webhook', 'simple-jwt-login'); ?></span>
            <span class="sjl-method-badge sjl-method-post"><?php echo esc_html(WebhooksSettings::DEFAULT_METHOD); ?></span>
            <span class="sjl-event-tags">
                <?php foreach ($allowedEvents as $ev) : ?>
                <span class="sjl-event-tag" data-event="<?php echo esc_attr($ev); ?>"><?php echo esc_html(ucfirst($ev)); ?></span>
                <?php endforeach; ?>
            </span>
            <label class="sjl-toggle-switch">
                <input type="checkbox" class="sjl-webhook-enabled">
                <span class="sjl-toggle-slider"></span>
            </label>
            <button type="button" class="sjl-webhook-remove"
                    title="<?php echo esc_attr(__('Remove webhook', 'simple-jwt-login')); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <div class="sjl-webhook-item-body">
            <div class="sjl-webhook-field-row">
                <div class="sjl-webhook-field">
                    <label><?php echo esc_html__('Endpoint URL', 'simple-jwt-login'); ?></label>
                    <input type="text" class="form-control sjl-webhook-url" placeholder="https://example.com/webhook" value="">
                </div>
                <div class="sjl-webhook-field sjl-webhook-field-method">
                    <label><?php echo esc_html__('Method', 'simple-jwt-login'); ?></label>
                    <select class="form-control sjl-webhook-method">
                        <?php foreach ($allowedMethods as $m) : ?>
                        <option value="<?php echo esc_attr($m); ?>" <?php echo $m === WebhooksSettings::DEFAULT_METHOD ? 'selected' : ''; ?>>
                            <?php echo esc_html($m); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sjl-webhook-field-row">
                <div class="sjl-webhook-field">
                    <label><?php echo esc_html__('Trigger on Events', 'simple-jwt-login'); ?></label>
                    <div class="sjl-webhook-events">
                        <?php foreach ($allowedEvents as $ev) : ?>
                        <label class="sjl-event-checkbox-label">
                            <input type="checkbox" class="sjl-webhook-event" value="<?php echo esc_attr($ev); ?>">
                            <span class="sjl-event-dot"></span>
                            <?php echo esc_html(ucfirst($ev)); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="sjl-webhook-subsection">
                <div class="sjl-webhook-subsection-header">
                    <span class="dashicons dashicons-tag"></span>
                    <?php echo esc_html__('Custom Headers', 'simple-jwt-login'); ?>
                    <span class="sjl-header-count">0</span>
                </div>
                <div class="sjl-webhook-headers-rows"></div>
                <button type="button" class="sjl-btn-add sjl-add-header">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php echo esc_html__('Add Header', 'simple-jwt-login'); ?>
                </button>
            </div>
            <div class="sjl-webhook-subsection">
                <div class="sjl-webhook-subsection-header">
                    <span class="dashicons dashicons-editor-code"></span>
                    <?php echo esc_html__('Custom Payload', 'simple-jwt-login'); ?>
                </div>
                <div class="sjl-vars-bar">
                    <span class="sjl-vars-label"><?php echo esc_html__('Variables:', 'simple-jwt-login'); ?></span>
                    <span class="sjl-var-chip" data-var="{{user_id}}">{{user_id}}</span>
                    <span class="sjl-var-chip" data-var="{{user_email}}">{{user_email}}</span>
                    <span class="sjl-var-chip" data-var="{{event}}">{{event}}</span>
                </div>
                <textarea class="sjl-webhook-payload-template"
                          rows="4"
                          placeholder='{"user_id": "{{user_id}}", "email": "{{user_email}}", "event": "{{event}}"}'></textarea>
                <p class="sjl-field-hint">
                    <?php echo esc_html__(
                        'Leave empty to send the default payload. Click a variable to insert it at the cursor.',
                        'simple-jwt-login'
                    ); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Hidden template for a single header row -->
<div id="sjl-webhook-header-row-template" style="display:none;">
    <div class="sjl-webhook-header-row">
        <input type="text"
               class="form-control form-control-sm sjl-header-key"
               placeholder="<?php echo esc_attr(__('Header name', 'simple-jwt-login')); ?>"
               value="">
        <span class="sjl-kv-sep">:</span>
        <input type="text"
               class="form-control form-control-sm sjl-header-value"
               placeholder="<?php echo esc_attr(__('Value', 'simple-jwt-login')); ?>"
               value="">
        <button type="button" class="sjl-header-remove"
                title="<?php echo esc_attr(__('Remove header', 'simple-jwt-login')); ?>">
            <span class="dashicons dashicons-minus"></span>
        </button>
    </div>
</div>
