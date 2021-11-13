<?php

use SimpleJWTLogin\Modules\AuthCodeBuilder;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Authorization Codes', 'simple-jwt-login'); ?></h3>
        <p class="text-justify">
            <?php echo __('Add authorization codes for authentication to this WordPress', 'simple-jwt-login'); ?>.
            <br/>
            <?php echo __(
                'One of this codes should be added in the request parameters for each API request',
                'simple-jwt-login'
); ?>.
            <br/>
            <?php echo __('For security reasons please use some random strings', 'simple-jwt-login'); ?>.
            <br/>
            <p class="text-muted"><?php echo __('Example: THISISMySpeCiaLAUthCode', 'simple-jwt-login'); ?></p>
        </p>
        <br/>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Config', 'simple-jwt-login'); ?></h3>
        <label for="auth_code_key"><b><?php echo __('Auth Code URL Key', 'simple-jwt-login'); ?></b></label> :
        <input
                name="auth_code_key"
                value="<?php echo esc_attr($jwtSettings->getAuthCodesSettings()->getAuthCodeKey()); ?>"
                class="form-control"
                id="auth_code_key"
                placeholder="<?php echo __('Auth Code Key', 'simple-jwt-login'); ?>"
        />
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
            <?php
            echo isset($errorCode)
            && $settingsErrors->generateCode(
                SettingsErrors::PREFIX_AUTH_CODES,
                SettingsErrors::ERR_INVALID_ROLE
            ) === $errorCode
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
            <?php echo __('Auth Codes', 'simple-jwt-login'); ?>
        </h3>
        <input type="button" class="btn btn-dark" value="<?php echo __('Add Auth Code', 'simple-jwt-login'); ?> +"
               id="add_code"/>
        <br/>
        <br/>
    </div>
</div>
<div class="row text-center">
    <div class="col-4">
        <b><?php echo __('Authentication Key', 'simple-jwt-login'); ?></b>
    </div>
    <div class="col-4">
        <b><?php echo __('WordPress new user Role ( when new users are created )', 'simple-jwt-login'); ?></b>
    </div>
    <div class="col-4">
        <b><?php echo __(
            'Expiration date: YYYY-MM-DD HH:MM:SS ( Example: 2020-12-23 23:34:59)',
            'simple-jwt-login'
        ); ?></b>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
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
                ); ?>
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
                               placeholder="<?php echo $authCodeRolePlaceholder; ?>"
                        />
                        <input type="text"
                               name="auth_codes[expiration_date][]"
                               class="form-control"
                               value="<?php echo esc_attr($code->getExpirationDate()); ?>"
                               placeholder="<?php echo $authCodeExpirationDatePlaceholder; ?>"
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
                <?php
            }
            ?>
        </div>
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-12">
        <p>
            <?php echo sprintf(
                __(
                    ' %sAuthentication Key%s: '
                    . 'This is the actual code that you have to add in the request.',
                    'simple-jwt-login'
                ),
                '<b>',
                '</b>'
            ); ?>
        </p>
        <p>
            <?php echo sprintf(
                __(
                    "%sWordPress new User Role%s:' 
                . ' can be used when you want to create multiple user types with the create user endpoint.' 
                . ' If you leave it blank, the value configured in the 'Register Settings' will be used.",
                    'simple-jwt-login'
                ),
                '<b>',
                '</b>'
            );
?>
            <a href="https://wordpress.org/support/article/roles-and-capabilities/" target="_blank">
                <?php echo __('More details', 'simple-jwt-login'); ?>
            </a>
        </p>
        <p>
            <?php echo sprintf(
                __("%sExpiration Date%s: This allows you to set an expiration date for you auth codes."
                . " The format is `Y-M-D H:m:s'. Example : 2020-12-24 23:00:00"
                . ' If you leave it blank, it will never expired.', 'simple-jwt-login'),
                '<b>',
                '</b>'
);
?>
        </p>
    </div>
</div>

