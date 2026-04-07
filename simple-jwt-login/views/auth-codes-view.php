<?php

use SimpleJWTLogin\Modules\AuthCodeBuilder;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
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
        <span class="dashicons dashicons-tickets-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Authorization Codes', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Auth codes act as a shared secret that must accompany API requests when required. Use a random, hard-to-guess string for each code.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-code-block">
            <p class="sjl-gen-code-block-intro"><?php echo __('Example auth code:', 'simple-jwt-login'); ?></p>
            <code class="sjl-gen-example-code">THISISMySpeCiaLAUthCode</code>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Configuration', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Set the URL query parameter name used to pass the auth code in API requests.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <label class="sjl-gen-field-label" for="auth_code_key">
            <?php echo __('Auth Code URL Key', 'simple-jwt-login'); ?>
        </label>
        <input
            name="auth_code_key"
            id="auth_code_key"
            class="form-control sjl-gen-input-medium"
            value="<?php echo esc_attr($jwtSettings->getAuthCodesSettings()->getAuthCodeKey()); ?>"
            placeholder="<?php echo __('Auth Code Key', 'simple-jwt-login'); ?>"
        />
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-list-view"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                echo isset($errorCode)
                && $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTH_CODES,
                    SettingsErrors::ERR_INVALID_ROLE
                ) === $errorCode
                    ? '<span class="simple-jwt-error">!</span> '
                    : '';
                ?>
                <?php echo __('Auth Codes', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Manage the list of active authentication codes. Each code can optionally target a specific WordPress user role and carry an expiration date.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div style="margin-bottom: 14px;">
            <input type="button" class="btn btn-dark" id="add_code"
                   value="<?php echo __('Add Auth Code', 'simple-jwt-login'); ?> +"
            />
        </div>

        <!-- Column headers -->
        <div class="sjl-gen-auth-codes-header">
            <span><?php echo __('Authentication Key', 'simple-jwt-login'); ?></span>
            <span><?php echo __('WordPress User Role', 'simple-jwt-login'); ?></span>
            <span><?php echo __('Expiration Date', 'simple-jwt-login'); ?></span>
            <span></span>
        </div>

        <!-- Code rows -->
        <div id="auth_codes">
            <?php
            foreach ($jwtSettings->getAuthCodesSettings()->getAuthCodes() as $code) {
                $code = new AuthCodeBuilder($code);
                $authCodeRolePlaceholder = __(
                    'WordPress new user Role ( when new users are created )',
                    'simple-jwt-login'
                );
                $authCodeExpirationDatePlaceholder = __(
                    'Expiration date: YYYY-MM-DD HH:MM:SS ( Example: 2020-12-23 23:34:59)',
                    'simple-jwt-login'
                );
                ?>
                <div class="form-group auth_row">
                    <div class="input-group">
                        <input type="text"
                               name="auth_codes[code][]"
                               class="form-control"
                               value="<?php echo esc_attr($code->getCode()); ?>"
                               placeholder="<?php echo __('Authentication Key', 'simple-jwt-login'); ?>"
                        />
                        <input type="text"
                               name="auth_codes[role][]"
                               class="form-control"
                               value="<?php echo esc_attr($code->getRole()); ?>"
                               placeholder="<?php echo esc_attr($authCodeRolePlaceholder); ?>"
                        />
                        <input type="text"
                               name="auth_codes[expiration_date][]"
                               class="form-control"
                               value="<?php echo esc_attr($code->getExpirationDate()); ?>"
                               placeholder="<?php echo esc_attr($authCodeExpirationDatePlaceholder); ?>"
                        />
                        <div class="input-group-addon auth-code-delete-container">
                            <a href="javascript:void(0)"
                               onclick="jwt_login_remove_auth_line(jQuery(this));"
                               title="<?php echo __('delete', 'simple-jwt-login'); ?>"
                            >
                                <i class="delete-auth-code" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <!-- Field legend -->
        <div class="sjl-gen-variables-box" style="margin-top: 16px;">
            <p class="sjl-gen-variables-title"><?php echo __('Field reference:', 'simple-jwt-login'); ?></p>
            <div class="sjl-gen-params-table">
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip"><?php echo __('Authentication Key', 'simple-jwt-login'); ?></code>
                    <span class="sjl-gen-card-desc">
                        <?php echo __('The code that must be included in the request.', 'simple-jwt-login'); ?>
                    </span>
                </div>
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip"><?php echo __('WordPress User Role', 'simple-jwt-login'); ?></code>
                    <span class="sjl-gen-card-desc">
                        <?php echo __('Assigns a WordPress role when a new user is created via this code. Leave blank to use the default role from Register Settings.', 'simple-jwt-login'); ?>
                        <a href="https://wordpress.org/support/article/roles-and-capabilities/" target="_blank">
                            <?php echo __('More details', 'simple-jwt-login'); ?>
                        </a>
                    </span>
                </div>
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip"><?php echo __('Expiration Date', 'simple-jwt-login'); ?></code>
                    <span class="sjl-gen-card-desc">
                        <?php echo __('Optional expiry in <code>YYYY-MM-DD HH:MM:SS</code> format (e.g. 2020-12-24 23:00:00). Leave blank for no expiration.', 'simple-jwt-login'); ?>
                    </span>
                </div>
            </div>
        </div>

    </div>
</div>
