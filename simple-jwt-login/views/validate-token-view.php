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
        <span class="dashicons dashicons-yes-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Allow Validate Token Endpoint', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'When enabled, the validate token endpoint is active and clients can verify a JWT and retrieve the associated WordPress user details.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_validate_token" value="0"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isValidateTokenEnabled() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_validate_token" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isValidateTokenEnabled() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="method">GET</span>
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    $sampleUrlParams = [$jwtSettings->getGeneralSettings()->getRequestKeyUrl() => 'YOUR_JWT'];
                    if ($jwtSettings->getAuthenticationSettings()->isValidateAuthKeyRequired()) {
                        $sampleUrlParams[ $jwtSettings->getAuthCodesSettings()->getAuthCodeKey() ] =
                            __('AUTH_KEY_VALUE', 'simple-jwt-login');
                    }
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_VALIDATE_ROUTE, $sampleUrlParams));
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
                <?php echo esc_html__('If enabled, an additional authentication code must be provided to use the validate token endpoint.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="validate_requires_auth_code" value="0"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isValidateAuthKeyRequired() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Not required', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="validate_requires_auth_code" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isValidateAuthKeyRequired() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Required', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>
