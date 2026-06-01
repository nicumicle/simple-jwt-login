<?php

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Services\RouteService;

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
        <span class="dashicons dashicons-dismiss"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Allow Revoke Token Endpoint', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'When enabled, the revoke token endpoint is active and clients can invalidate a JWT for future requests.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_revoke_token" value="0"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isRevokeTokenEnabled() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_revoke_token" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isRevokeTokenEnabled() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    echo esc_html($jwtSettings->generateExampleLink(
                        RouteService::AUTHENTICATION_REVOKE,
                        [$jwtSettings->getGeneralSettings()->getRequestKeyUrl() => 'YOUR_JWT']
                    ));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo esc_html__('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-lock"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Require Authentication Code', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('If enabled, an additional authentication code must be provided to use the revoke token endpoint.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="revoke_requires_auth_code" value="0"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isRevokeAuthKeyRequired() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Not required', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="revoke_requires_auth_code" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isRevokeAuthKeyRequired() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Required', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>
