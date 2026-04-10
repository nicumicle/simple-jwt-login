<?php

use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\RouteService;

if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line */
    exit;
} // Exit if accessed directly

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-email-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Password Reset', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Allow users to request a password reset via the JWT API endpoint. A reset code is sent by email and then used to set the new password.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_reset_password" value="0"
                    <?php echo $jwtSettings->getResetPasswordSettings()->isResetPasswordEnabled() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_reset_password" value="1"
                    <?php echo $jwtSettings->getResetPasswordSettings()->isResetPasswordEnabled() ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-lock"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Require Authentication Code', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('If enabled, an additional Auth Code must be included in password reset requests. Configure Auth Codes in the Auth Codes tab.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="reset_password_requires_auth_code" value="0"
                       id="reset_password_auth_code_no"
                    <?php echo $jwtSettings->getResetPasswordSettings()->isAuthKeyRequired() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Not required', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="reset_password_requires_auth_code" value="1"
                       id="reset_password_auth_code_yes"
                    <?php echo $jwtSettings->getResetPasswordSettings()->isAuthKeyRequired() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Required', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-editor-help"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Step 1 — Request Reset Link', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Sends a reset code to the provided email address.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-params-table">
            <div class="sjl-gen-param-def">
                <code class="sjl-gen-var-chip">email</code><span class="required">*</span>
                <span class="sjl-gen-feature-desc"><?php echo __('The email address for which the password reset is requested.', 'simple-jwt-login'); ?></span>
            </div>
        </div>

        <div class="sjl-gen-url-example" style="margin-top:12px;">
            <p class="sjl-gen-url-example-label"><?php echo __('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    $sampleUrlParams = ['email' => __('Email', 'simple-jwt-login')];
                    if ($jwtSettings->getResetPasswordSettings()->isAuthKeyRequired()) {
                        $sampleUrlParams[$jwtSettings->getAuthCodesSettings()->getAuthCodeKey()] =
                            __('AUTH_KEY_VALUE', 'simple-jwt-login');
                    }
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::RESET_PASSWORD_LINK, $sampleUrlParams));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo __('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-editor-ul"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                $hasRoleError = isset($errorCode) && (
                    $settingsErrors->generateCode(SettingsErrors::PREFIX_RESET_PASSWORD, ErrorCodes::ERR_EMPTY_CUSTOM_EMAIL_SUBJECT) === $errorCode
                    || $settingsErrors->generateCode(SettingsErrors::PREFIX_RESET_PASSWORD, ErrorCodes::ERR_MISSING_CODE_FROM_EMAIL_BODY) === $errorCode
                );
                if ($hasRoleError) {
                    echo '<span class="simple-jwt-error">!</span> ';
                }
                ?>
                <?php echo __('Reset Flow', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Choose how the reset code is delivered to the user.', 'simple-jwt-login'); ?>
            </p>
           
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-radio-stack">
            <div>
                <label class="sjl-gen-radio-block">
                    <input type="radio" name="jwt_reset_password_flow"
                        class="jwt_reset_password_flow"
                        id="jwt_reset_password_flow_db"
                        value="<?php echo esc_attr(ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB); ?>"
                        <?php echo $jwtSettings->getResetPasswordSettings()->getFlowType() === ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB ? 'checked="checked"' : ''; ?>
                    />
                    <div>
                        <span class="sjl-gen-radio-block-label"><?php echo __('Save code in database only', 'simple-jwt-login'); ?></span>
                        <span class="sjl-gen-feature-desc"><?php echo __('No email is sent. Retrieve the reset code from the database and use it programmatically.', 'simple-jwt-login'); ?></span>
                    </div>
                </label>
            </div>

            <div>
                <label class="sjl-gen-radio-block">
                    <input type="radio" name="jwt_reset_password_flow"
                        class="jwt_reset_password_flow"
                        id="jwt_reset_password_flow_wordpress"
                        value="<?php echo esc_attr(ResetPasswordSettings::FLOW_SEND_DEFAULT_WP_EMAIL); ?>"
                        <?php echo $jwtSettings->getResetPasswordSettings()->getFlowType() === ResetPasswordSettings::FLOW_SEND_DEFAULT_WP_EMAIL ? 'checked="checked"' : ''; ?>
                    />
                    <div>
                        <span class="sjl-gen-radio-block-label"><?php echo __('Send default WordPress reset email', 'simple-jwt-login'); ?></span>
                        <span class="sjl-gen-feature-desc"><?php echo __('Uses the standard WordPress password reset email template.', 'simple-jwt-login'); ?></span>
                    </div>
                </label>
            </div>

            <div>
                <label class="sjl-gen-radio-block">
                    <input type="radio" name="jwt_reset_password_flow"
                        class="jwt_reset_password_flow"
                        id="jwt_reset_password_flow_custom"
                        value="<?php echo esc_attr(ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL); ?>"
                        <?php echo $jwtSettings->getResetPasswordSettings()->getFlowType() === ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL ? 'checked="checked"' : ''; ?>
                    />
                    <div>
                        <span class="sjl-gen-radio-block-label"><?php echo __('Send custom email', 'simple-jwt-login'); ?></span>
                        <span class="sjl-gen-feature-desc"><?php echo __('Compose a custom subject and body below.', 'simple-jwt-login'); ?></span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Custom email composer (shown only for custom flow) -->
        <div id="simple_jwt_reset_password_email_container" class="sjl-gen-custom-email-box">

            <div class="sjl-gen-feature-item">
                <label class="sjl-gen-feature-label" for="jwt_email_subject">
                    <?php echo __('Email Subject', 'simple-jwt-login'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text" name="jwt_email_subject" id="jwt_email_subject"
                       class="form-control"
                       placeholder="<?php echo __('e.g. Reset your password', 'simple-jwt-login'); ?>"
                       value="<?php echo esc_attr($jwtSettings->getResetPasswordSettings()->getResetPasswordEmailSubject()); ?>"
                />
            </div>

            <div class="sjl-gen-feature-item">
                <label class="sjl-gen-feature-label" for="reset_password_email_body">
                    <?php echo __('Email Body', 'simple-jwt-login'); ?>
                    <span class="required">*</span>
                </label>
                <textarea class="form-control" name="jwt_reset_password_email_body"
                          id="reset_password_email_body"
                          placeholder="<?php echo __('Email content…', 'simple-jwt-login'); ?>"
                ><?php echo esc_html($jwtSettings->getWordPressData()->wpUnslash($jwtSettings->getResetPasswordSettings()->getResetPasswordEmailBody())); ?></textarea>
            </div>

            <div class="sjl-gen-feature-item">
                <span class="sjl-gen-feature-label"><?php echo __('Email Format', 'simple-jwt-login'); ?></span>
                <div class="sjl-gen-radio-group" style="margin-top:6px;">
                    <label class="sjl-gen-radio-option">
                        <input type="radio" name="jwt_email_type" id="jwt_email_type_plain_text" value="0"
                            <?php echo $jwtSettings->getResetPasswordSettings()->getResetPasswordEmailType() === 0 ? 'checked="checked"' : ''; ?>
                        />
                        <span class="sjl-gen-radio-label"><?php echo __('Plain text', 'simple-jwt-login'); ?></span>
                    </label>
                    <label class="sjl-gen-radio-option">
                        <input type="radio" name="jwt_email_type" id="jwt_email_type_html" value="1"
                            <?php echo $jwtSettings->getResetPasswordSettings()->getResetPasswordEmailType() === 1 ? 'checked="checked"' : ''; ?>
                        />
                        <span class="sjl-gen-radio-label">HTML</span>
                    </label>
                </div>
            </div>

            <div class="sjl-gen-variables-box" style="margin-top:4px;">
                <p class="sjl-gen-variables-title">
                    <?php echo __('Available template variables:', 'simple-jwt-login'); ?>
                    &mdash;
                    <span class="sjl-gen-feature-desc" style="display:inline;">
                        <?php echo sprintf(
                            __('Include %s in your body to embed the reset link.', 'simple-jwt-login'),
                            '<code class="sjl-gen-var-chip">{{CODE}}</code>'
                        ); ?>
                    </span>
                </p>
                <div class="sjl-gen-variables-grid">
                    <?php foreach ($jwtSettings->getResetPasswordSettings()->getEmailContentVariables() as $variable => $text) { ?>
                        <code class="sjl-gen-var-chip"><?php echo esc_html($variable); ?></code>
                        <span class="sjl-gen-feature-desc"><?php echo esc_html($text); ?></span>
                    <?php } ?>
                </div>
            </div>

        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-update-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Step 2 — Set New Password', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Submits the reset code received by email along with the new password to complete the reset.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-params-table">
            <div class="sjl-gen-param-def">
                <code class="sjl-gen-var-chip">email</code><span class="required">*</span>
                <span class="sjl-gen-feature-desc"><?php echo __('The email address of the account being reset.', 'simple-jwt-login'); ?></span>
            </div>
            <div class="sjl-gen-param-def">
                <code class="sjl-gen-var-chip">code</code><span class="required">*</span>
                <span class="sjl-gen-feature-desc"><?php echo __('The reset code received by email.', 'simple-jwt-login'); ?></span>
            </div>
            <div class="sjl-gen-param-def">
                <code class="sjl-gen-var-chip">new_password</code><span class="required">*</span>
                <span class="sjl-gen-feature-desc"><?php echo __('The new password to set for the account.', 'simple-jwt-login'); ?></span>
            </div>
        </div>

        <div class="sjl-gen-url-example" style="margin-top:12px;">
            <p class="sjl-gen-url-example-label"><?php echo __('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="method">PUT</span>
                <span class="code">
                    <?php
                    $sampleUrlParams = [
                        'email'        => __('Email', 'simple-jwt-login'),
                        'code'         => __('Code', 'simple-jwt-login'),
                        'new_password' => __('New password', 'simple-jwt-login'),
                    ];
                    if ($jwtSettings->getResetPasswordSettings()->isAuthKeyRequired()) {
                        $sampleUrlParams[$jwtSettings->getAuthCodesSettings()->getAuthCodeKey()] =
                            __('AUTH_KEY_VALUE', 'simple-jwt-login');
                    }
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::RESET_PASSWORD_LINK, $sampleUrlParams));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo __('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
        </div>

        <div class="sjl-gen-feature-toggle" style="margin-top:16px;padding-top:16px;border-top:1px solid #f0f1f2;">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="reset_password_jwt" id="reset_password_jwt" value="1"
                    <?php echo $jwtSettings->getResetPasswordSettings()->isJwtAllowed() ? 'checked' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="reset_password_jwt" class="sjl-gen-feature-label">
                    <?php echo __('Allow JWT-based password reset (skip reset code)', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo __('When enabled, the <code>code</code> parameter is not required. The plugin identifies the user from the JWT payload directly. The JWT must be valid.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>

    </div>
</div>
