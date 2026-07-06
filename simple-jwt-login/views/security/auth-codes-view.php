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
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Authorization Codes', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Auth codes act as a shared secret that must accompany API requests when required. Use a random, hard-to-guess string for each code.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-code-block">
            <p class="sjl-gen-code-block-intro"><?php echo esc_html__('Example auth code:', 'simple-jwt-login'); ?></p>
            <code class="sjl-gen-example-code">THISISMySpeCiaLAUthCode</code>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Configuration', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Set the URL query parameter name used to pass the auth code in API requests.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <label class="sjl-gen-field-label" for="auth_code_key">
            <?php echo esc_html__('Auth Code URL Key', 'simple-jwt-login'); ?>
        </label>
        <input
            name="auth_code_key"
            id="auth_code_key"
            class="form-control sjl-gen-input-medium"
            value="<?php echo esc_attr($jwtSettings->getAuthCodesSettings()->getAuthCodeKey()); ?>"
            placeholder="<?php echo esc_attr__('Auth Code Key', 'simple-jwt-login'); ?>"
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
                <?php echo esc_html__('Auth Codes', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Manage the list of active authentication codes. Each code can optionally target a specific WordPress user role and carry an expiration date.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <!-- Column headers -->
        <div class="sjl-auth-codes-header">
            <span><?php echo esc_html__('Authentication Key', 'simple-jwt-login'); ?></span>
            <span><?php echo esc_html__('WordPress User Role', 'simple-jwt-login'); ?></span>
            <span><?php echo esc_html__('Expiration Date', 'simple-jwt-login'); ?></span>
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
                <div class="auth_row sjl-auth-row">
                    <input type="text"
                           name="auth_codes[code][]"
                           class="form-control sjl-auth-input"
                           value="<?php echo esc_attr($code->getCode()); ?>"
                           placeholder="<?php echo esc_attr__('Authentication Key', 'simple-jwt-login'); ?>"
                    />
                    <input type="text"
                           name="auth_codes[role][]"
                           class="form-control sjl-auth-input"
                           value="<?php echo esc_attr($code->getRole()); ?>"
                           placeholder="<?php echo esc_attr($authCodeRolePlaceholder); ?>"
                    />
                    <input type="text"
                           name="auth_codes[expiration_date][]"
                           class="form-control sjl-auth-input"
                           value="<?php echo esc_attr($code->getExpirationDate()); ?>"
                           placeholder="<?php echo esc_attr($authCodeExpirationDatePlaceholder); ?>"
                    />
                    <button type="button"
                            class="sjl-endpoint-remove"
                            onclick="sjlRemoveAuthLine(jQuery(this));"
                            title="<?php echo esc_attr__('Remove', 'simple-jwt-login'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            <?php } ?>
        </div>

        <button type="button" class="btn btn-outline-secondary" id="add_code" style="margin-top: 10px;">
            <?php echo esc_html__('+ Add Auth Code', 'simple-jwt-login'); ?>
        </button>

        <!-- Field legend -->
        <div class="sjl-gen-variables-box" style="margin-top: 16px;">
            <p class="sjl-gen-variables-title"><?php echo esc_html__('Field reference:', 'simple-jwt-login'); ?></p>
            <div class="sjl-gen-params-table">
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip"><?php echo esc_html__('Authentication Key', 'simple-jwt-login'); ?></code>
                    <span class="sjl-gen-card-desc">
                        <?php echo esc_html__('The code that must be included in the request.', 'simple-jwt-login'); ?>
                    </span>
                </div>
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip"><?php echo esc_html__('WordPress User Role', 'simple-jwt-login'); ?></code>
                    <span class="sjl-gen-card-desc">
                        <?php echo esc_html__('Assigns a WordPress role when a new user is created via this code. Leave blank to use the default role from Register Settings.', 'simple-jwt-login'); ?>
                        <a href="https://wordpress.org/support/article/roles-and-capabilities/" target="_blank">
                            <?php echo esc_html__('More details', 'simple-jwt-login'); ?>
                        </a>
                    </span>
                </div>
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip"><?php echo esc_html__('Expiration Date', 'simple-jwt-login'); ?></code>
                    <span class="sjl-gen-card-desc">
                        <?php echo esc_html__('Optional expiry in <code>YYYY-MM-DD HH:MM:SS</code> format (e.g. 2020-12-24 23:00:00). Leave blank for no expiration.', 'simple-jwt-login'); ?>
                    </span>
                </div>
            </div>
        </div>

    </div>
</div>
