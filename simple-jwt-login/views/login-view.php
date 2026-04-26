<?php

use SimpleJWTLogin\Modules\Settings\LoginSettings;
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
        <span class="dashicons dashicons-admin-users"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Auto-Login', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Allow users to log in automatically by providing a valid JWT via URL parameter or Authorization header.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_autologin" value="0"
                    <?php echo $jwtSettings->getLoginSettings()->isAutologinEnabled() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_autologin" value="1"
                    <?php echo $jwtSettings->getLoginSettings()->isAutologinEnabled() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="method">GET</span>
                <span class="code">
                    <?php
                    $sampleUrlParams = [
                        $jwtSettings->getGeneralSettings()->getRequestKeyUrl() => __('JWT', 'simple-jwt-login')
                    ];
                    if ($jwtSettings->getLoginSettings()->isAuthKeyRequiredOnLogin()) {
                        $sampleUrlParams[ $jwtSettings->getAuthCodesSettings()->getAuthCodeKey() ] =
                            __('AUTH_KEY_VALUE', 'simple-jwt-login');
                    }
                    echo esc_html($jwtSettings->generateExampleLink('autologin', $sampleUrlParams));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo esc_html__('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
            <p class="sjl-gen-card-desc" style="margin-top:6px;">
                <?php echo esc_html__('You can also pass the JWT in the Authorization header:', 'simple-jwt-login'); ?>
                <code class="sjl-gen-example-code">Authorization: Bearer <strong>YOUR_JWT</strong></code>
            </p>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-lock"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Require Authentication Code', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('If enabled, an additional Auth Code must be provided alongside the JWT to allow login. Configure Auth Codes in the Auth Codes tab.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="require_login_auth" value="0"
                    <?php echo $jwtSettings->getLoginSettings()->isAuthKeyRequiredOnLogin() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Not required', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="require_login_auth" value="1"
                    <?php echo $jwtSettings->getLoginSettings()->isAuthKeyRequiredOnLogin() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Required', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-arrow-right-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                echo isset($errorCode)
                && $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_LOGIN,
                    SettingsErrors::ERR_LOGIN_INVALID_CUSTOM_URL
                ) === $errorCode
                    ? '<span class="simple-jwt-error">!</span> '
                    : '';
                ?>
                <?php echo esc_html__('Redirect Behavior', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Control where users are sent after login succeeds or fails.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <!-- Success redirect -->
        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">1</div>
            <div class="sjl-gen-step-content">
                <span class="sjl-gen-step-label"><?php echo esc_html__('After successful login, redirect to:', 'simple-jwt-login'); ?></span>

                <div class="sjl-gen-radio-grid">
                    <label class="sjl-gen-radio-card">
                        <input type="radio" name="redirect"
                               value="<?php echo esc_attr(LoginSettings::REDIRECT_DASHBOARD); ?>"
                            <?php echo $jwtSettings->getLoginSettings()->getRedirect() === LoginSettings::REDIRECT_DASHBOARD ? 'checked' : ''; ?>
                        />
                        <span class="dashicons dashicons-dashboard"></span>
                        <span><?php echo esc_html__('Dashboard', 'simple-jwt-login'); ?></span>
                    </label>
                    <label class="sjl-gen-radio-card">
                        <input type="radio" name="redirect"
                               value="<?php echo esc_attr(LoginSettings::REDIRECT_HOMEPAGE); ?>"
                            <?php echo $jwtSettings->getLoginSettings()->getRedirect() === LoginSettings::REDIRECT_HOMEPAGE ? 'checked' : ''; ?>
                        />
                        <span class="dashicons dashicons-admin-home"></span>
                        <span><?php echo esc_html__('Homepage', 'simple-jwt-login'); ?></span>
                    </label>
                    <label class="sjl-gen-radio-card">
                        <input type="radio" name="redirect"
                               value="<?php echo esc_attr(LoginSettings::NO_REDIRECT); ?>"
                            <?php echo $jwtSettings->getLoginSettings()->getRedirect() === LoginSettings::NO_REDIRECT ? 'checked' : ''; ?>
                        />
                        <span class="dashicons dashicons-no-alt"></span>
                        <span><?php echo esc_html__('No redirect', 'simple-jwt-login'); ?></span>
                    </label>
                    <label class="sjl-gen-radio-card">
                        <input type="radio" name="redirect"
                               value="<?php echo esc_attr(LoginSettings::REDIRECT_CUSTOM); ?>"
                            <?php echo $jwtSettings->getLoginSettings()->getRedirect() === LoginSettings::REDIRECT_CUSTOM ? 'checked' : ''; ?>
                        />
                        <span class="dashicons dashicons-admin-links"></span>
                        <span><?php echo esc_html__('Custom URL', 'simple-jwt-login'); ?></span>
                    </label>
                </div>

                <input type="text" id="redirect_url" name="redirect_url" class="form-control sjl-gen-input-medium"
                       placeholder="https://your-site.com/welcome"
                       value="<?php echo esc_attr($jwtSettings->getLoginSettings()->getCustomRedirectURL()); ?>"
                       style="<?php echo $jwtSettings->getLoginSettings()->getRedirect() === LoginSettings::REDIRECT_CUSTOM ? '' : 'display:none;'; ?>"
                />
            </div>
        </div>

        <!-- Failure redirect -->
        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">2</div>
            <div class="sjl-gen-step-content">
                <label class="sjl-gen-step-label" for="login_fail_redirect">
                    <?php echo esc_html__('On login failure, redirect to:', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-step-desc">
                    <?php echo esc_html__('Leave blank to show an error response without redirecting.', 'simple-jwt-login'); ?>
                </p>
                <input type="text" id="login_fail_redirect" name="login_fail_redirect"
                       class="form-control sjl-gen-input-medium"
                       value="<?php echo esc_attr($jwtSettings->getLoginSettings()->getAutologinRedirectOnFail()); ?>"
                       placeholder="https://your-site.com/login-failed"
                />
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Advanced Options', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Fine-tune URL parameters and redirect behavior after login.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <!-- Include request parameters -->
        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="include_login_request_parameters"
                       id="include_login_request_parameters" value="1"
                    <?php echo $jwtSettings->getLoginSettings()->getShouldIncludeRequestParameters() ? 'checked' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="include_login_request_parameters" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Pass login request parameters to the redirect URL', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('Appends the original JWT and any other login request parameters to the redirect URL as query string arguments.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>

        <!-- Remove parameters on redirect -->
        <div class="sjl-gen-feature-item">
            <label class="sjl-gen-feature-label" for="login_remove_request_parameters">
                <?php echo esc_html__('Strip these parameters from the redirect URL', 'simple-jwt-login'); ?>
            </label>
            <p class="sjl-gen-feature-desc">
                <?php echo esc_html__('Comma-separated list of query parameters to remove after redirect (e.g. jwt,auth_code). Leave blank to keep all parameters.', 'simple-jwt-login'); ?>
            </p>
            <input type="text" id="login_remove_request_parameters" name="login_remove_request_parameters"
                   class="form-control sjl-gen-input-medium"
                   value="<?php echo esc_attr($jwtSettings->getLoginSettings()->getDangerousQueryParameters()); ?>"
                   placeholder="<?php echo esc_attr__('e.g. jwt, auth_code', 'simple-jwt-login'); ?>"
            />
        </div>

        <!-- Allow redirect parameter -->
        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="allow_usage_redirect_parameter"
                       id="allow_usage_redirect_parameter" value="1"
                    <?php echo $jwtSettings->getLoginSettings()->isRedirectParameterAllowed() ? 'checked' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="allow_usage_redirect_parameter" class="sjl-gen-feature-label">
                    <?php echo wp_kses(
                        sprintf(
                            __('Honor the <code>%s</code> query parameter as a redirect override', 'simple-jwt-login'),
                            esc_html(LoginSettings::REDIRECT_URL_PARAMETER)
                        ),
                        ['code' => []]
                    ); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo wp_kses(
                        sprintf(
                            __('When present in the request, the <code>%s</code> parameter overrides any configured redirect destination.', 'simple-jwt-login'),
                            esc_html(LoginSettings::REDIRECT_URL_PARAMETER)
                        ),
                        ['code' => []]
                    ); ?>
                </p>

                <div class="sjl-gen-variables-box">
                    <p class="sjl-gen-variables-title"><?php echo esc_html__('Available URL template variables:', 'simple-jwt-login'); ?></p>
                    <div class="sjl-gen-variables-grid">
                        <code class="sjl-gen-var-chip">{{site_url}}</code><span><?php echo esc_html__('Site URL', 'simple-jwt-login'); ?></span>
                        <code class="sjl-gen-var-chip">{{user_id}}</code><span><?php echo esc_html__('Logged-in user ID', 'simple-jwt-login'); ?></span>
                        <code class="sjl-gen-var-chip">{{user_email}}</code><span><?php echo esc_html__('User email', 'simple-jwt-login'); ?></span>
                        <code class="sjl-gen-var-chip">{{user_login}}</code><span><?php echo esc_html__('Username', 'simple-jwt-login'); ?></span>
                        <code class="sjl-gen-var-chip">{{user_first_name}}</code><span><?php echo esc_html__('First name', 'simple-jwt-login'); ?></span>
                        <code class="sjl-gen-var-chip">{{user_last_name}}</code><span><?php echo esc_html__('Last name', 'simple-jwt-login'); ?></span>
                        <code class="sjl-gen-var-chip">{{user_nicename}}</code><span><?php echo esc_html__('Nice name', 'simple-jwt-login'); ?></span>
                    </div>
                    <p class="sjl-gen-feature-desc" style="margin-top:8px;">
                        <?php echo esc_html__('Example:', 'simple-jwt-login'); ?>
                        <code class="sjl-gen-example-code"><?php echo esc_url(site_url()); ?>?uid={{user_id}}&amp;site={{site_url}}</code>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Access Control', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Restrict auto-login to specific IP addresses or JWT issuers. Leave fields blank to allow all.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-two-col">
            <div class="sjl-gen-two-col-left">
                <label class="sjl-gen-field-label" for="login_ip">
                    <?php echo esc_html__('Allowed IP Addresses', 'simple-jwt-login'); ?>
                </label>
                <input type="text" id="login_ip" name="login_ip" class="form-control"
                       value="<?php echo esc_attr($jwtSettings->getLoginSettings()->getAllowedLoginIps()); ?>"
                       placeholder="<?php echo esc_attr__('e.g. 192.168.1.1, 10.0.0.0', 'simple-jwt-login'); ?>"
                />
                <p class="sjl-gen-card-desc" style="margin-top:4px;">
                    <?php echo esc_html__('Comma-separated. Leave blank to allow all IPs.', 'simple-jwt-login'); ?>
                </p>
            </div>
            <div class="sjl-gen-two-col-right">
                <label class="sjl-gen-field-label" for="login_iss">
                    <?php echo esc_html__('Allowed JWT Issuers (iss)', 'simple-jwt-login'); ?>
                </label>
                <input type="text" id="login_iss" name="login_iss" class="form-control"
                       value="<?php echo esc_attr($jwtSettings->getLoginSettings()->getAllowedLoginIss()); ?>"
                       placeholder="<?php echo esc_attr__('e.g. https://auth.example.com', 'simple-jwt-login'); ?>"
                />
                <p class="sjl-gen-card-desc" style="margin-top:4px;">
                    <?php echo esc_html__('Comma-separated. Leave blank to allow any issuer.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>
    </div>
</div>
