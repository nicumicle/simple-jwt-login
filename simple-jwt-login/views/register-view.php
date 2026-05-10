<?php

use SimpleJWTLogin\Services\RouteService;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\UserProperties;

if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 * @var int|null $errorCode
 */
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-users"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('User Registration', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Allow new users to register via the JWT API endpoint.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_register" value="0"
                    <?php echo $jwtSettings->getRegisterSettings()->isRegisterAllowed() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_register" value="1"
                    <?php echo $jwtSettings->getRegisterSettings()->isRegisterAllowed() ? 'checked' : ''; ?>
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
                    $sampleUrlParams = [
                        'email'    => __('NEW_USER_EMAIL', 'simple-jwt-login'),
                        'password' => __('NEW_USER_PASSWORD', 'simple-jwt-login'),
                    ];
                    if ($jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister()) {
                        $sampleUrlParams[$jwtSettings->getAuthCodesSettings()->getAuthCodeKey()] =
                            __('AUTH_KEY_VALUE', 'simple-jwt-login');
                    }
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::USER_ROUTE, $sampleUrlParams));
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
                <?php echo esc_html__('If enabled, an additional Auth Code must be included in the registration request. Configure Auth Codes in the Auth Codes tab.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="require_register_auth" value="0"
                    <?php echo $jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Not required', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="require_register_auth" value="1"
                    <?php echo $jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Required', 'simple-jwt-login'); ?></span>
            </label>
        </div>
        <div id="require_register_auth_alert"
             class="sjl-gen-warning-banner"
             style="<?php echo $jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister() === true ? 'display:none;' : ''; ?>"
        >
            <span class="dashicons dashicons-warning"></span>
            <?php echo esc_html__('Allowing registration without an Auth Code is not recommended. Anyone can create accounts on your site.', 'simple-jwt-login'); ?>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-id-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                $hasRoleError = isset($errorCode) && (
                    $settingsErrors->generateCode(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_MISSING_NEW_USER_PROFILE) === $errorCode
                    || $settingsErrors->generateCode(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_INVALID_ROLE) === $errorCode
                );
                if ($hasRoleError) {
                    echo '<span class="simple-jwt-error">!</span> ';
                }
                ?>
                <?php echo esc_html__('New User Settings', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Configure the default role and password behaviour for newly registered users.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <!-- Default Role -->
        <div class="sjl-gen-feature-item">
            <label class="sjl-gen-feature-label" for="new_user_profile">
                <?php
                if ($hasRoleError) {
                    echo '<span class="simple-jwt-error">!</span>';
                }
                ?>
                <?php echo esc_html__('Default User Role', 'simple-jwt-login'); ?>
                 <span class="required">*</span>
               
            </label>
            <p class="sjl-gen-feature-desc">
                <?php echo esc_html__('WordPress role assigned to new users.', 'simple-jwt-login'); ?>
                <?php echo esc_html__('Common values:', 'simple-jwt-login'); ?>
                <code class="sjl-gen-var-chip">administrator</code>
                <code class="sjl-gen-var-chip">editor</code>
                <code class="sjl-gen-var-chip">author</code>
                <code class="sjl-gen-var-chip">contributor</code>
                <code class="sjl-gen-var-chip">subscriber</code>
                &mdash; <a href="https://wordpress.org/support/article/roles-and-capabilities/" target="_blank"><?php echo esc_html__('full list', 'simple-jwt-login'); ?></a>
            </p>
            <input type="text" name="new_user_profile" id="new_user_profile" class="form-control sjl-gen-input-medium"
                   value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getNewUserProfile()); ?>"
                   placeholder="<?php echo esc_attr__('e.g. subscriber', 'simple-jwt-login'); ?>"
            />
        </div>

        <!-- Random Password -->
        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="random_password" id="random_password" value="1"
                    <?php echo $jwtSettings->getRegisterSettings()->isRandomPasswordForCreateUserEnabled() ? 'checked' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="random_password" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Generate a random password', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('When enabled, a secure random password is generated automatically and the password field is not required in the registration request.', 'simple-jwt-login'); ?>
                </p>
                <div class="sjl-gen-inline-field" style="margin-top:8px;">
                    <label class="sjl-gen-field-label" for="random_password_length" style="display:inline-block;margin-right:8px;">
                        <?php
                        $hasPassLengthError = isset($errorCode) && (
                            $settingsErrors->generateCode(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_NUMERIC) === $errorCode
                            || $settingsErrors->generateCode(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_MIN_LENGTH) === $errorCode
                            || $settingsErrors->generateCode(SettingsErrors::PREFIX_REGISTER, SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_MAX_LENGTH) === $errorCode
                        );
                        if ($hasPassLengthError) {
                            echo '<span class="simple-jwt-error">!</span> ';
                        }
                        echo esc_html__('Password length:', 'simple-jwt-login');
                        ?>
                    </label>
                    <input type="text" name="random_password_length" id="random_password_length"
                           class="sjl-gen-short-input"
                           value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getRandomPasswordLength()); ?>"
                           placeholder="12"
                    />
                    <span class="sjl-gen-card-desc" style="margin-left:6px;"><?php echo esc_html__('characters', 'simple-jwt-login'); ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-update"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Post-Registration Options', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('What happens immediately after a user is successfully registered.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="register_force_login" id="register_force_login" value="1"
                    <?php echo $jwtSettings->getRegisterSettings()->isForceLoginAfterCreateUserEnabled() ? 'checked' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="register_force_login" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Auto-login after registration', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('Automatically authenticates the new user after successful registration, using the Login tab redirect settings. Requires Auto-Login to be enabled.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="register_jwt" id="register_jwt" value="1"
                    <?php echo $jwtSettings->getRegisterSettings()->isJwtEnabled() ? 'checked' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="register_jwt" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Return a JWT in the registration response', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('Includes a signed JWT in the API response. The payload contains email, user ID, and username by default. If Authentication is configured, the payload follows those settings instead.', 'simple-jwt-login'); ?>
                </p>
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
                <?php echo esc_html__('Restrict registrations by IP address or email domain. Leave fields blank to allow all.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-two-col">
            <div class="sjl-gen-two-col-left">
                <label class="sjl-gen-field-label" for="register_ip">
                    <?php echo esc_html__('Allowed IP Addresses', 'simple-jwt-login'); ?>
                </label>
                <input type="text" id="register_ip" name="register_ip" class="form-control"
                       value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getAllowedRegisterIps()); ?>"
                       placeholder="<?php echo esc_attr__('e.g. 192.168.1.1, 10.0.0.0', 'simple-jwt-login'); ?>"
                />
                <p class="sjl-gen-card-desc" style="margin-top:4px;">
                    <?php echo esc_html__('Comma-separated. Leave blank to allow all IPs.', 'simple-jwt-login'); ?>
                </p>
            </div>
            <div class="sjl-gen-two-col-right">
                <label class="sjl-gen-field-label" for="register_domain">
                    <?php echo esc_html__('Allowed Email Domains', 'simple-jwt-login'); ?>
                </label>
                <input type="text" id="register_domain" name="register_domain" class="form-control"
                       value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getAllowedRegisterDomain()); ?>"
                       placeholder="<?php echo esc_attr__('e.g. gmail.com, company.org', 'simple-jwt-login'); ?>"
                />
                <p class="sjl-gen-card-desc" style="margin-top:4px;">
                    <?php echo esc_html__('Comma-separated. Leave blank to allow all domains.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-list-view"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('User Data', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Control which extra user properties and meta keys can be set during registration.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <!-- Allowed user meta -->
        <div class="sjl-gen-feature-item">
            <label class="sjl-gen-feature-label" for="allowed_user_meta">
                <?php echo esc_html__('Allowed User Meta Keys', 'simple-jwt-login'); ?>
            </label>
            <p class="sjl-gen-feature-desc">
                <?php echo esc_html__('Comma-separated list of <code>user_meta</code> keys that may be set during registration. Leave blank to disallow all meta.', 'simple-jwt-login'); ?>
                <?php echo esc_html__('Example:', 'simple-jwt-login'); ?>
                <code class="sjl-gen-example-code">my_meta1, my_meta2</code>
            </p>
            <input type="text" class="form-control sjl-gen-input-medium" name="allowed_user_meta" id="allowed_user_meta"
                   value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getAllowedUserMeta()); ?>"
                   placeholder="<?php echo esc_attr__('e.g. my_meta1, my_meta2', 'simple-jwt-login'); ?>"
            />
        </div>

        <!-- Available properties reference -->
        <div class="sjl-gen-feature-item" style="border-bottom:none;padding-bottom:0;">
            <p class="sjl-gen-feature-label"><?php echo esc_html__('Available User Properties', 'simple-jwt-login'); ?></p>
            <p class="sjl-gen-feature-desc" style="margin-bottom:10px;">
                <?php echo esc_html__('These standard WordPress user properties can be included in the registration POST body.', 'simple-jwt-login'); ?>
            </p>
            <div class="sjl-gen-props-grid">
                <?php foreach (UserProperties::getAllowedUserProperties() as $key => $userProperty) { ?>
                    <div class="sjl-gen-prop-row">
                        <code class="sjl-gen-var-chip"><?php echo esc_html($key); ?></code>
                        <span class="sjl-gen-feature-desc"><?php echo esc_html($userProperty['description']); ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>

    </div>
</div>
