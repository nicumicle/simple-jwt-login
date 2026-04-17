<?php

use SimpleJWTLogin\Services\RouteService;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-trash"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Delete User', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Allow users to delete their accounts via the JWT API endpoint.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_delete" value="0"
                    <?php echo $jwtSettings->getDeleteUserSettings()->isDeleteAllowed() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_delete" value="1"
                    <?php echo $jwtSettings->getDeleteUserSettings()->isDeleteAllowed() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo __('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="method">DELETE</span>
                <span class="code">
                    <?php
                    $sampleUrlParams = [
                        $jwtSettings->getGeneralSettings()->getRequestKeyUrl() => __('JWT', 'simple-jwt-login'),
                    ];
                    if ($jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete()) {
                        $sampleUrlParams[$jwtSettings->getAuthCodesSettings()->getAuthCodeKey()] =
                            __('AUTH_KEY_VALUE', 'simple-jwt-login');
                    }
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::USER_ROUTE, $sampleUrlParams));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo __('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
            <p class="sjl-gen-card-desc" style="margin-top:6px;">
                <?php echo __('You can also pass the JWT in the Authorization header:', 'simple-jwt-login'); ?>
                <code class="sjl-gen-example-code">Authorization: Bearer <strong>YOUR_JWT</strong></code>
            </p>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-lock"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Require Authentication Code', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('If enabled, an additional Auth Code must be included in the deletion request. Configure Auth Codes in the Auth Codes tab.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="require_delete_auth" value="0"
                    <?php echo $jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Not required', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="require_delete_auth" value="1"
                    <?php echo $jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Required', 'simple-jwt-login'); ?></span>
            </label>
        </div>
        <div id="require_delete_auth_alert"
             class="sjl-gen-warning-banner"
             style="<?php echo $jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete() === true ? 'display:none;' : ''; ?>"
        >
            <span class="dashicons dashicons-warning"></span>
            <?php echo __('Allowing account deletion without an Auth Code is not recommended. Any valid JWT holder could delete accounts.', 'simple-jwt-login'); ?>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Access Control', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Restrict account deletion to specific IP addresses. Leave blank to allow all.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <label class="sjl-gen-field-label" for="delete_ip">
            <?php echo __('Allowed IP Addresses', 'simple-jwt-login'); ?>
        </label>
        <input type="text" id="delete_ip" name="delete_ip" class="form-control sjl-gen-input-medium"
               value="<?php echo esc_attr($jwtSettings->getDeleteUserSettings()->getAllowedDeleteIps()); ?>"
               placeholder="<?php echo __('e.g. 192.168.1.1, 10.0.0.0', 'simple-jwt-login'); ?>"
        />
        <p class="sjl-gen-card-desc" style="margin-top:4px;">
            <?php echo __('Comma-separated. Leave blank to allow all IPs.', 'simple-jwt-login'); ?>
        </p>
    </div>
</div>
