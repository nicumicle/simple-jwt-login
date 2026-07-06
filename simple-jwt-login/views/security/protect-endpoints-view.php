<?php

use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * Helper function for drawing a protect endpoint rule line
 * @param ?array<string,mixed> $rule
 * @return void
 * @throws Exception
 */
function simple_jwt_login_draw_endpoin_row($rule)
{
    $group = ProtectEndpointSettings::PROPERTY_GROUP;

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

    $typeOpts = [
        ProtectEndpointSettings::RULE_TYPE_PUBLIC          => __('Public', 'simple-jwt-login'),
        ProtectEndpointSettings::RULE_TYPE_PROTECTED       => __('JWT required', 'simple-jwt-login'),
        ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES => __('JWT + Roles', 'simple-jwt-login'),
    ];

    $ruleMethod = !empty($rule['method']) ? $rule['method'] : ProtectEndpointSettings::REQUEST_METHOD_ALL;
    $ruleMatch  = !empty($rule['match']) ? $rule['match'] : ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH;
    $ruleType   = !empty($rule['type']) ? $rule['type'] : ProtectEndpointSettings::RULE_TYPE_PROTECTED;
    $ruleUrl    = !empty($rule['url']) ? $rule['url'] : '';
    $ruleRoles  = (!empty($rule['roles']) && is_array($rule['roles'])) ? implode(', ', $rule['roles']) : '';
    $rolesHidden = $ruleType === ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES ? '' : 'display:none;';
    ?>
    <div class="endpoint_row sjl-endpoint-row">
        <select class="sjl-endpoint-method-select"
                name="<?php echo esc_attr($group . '[rules_method][]'); ?>">
            <option value="<?php echo esc_attr(ProtectEndpointSettings::REQUEST_METHOD_ALL); ?>"
                <?php simple_jwt_login_selected_attr($ruleMethod === ProtectEndpointSettings::REQUEST_METHOD_ALL); ?>
            ><?php echo esc_html__('ALL', 'simple-jwt-login'); ?></option>
            <optgroup label="<?php echo esc_attr__('HTTP Methods', 'simple-jwt-login'); ?>">
                <?php foreach ($requestMethodsOpts as $method => $translation) { ?>
                    <option value="<?php echo esc_attr($method); ?>"
                        <?php simple_jwt_login_selected_attr($ruleMethod === $method); ?>
                    ><?php echo esc_html($translation); ?></option>
                <?php } ?>
            </optgroup>
        </select>
        <select class="sjl-endpoint-match-select"
                name="<?php echo esc_attr($group . '[rules_match][]'); ?>">
            <?php foreach ($matchesOpts as $match => $translation) { ?>
                <option value="<?php echo esc_attr($match); ?>"
                    <?php simple_jwt_login_selected_attr($ruleMatch === $match); ?>
                ><?php echo esc_html($translation); ?></option>
            <?php } ?>
        </select>
        <input type="text"
               class="form-control sjl-endpoint-url-input"
               name="<?php echo esc_attr($group . '[rules_url][]'); ?>"
               value="<?php echo esc_attr($ruleUrl); ?>"
               placeholder="<?php echo esc_attr__('/wp-json/namespace/endpoint', 'simple-jwt-login'); ?>"
        />
        <select class="sjl-endpoint-type-select"
                name="<?php echo esc_attr($group . '[rules_type][]'); ?>">
            <?php foreach ($typeOpts as $type => $translation) { ?>
                <option value="<?php echo esc_attr($type); ?>"
                    <?php simple_jwt_login_selected_attr($ruleType === $type); ?>
                ><?php echo esc_html($translation); ?></option>
            <?php } ?>
        </select>
        <input type="text"
               class="form-control sjl-endpoint-roles-input"
               name="<?php echo esc_attr($group . '[rules_roles][]'); ?>"
               value="<?php echo esc_attr($ruleRoles); ?>"
               placeholder="<?php echo esc_attr__('administrator, editor', 'simple-jwt-login'); ?>"
               title="<?php echo esc_attr__('Comma-separated roles required to access this endpoint.', 'simple-jwt-login'); ?>"
               style="<?php echo esc_attr($rolesHidden); ?>"
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
 * Echoes the `selected` attribute when the condition is truthy.
 * @param bool $isSelected
 * @return void
 */
function simple_jwt_login_selected_attr($isSelected)
{
    echo $isSelected ? esc_html('selected') : esc_html('');
}

/**
 * Echoes the `checked` attribute when the condition is truthy.
 * @param bool $isChecked
 * @return void
 */
function simple_jwt_login_checked_attr($isChecked)
{
    echo $isChecked ? esc_html('checked') : esc_html('');
}

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
$protectSettings = $jwtSettings->getProtectEndpointsSettings();
$defaultAction   = $protectSettings->getDefaultAction();
$rules           = $protectSettings->getRules();
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
                    <?php simple_jwt_login_checked_attr(!$protectSettings->isEnabled()); ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio"
                       id="protect_endpoints_enabled_yes"
                       name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP); ?>[enabled]"
                       value="1"
                    <?php simple_jwt_login_checked_attr($protectSettings->isEnabled()); ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>

