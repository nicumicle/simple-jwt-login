<?php

use SimpleJWTLogin\Services\RouteService;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\UserProperties;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 * @var int|null $errorCode
 */
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Allow Register', 'simple-jwt-login'); ?></h3>
        <div class="form-group">
            <input type="radio" id="allow_register_no" name="allow_register" class="form-control"
                   value="0"
                <?php echo $jwtSettings->getRegisterSettings()->isRegisterAllowed() === false
                    ? 'checked'
                    : '';
                ?>
            />
            <label for="allow_register_no">
                <?php echo __('No', 'simple-jwt-login'); ?>
            </label>

            <input type="radio" id="allow_register_yes" name="allow_register" class="form-control"
                   value="1"
                <?php echo
                    $jwtSettings->getRegisterSettings()->isRegisterAllowed()
                    ? 'checked'
                    : '';
                ?>
            />
            <label for="allow_register_yes">
                <?php echo __('Yes', 'simple-jwt-login'); ?>
            </label>
            <br/>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('URL Example', 'simple-jwt-login'); ?></h3>
        <div class="generated-code">
            <span class="method">POST:</span>
            <span class="code">
                <?php
                $sampleUrlParams = [
                    'email' => __('NEW_USER_EMAIL', 'simple-jwt-login'),
                    'password' => __('NEW_USER_PASSWORD', 'simple-jwt-login'),
                ];
                if ($jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister()) {
                    $sampleUrlParams[$jwtSettings->getAuthCodesSettings()->getAuthCodeKey()] = __('AUTH_KEY_VALUE', 'simple-jwt-login');
                }
                echo esc_html($jwtSettings->generateExampleLink(RouteService::USER_ROUTE, $sampleUrlParams));
                ?>
            </span>
            <span class="copy-button">
                <button class="btn btn-secondary btn-xs">
                    <?php echo __('Copy', 'simple-jwt-login'); ?>
                </button>
            </span>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Register Requires Auth Code', 'simple-jwt-login'); ?></h3>
        <div class="form-group">
            <input type="radio" id="require_register_auth_no" name="require_register_auth" class="form-control"
                   value="0"
                <?php echo $jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister() === false ? 'checked' : '' ?>
            />
            <label for="require_register_auth_no">
                <?php echo __('No', 'simple-jwt-login'); ?>
            </label>
            <input type="radio" id="require_register_auth_yes" name="require_register_auth" class="form-control"
                   value="1"
                <?php echo $jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister() === true ? 'checked' : '' ?>
            />
            <label for="require_register_auth_yes">
                <?php echo __('Yes', 'simple-jwt-login'); ?>
            </label>
            <div id="require_register_auth_alert" class="alert alert-warning" role="alert"
                 style="<?php echo $jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister() === true ? 'display:none;' : ''; ?>">
                <?php echo __(
                    " Warning! It's not recommended to allow register without Auth Codes",
                    'simple-jwt-login'
                ); ?>.
            </div>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
            <?php
            echo isset($errorCode)
            && $settingsErrors->generateCode(
                SettingsErrors::PREFIX_REGISTER,
                SettingsErrors::ERR_REGISTER_MISSING_NEW_USER_PROFILE
            ) === $errorCode
            ||
             $settingsErrors->generateCode(
                SettingsErrors::PREFIX_REGISTER,
                SettingsErrors::ERR_REGISTER_INVALID_ROLE
            ) === $errorCode
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
            <?php echo __('New User profile slug', 'simple-jwt-login'); ?>
        </h3>
        <p class="text-muted"><?php echo __('Example', 'simple-jwt-login'); ?>: `administrator`, `editor`, `author`, `contributor`,
            `subscriber`</p>
        <a href="https://wordpress.org/support/article/roles-and-capabilities/" target="_blank">
            <?php echo __('More details', 'simple-jwt-login'); ?>
        </a>
        <div class="form-group">
            <input type="text" name="new_user_profile" class="form-control"
                   value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getNewUSerProfile()); ?>"
                   placeholder="<?php echo __('New user profile name', 'simple-jwt-login'); ?>"
            />
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('New User Config', 'simple-jwt-login'); ?></h3>
        <input type="checkbox" name="random_password"
               id="random_password"
            <?php echo($jwtSettings->getRegisterSettings()->isRandomPasswordForCreateUserEnabled() ? 'checked' : ''); ?>
               value="1"/>
        <label for="random_password">
            <?php echo __('Generate a random password when a new user is created', 'simple-jwt-login'); ?>
        </label>
        <br/>
        <p class="text-muted"><?php echo __(
                'If this option is selected, the password is no more required when a new user is created.',
                'simple-jwt-login'
            ); ?></p>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <input type="checkbox" name="register_force_login" id="register_force_login"
               value="1" <?php echo($jwtSettings->getRegisterSettings()->isForceLoginAfterCreateUserEnabled() ? 'checked' : ''); ?>>
        <label for="register_force_login">
            <?php echo __('Initialize force login after register', 'simple-jwt-login'); ?>
        </label>
        <br/>
        <p class="text-muted">
            <?php
            echo __(
            'If user registration is completed, the user will continue on the flow configured on login config.'
                . ' If auto-login is disabled, this feature will not work.',
            'simple-jwt-login'
        );
            ?>
        </p>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <input type="checkbox" name="register_jwt" id="register_jwt"
               value="1" <?php echo($jwtSettings->getRegisterSettings()->isJwtEnabled() ? 'checked' : ''); ?>>
        <label for="register_jwt">
            <?php echo __('Return a JWT in the response', 'simple-jwt-login'); ?>
        </label>
        <br/>
        <p class="text-muted">
            <?php
            echo __(
                'If this option is selected, a JWT will be added in the response.'
                . ' By default, it will contain email, id and username in the payload. If the Authentication is enabled, payload will be generated using authentication configuration.',
                'simple-jwt-login'
            );
            ?>
        </p>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __(
                'Allow Register only from the following IP addresses',
                'simple-jwt-login'
            ); ?>:</h3>
        <div class="form-group">
            <input type="text" id="register_ip" name="register_ip" class="form-control"
                   value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getAllowedRegisterIps()); ?>"
                   placeholder="<?php echo __('Enter IP here', 'simple-jwt-login'); ?>"/>
            <p class="text-muted">
                <?php echo __("If you want to add more IP's, separate them by comma", 'simple-jwt-login'); ?>.
                <br/>
                <?php echo __('Leave blank to allow all IP addresses', 'simple-jwt-login'); ?>.
            </p>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
            <?php echo __(
            'Allow Register only for specific email domains',
            'simple-jwt-login'
        ); ?>
            :</h3>
        <div class="form-group">
            <input type="text" id="register_domain" name="register_domain" class="form-control"
                   value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getAllowedRegisterDomain()); ?>"
                   placeholder="<?php echo __('', 'simple-jwt-login'); ?>Email domain"/>
            <p class="text-muted">
                <?php echo __(
                'For example, if you want to allow registration only for users that use their gmail account,'
                    . ' add `gmail.com`',
                'simple-jwt-login'
            ); ?>.
                <?php echo __('For multiple domains, separate them by comma', 'simple-jwt-login'); ?>.
                <br/>
                <?php echo __('Leave blank to allow all domains', 'simple-jwt-login'); ?>.
            </p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Allowed user_meta keys on create user', 'simple-jwt-login'); ?></h3>
        <p>
            <input
                    type="text"
                    class="form-control"
                    name="allowed_user_meta"
                    value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getAllowedUserMeta()); ?>"
            />
            <span class="text-muted">
                <?php echo __(
                    'Separate user_meta keys by comma.'
                    . ' If no user_meta is specified, then users will not be able to'
                    . ' add user_meta via register user.',
                    'simple-jwt-login'
                );
                            ?></span>
            <br/>
            <span class="text-muted"><?php echo __('Example', 'simple-jwt-login'); ?>: my_meta1,my_meta2</span>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('New User available properties', 'simple-jwt-login'); ?></h3>
        <div class="form-group">
            <p>
                <?php echo __(
                                'This properties can be passed in the request when the new user is created.',
                                'simple-jwt-login'
                            ); ?>
            </p>
            <ul class="simple-jwt-register-user-properties">
                <?php
                foreach (UserProperties::getAllowedUserProperties() as $key => $userProperty) {
                    echo "<li> <b>" . esc_html($key) . "</b> : " . esc_html($userProperty['description']) . "</li>";
                }
                ?>
            </ul>
        </div>
    </div>
</div>
