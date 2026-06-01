<?php

use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
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
        <span class="dashicons dashicons-image-rotate"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Allow Refresh Token Endpoint', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'When enabled, the refresh token endpoint is active and a refresh token will be returned alongside the JWT on authentication.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_refresh_token" value="0"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isRefreshTokenEnabled() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_refresh_token" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isRefreshTokenEnabled() === true ? 'checked' : ''; ?>
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
                        RouteService::AUTHENTICATION_REFRESH_ROUTE,
                        ['refresh_token' => 'YOUR_REFRESH_TOKEN']
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
                <?php echo esc_html__('If enabled, an additional authentication code must be provided to use the refresh token endpoint.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="refresh_requires_auth_code" value="0"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isRefreshAuthKeyRequired() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Not required', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="refresh_requires_auth_code" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isRefreshAuthKeyRequired() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Required', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>

<div class="sjl-gen-card" id="refresh_token_key_card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                $hasRefreshTokenError = isset($errorCode) && (
                    $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_REFRESH_TOKEN,
                        SettingsErrors::ERR_AUTHENTICATION_REFRESH_TOKEN_KEY_REQUIRED
                    ) === $errorCode
                    || $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_REFRESH_TOKEN,
                        SettingsErrors::ERR_AUTHENTICATION_REFRESH_TTL_ZERO
                    ) === $errorCode
                );
                if ($hasRefreshTokenError) {
                    echo '<span class="simple-jwt-error">!</span> ';
                }
                ?>
                <?php echo esc_html__('Refresh Token Settings', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'Configure the refresh token lifetime and the secret key used to encrypt refresh tokens stored in the database.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="form-group" style="margin-bottom: 20px;">
            <label class="sjl-gen-field-label" for="jwt_auth_refresh_ttl">
                <?php echo esc_html__('JWT Refresh Window', 'simple-jwt-login'); ?>
                <span class="required">*</span>
            </label>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'Specify the length of time (in minutes) that the refresh token is valid for. Defaults to 2 weeks.',
                    'simple-jwt-login'
                ); ?>
            </p>
            <input type="text" name="jwt_auth_refresh_ttl" id="jwt_auth_refresh_ttl"
                   class="form-control sjl-gen-input-medium"
                   value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAuthJwtRefreshTtl()); ?>"
                   placeholder="<?php echo esc_attr__('Number of minutes', 'simple-jwt-login'); ?>"
            />
        </div>

        <div class="form-group">
            <label for="refresh_token_key">
                <?php echo esc_html__('Refresh Token Secret Key', 'simple-jwt-login'); ?>
                <span class="required">*</span>
            </label>
            <div class="input-group" id="refresh_token_key_container">
                <input type="password" name="refresh_token_key" class="form-control" autocomplete="off"
                       id="refresh_token_key"
                       value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getRefreshTokenKey()); ?>"
                       placeholder="<?php echo esc_attr__('Enter refresh token secret key', 'simple-jwt-login'); ?>"
                />
                <div class="input-group-addon">
                    <a href="javascript:void(0)"
                       onclick="showRefreshTokenKey()"
                       class="toggle_key_button"
                       title="<?php echo esc_attr__('Toggle key visibility', 'simple-jwt-login'); ?>"
                    >
                        <i class="toggle-image" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            <div class="sjl-gen-strength-row">
                <span><?php echo esc_html__('Strength', 'simple-jwt-login'); ?>:</span>
                <progress id="refresh_token_progress" value="0" max="100"></progress>
                <span id="refresh_token_progress_label" class="sjl-gen-strength-label"></span>
            </div>
            <div class="sjl-gen-key-actions">
                <button type="button"
                        onclick="generateRefreshTokenKey()"
                        class="sjl-gen-btn-generate"
                        title="<?php echo esc_attr__('Generate a cryptographically secure random key', 'simple-jwt-login'); ?>"
                >
                    <span class="dashicons dashicons-randomize" aria-hidden="true"></span>
                    <?php echo esc_html__('Generate Secure Key', 'simple-jwt-login'); ?>
                </button>
                <span id="refresh_token_generated_msg" class="sjl-gen-generated-msg" aria-live="polite"></span>
            </div>
        </div>
    </div>
</div>
