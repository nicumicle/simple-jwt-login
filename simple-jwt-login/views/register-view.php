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
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Allow User Registration', 'simple-jwt-login'); ?></h3>
        <p class="text-muted">
            <?php echo __('Allow new users to register via JWT API endpoints.', 'simple-jwt-login'); ?>
        </p>
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
            <span class="method">POST</span>
            <span class="code">
                <?php
                $sampleUrlParams = [
                    'email' => __('NEW_USER_EMAIL', 'simple-jwt-login'),
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
        <h3 class="section-title"><?php echo __('Require Authentication Code for Registration', 'simple-jwt-login'); ?></h3>
        <p class="text-muted">
            <?php echo __('If enabled, an additional authentication code must be provided for user registration.', 'simple-jwt-login'); ?>
        </p>
        <div class="form-group">
            <input type="radio" id="require_register_auth_no" name="require_register_auth" class="form-control"
                   value="0"
                <?php echo $jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister() === false
                    ? 'checked'
                    : ''
                ?>
            />
            <label for="require_register_auth_no">
                <?php echo __('No', 'simple-jwt-login'); ?>
            </label>
            <input type="radio" id="require_register_auth_yes" name="require_register_auth" class="form-control"
                   value="1"
                <?php echo $jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister() === true
                    ? 'checked'
                    : ''
                ?>
            />
            <label for="require_register_auth_yes">
                <?php echo __('Yes', 'simple-jwt-login'); ?>
            </label>
            <div id="require_register_auth_alert" class="alert alert-warning" role="alert"
                 style="<?php
                     echo $jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister() === true
                         ? 'display:none;'
                         : '';
                    ?>">
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
            <?php echo __('Default User Role', 'simple-jwt-login'); ?>
        </h3>
        <p class="text-muted">
            <?php echo __('Specify the WordPress role assigned to newly registered users.', 'simple-jwt-login'); ?>
        </p>
        <p class="text-muted">
            <?php echo __('Example', 'simple-jwt-login'); ?>
            : `administrator`, `editor`, `author`, `contributor`, `subscriber`
        </p>
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
        <h3 class="section-title">
            <?php
            echo isset($errorCode)
            && (
                    $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_REGISTER,
                        SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_NUMERIC
                    ) === $errorCode
                ||
                    $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_REGISTER,
                        SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_MIN_LENGTH
                    ) === $errorCode
                ||
                    $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_REGISTER,
                        SettingsErrors::ERR_REGISTER_RANDOM_PASS_LENGTH_MAX_LENGTH
                    ) === $errorCode
            )
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
            <?php echo __('User Creation Settings', 'simple-jwt-login'); ?>
        </h3>
        <p class="text-muted">
            <?php echo __('Configure how new user accounts are created during registration.', 'simple-jwt-login'); ?>
        </p>
        <input type="checkbox" name="random_password"
               id="random_password"
            <?php echo($jwtSettings->getRegisterSettings()->isRandomPasswordForCreateUserEnabled() ? 'checked' : ''); ?>
               value="1"/>
        <label for="random_password">
            <?php echo __('Generate a random password when a new user is created', 'simple-jwt-login'); ?>
        </label>
        <br/>
        <p class="text-muted">
            <?php
            echo __(
                'If this option is selected, the password is no more required when a new user is created.',
                'simple-jwt-login'
            );
            ?>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <label for="random_password_length">
            <?php echo __('Random password length', 'simple-jwt-login'); ?>
        </label>
        <br />
        <div class="text-muted">
            <?php
            echo __(
                'The number of characters for the random generated password',
                'simple-jwt-login'
            );
            ?>
        </div>
        <br />
        <input type="text" name="random_password_length"
               id="random_password_length"
               value="<?php
                    echo $jwtSettings->getRegisterSettings()->getRandomPasswordLength();
                ?>"
        />
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <input type="checkbox" name="register_force_login" id="register_force_login"
               value="1"
            <?php echo($jwtSettings->getRegisterSettings()->isForceLoginAfterCreateUserEnabled() ? 'checked' : ''); ?>
        />
        <label for="register_force_login">
            <?php echo __('Initialize force login after register', 'simple-jwt-login'); ?>
        </label>
        <br/>
        <p class="text-muted">
            <?php
            echo __(
                'Automatically log in the user after successful registration, following the login configuration settings. Requires auto-login to be enabled.',
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
            <?php echo __('Return JWT on Registration', 'simple-jwt-login'); ?>
        </label>
        <br/>
        <p class="text-muted">
            <?php
            echo __(
                'If this option is selected, a JWT will be added in the response.'
                . ' By default, it will contain email, id and username in the payload.'
                . ' If the Authentication is enabled, payload will be generated using authentication configuration.',
                'simple-jwt-login'
            );
            ?>
        </p>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
            <?php echo __(
                'Restrict Registration to Specific IP Addresses',
                'simple-jwt-login'
            ); ?>:
        </h3>
        <p class="text-muted">
            <?php echo __('Only allow user registration from these IP addresses. Leave blank to allow from any IP.', 'simple-jwt-login'); ?>
        </p>
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
                'Restrict Registration to Specific Email Domains',
                'simple-jwt-login'
            ); ?>
            :
        </h3>
        <div class="form-group">
            <input type="text" id="register_domain" name="register_domain" class="form-control"
                   value="<?php echo esc_attr($jwtSettings->getRegisterSettings()->getAllowedRegisterDomain()); ?>"
                   placeholder="<?php echo __('', 'simple-jwt-login'); ?>Email domain"/>
            <p class="text-muted">
                <?php
                echo __(
                    'For example, if you want to allow registration only for users that use their gmail account,'
                    . ' add `gmail.com`',
                    'simple-jwt-login'
                );
                ?>.
                <?php echo __('For multiple domains, separate them by comma', 'simple-jwt-login'); ?>.
                <br/>
                <?php echo __('Leave blank to allow all domains', 'simple-jwt-login'); ?>.
            </p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Allowed User Meta Keys', 'simple-jwt-login'); ?></h3>
        <p class="text-muted">
            <?php echo __('Specify which user meta keys can be set during registration. Leave blank to disallow all.', 'simple-jwt-login'); ?>
        </p>
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
        <h3 class="section-title"><?php echo __('Available User Properties', 'simple-jwt-login'); ?></h3>
        <p class="text-muted">
            <?php echo __('These properties can be included in the registration request to set user details.', 'simple-jwt-login'); ?>
        </p>
        <div class="form-group">
            <p>
                <?php
                echo __(
                    'This properties can be passed in the request when the new user is created.',
                    'simple-jwt-login'
                );
                ?>
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