<div class="sjl-gen-card" id="protected_endpoints_rules">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-lock"></span>
        <div style="flex: 1;">
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Endpoint Rules', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Rules are evaluated top to bottom; the first matching rule wins. Set each endpoint as Public, JWT required, or JWT + Roles.', 'simple-jwt-login'); ?>
            </p>
        </div>
        <span class="sjl-endpoint-count" id="rules_endpoint_count">
            <?php echo count($rules); ?>
        </span>
    </div>
    <div class="sjl-gen-card-body">
        <?php if (!empty($rules)) { ?>
        <div class="sjl-endpoint-header-row">
            <span class="sjl-endpoint-col-label"><?php echo esc_html__('Method', 'simple-jwt-login'); ?></span>
            <span class="sjl-endpoint-col-label"><?php echo esc_html__('Match', 'simple-jwt-login'); ?></span>
            <span class="sjl-endpoint-col-label sjl-endpoint-col-url"><?php echo esc_html__('Endpoint', 'simple-jwt-login'); ?></span>
            <span class="sjl-endpoint-col-label"><?php echo esc_html__('Type', 'simple-jwt-login'); ?></span>
            <span class="sjl-endpoint-col-label sjl-endpoint-col-roles"><?php echo esc_html__('Roles', 'simple-jwt-login'); ?></span>
            <span class="sjl-endpoint-col-label sjl-endpoint-col-del"></span>
        </div>
        <?php } ?>
        <div id="endpoint-rules">
            <?php foreach ($rules as $rule) {
                simple_jwt_login_draw_endpoin_row($rule);
            } ?>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="add_rule_endpoint" style="margin-top: 10px;">
            <?php echo esc_html__('+ Add Endpoint', 'simple-jwt-login'); ?>
        </button>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-filter"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Default Action', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('What happens to any endpoint that does not match a rule above.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <label class="sjl-gen-field-label" for="protection_default_action">
            <?php echo esc_html__('Default behavior for endpoints not listed above:', 'simple-jwt-login'); ?>
        </label>
        <select id="protection_default_action"
                name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP); ?>[default_action]"
                class="form-control sjl-gen-select"
        >
            <option value="<?php echo esc_attr(ProtectEndpointSettings::DEFAULT_ALLOW_ALL); ?>"
                <?php simple_jwt_login_selected_attr($defaultAction === ProtectEndpointSettings::DEFAULT_ALLOW_ALL); ?>
            >
                <?php echo esc_html__('Allow access - protect only the endpoints listed above', 'simple-jwt-login'); ?>
            </option>
            <option value="<?php echo esc_attr(ProtectEndpointSettings::DEFAULT_PROTECT_ALL); ?>"
                <?php simple_jwt_login_selected_attr($defaultAction === ProtectEndpointSettings::DEFAULT_PROTECT_ALL); ?>
            >
                <?php echo esc_html__('Require a valid JWT - keep only the endpoints listed above public', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
</div>

<?php // Empty endpoint line used by JS for inserting new rows ?>
<div id="endpoint_rule_line" style="display: none;">
    <?php simple_jwt_login_draw_endpoin_row(null); ?>
</div>
