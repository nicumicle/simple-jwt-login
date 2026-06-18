<?php

use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * Helper function for drawing protect endpoint line
 * @param string $type
 * @param ?array<string,mixed> $endpoint
 * @return void
 * @throws Exception
 */
function simple_jwt_login_draw_endpoin_row($type, $endpoint)
{
    $requestMethodsOpts = [
        ProtectEndpointSettings::REQUEST_METHOD_GET    => __('GET', 'simple-jwt-login'),
        ProtectEndpointSettings::REQUEST_METHOD_POST   => __('POST', 'simple-jwt-login'),
        ProtectEndpointSettings::REQUEST_METHOD_PUT    => __('PUT', 'simple-jwt-login'),
        ProtectEndpointSettings::REQUEST_METHOD_PATCH  => __('PATCH', 'simple-jwt-login'),
        ProtectEndpointSettings::REQUEST_METHOD_DELETE => __('DELETE', 'simple-jwt-login'),
    ];

    $matchesOpts = [
        ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH => __('Starts with', 'simple-jwt-login'),
        ProtectEndpointSettings::ENDPOINT_MATCH_EXACT      => __('Exact match', 'simple-jwt-login'),
    ];
    ?>
    <div class="endpoint_row sjl-endpoint-row">
        <select class="sjl-endpoint-method-select"
                name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP . '[' . $type . '_method][]'); ?>">
            <option value="<?php echo esc_attr(ProtectEndpointSettings::REQUEST_METHOD_ALL); ?>"
                <?php echo (!empty($endpoint) && $endpoint['method'] == ProtectEndpointSettings::REQUEST_METHOD_ALL ? 'selected' : ''); ?>
            ><?php echo esc_html__('ALL', 'simple-jwt-login'); ?></option>
            <optgroup label="<?php echo esc_attr__('HTTP Methods', 'simple-jwt-login'); ?>">
                <?php foreach ($requestMethodsOpts as $method => $translation) { ?>
                    <option value="<?php echo esc_attr($method); ?>"
                        <?php echo (!empty($endpoint) && $endpoint['method'] == $method ? 'selected' : ''); ?>
                    ><?php echo esc_html($translation); ?></option>
                <?php } ?>
            </optgroup>
        </select>
        <select class="sjl-endpoint-match-select"
                name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP . '[' . $type . '_match][]'); ?>">
            <?php foreach ($matchesOpts as $match => $translation) { ?>
                <option value="<?php echo esc_attr($match); ?>"
                    <?php echo (!empty($endpoint) && $endpoint['match'] === $match ? 'selected' : ''); ?>
                ><?php echo esc_html($translation); ?></option>
            <?php } ?>
        </select>
        <input type="text"
               class="form-control sjl-endpoint-url-input"
               name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP . '[' . $type . '][]'); ?>"
               value="<?php echo !empty($endpoint) ? esc_attr($endpoint['url']) : ''; ?>"
               placeholder="<?php echo esc_attr__('/wp-json/namespace/endpoint', 'simple-jwt-login'); ?>"
        />
        <button type="button"
                class="sjl-endpoint-remove"
                onclick="sjlRemoveEndpointRow(jQuery(this));"
                title="<?php echo esc_attr__('Remove', 'simple-jwt-login'); ?>">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
    <?php
}

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Protect Endpoints', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('When enabled, REST endpoints will require a valid JWT to be accessed. Requests without a JWT will receive an error instead of content.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio"
                       id="protect_endpoints_enabled_no"
                       name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP); ?>[enabled]"
                       value="0"
                    <?php echo !$jwtSettings->getProtectEndpointsSettings()->isEnabled()
                        ? esc_html('checked')
                        : esc_html('');
                    ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio"
                       id="protect_endpoints_enabled_yes"
                       name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP); ?>[enabled]"
                       value="1"
                    <?php echo $jwtSettings->getProtectEndpointsSettings()->isEnabled()
                        ? esc_html('checked')
                        : esc_html('');
                    ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-filter"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Protection Scope', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Choose whether to apply JWT protection to all REST endpoints or only to specific ones.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <label class="sjl-gen-field-label" for="protection_type">
            <?php echo esc_html__('Apply protection to:', 'simple-jwt-login'); ?>
        </label>
        <select id="protection_type"
                name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP); ?>[action]"
                class="form-control sjl-gen-select"
        >
            <option value="<?php echo esc_attr(ProtectEndpointSettings::ALL_ENDPOINTS); ?>"
                <?php echo $jwtSettings->getProtectEndpointsSettings()->getAction() === ProtectEndpointSettings::ALL_ENDPOINTS
                    ? esc_html('selected')
                    : esc_html('');
                ?>
            >
                <?php echo esc_html__('Apply on All REST Endpoints', 'simple-jwt-login'); ?>
            </option>
            <option value="<?php echo esc_attr(ProtectEndpointSettings::SPECIFIC_ENDPOINTS); ?>"
                <?php echo $jwtSettings->getProtectEndpointsSettings()->getAction() === ProtectEndpointSettings::SPECIFIC_ENDPOINTS
                    ? esc_html('selected')
                    : esc_html('');
                ?>
            >
                <?php echo esc_html__('Apply only on Specific REST endpoints', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
</div>

<div class="sjl-gen-card sjl-gen-card--whitelist" id="protected_endpoints_whitelisted">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
        <div style="flex: 1;">
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Whitelisted Endpoints', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('These endpoints will skip the JWT check and remain publicly accessible.', 'simple-jwt-login'); ?>
            </p>
        </div>
        <span class="sjl-endpoint-count" id="whitelist_endpoint_count">
            <?php echo count($jwtSettings->getProtectEndpointsSettings()->getWhitelistedDomains()); ?>
        </span>
    </div>
    <div class="sjl-gen-card-body">
        <div id="whitelisted-domains">
            <?php foreach ($jwtSettings->getProtectEndpointsSettings()->getWhitelistedDomains() as $endpoint) {
                simple_jwt_login_draw_endpoin_row('whitelist', $endpoint);
            } ?>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="add_whitelist_endpoint" style="margin-top: 10px;">
            <?php echo esc_html__('+ Add Endpoint', 'simple-jwt-login'); ?>
        </button>
    </div>
</div>

<div class="sjl-gen-card sjl-gen-card--protected" id="protected_endpoints_protected">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-lock" style="color: #d63638;"></span>
        <div style="flex: 1;">
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Protected Endpoints', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('A valid JWT will be required to access these endpoints.', 'simple-jwt-login'); ?>
            </p>
        </div>
        <span class="sjl-endpoint-count" id="protected_endpoint_count">
            <?php echo count($jwtSettings->getProtectEndpointsSettings()->getProtectedEndpoints()); ?>
        </span>
    </div>
    <div class="sjl-gen-card-body">
        <div id="protected-domains">
            <?php foreach ($jwtSettings->getProtectEndpointsSettings()->getProtectedEndpoints() as $endpoint) {
                simple_jwt_login_draw_endpoin_row('protect', $endpoint);
            } ?>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="add_protect_endpoint" style="margin-top: 10px;">
            <?php echo esc_html__('+ Add Endpoint', 'simple-jwt-login'); ?>
        </button>
    </div>
</div>

<?php // Empty endpoint lines used by JS for inserting new rows ?>
<div id="endpoint_whitelist_line" style="display: none;">
    <?php simple_jwt_login_draw_endpoin_row('whitelist', null); ?>
</div>

<div id="endpoint_protect_line" style="display: none;">
    <?php simple_jwt_login_draw_endpoin_row('protect', null); ?>
</div>
