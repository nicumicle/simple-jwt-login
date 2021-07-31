<?php

use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\RouteService;

if ( ! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php
            echo __('Allow Reset Password', 'simple-jwt-login'); ?></h3>
        <div class="form-group">
            <input type="radio" id="allow_reset_password_no" name="allow_reset_password" class="form-control"
                   value="0"
                <?php
                echo $jwtSettings->getResetPasswordSettings()->isResetPasswordEnabled() === false
                    ? 'checked'
                    : '';
                ?>
            />
            <label for="allow_register_no">
                <?php
                echo __('No', 'simple-jwt-login'); ?>
            </label>

            <input type="radio" id="allow_reset_password_yes" name="allow_reset_password" class="form-control"
                   value="1"
                <?php
                echo
                $jwtSettings->getResetPasswordSettings()->isResetPasswordEnabled()
                    ? 'checked'
                    : '';
                ?>
            />
            <label for="allow_register_yes">
                <?php
                echo __('Yes', 'simple-jwt-login'); ?>
            </label>
            <br/>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php
            echo __('Reset Password Requires Auth Code', 'simple-jwt-login'); ?>
        </h3>
        <div class="form-group">
            <input type="radio" id="reset_password_auth_code_no" name="reset_password_requires_auth_code"
                   class="form-control"
                   value="0"
                <?php
                echo $jwtSettings->getResetPasswordSettings()->isAuthKeyRequired() === false ? 'checked' : '' ?>
            />
            <label for="require_login_auth_no">
                <?php
                echo __('No', 'simple-jwt-login'); ?>
            </label>
            <input type="radio" id="reset_password_auth_code_yes" name="reset_password_requires_auth_code"
                   class="form-control"
                   value="1"
                <?php
                echo $jwtSettings->getResetPasswordSettings()->isAuthKeyRequired() === true ? 'checked' : '' ?>
            />
            <label for="require_login_auth_yes">
                <?php
                echo __('Yes', 'simple-jwt-login'); ?>
            </label>
        </div>
    </div>
</div>
<hr/>


<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php
            echo __('Send Reset Password Example URL', 'simple-jwt-login'); ?></h3>
        <p>
            <?php
            echo __(
                'This route sends an email to a specific email address in order to reset the password.',
                'simple-jwt-login'
            );
            ?>
        </p>
        <p class="text-muted">
            Parameters:<Br/>
            <b>email</b><span class="required">*</span> : The email address that needs reset passowrd<Br/>
            <br/>
            An email with the reset password link will be sent to this email address.
        </p>
        <div class="generated-code">
            <span class="method">POST:</span>
            <span class="code">
                <?php
                $sampleUrlParams = [
                    'email' => __('Email', 'simple-jwt-login'),
                ];

                if ($jwtSettings->getResetPasswordSettings()->isAuthKeyRequired()) {
                    $sampleUrlParams[$jwtSettings->getAuthCodesSettings()->getAuthCodeKey()] = __(
                        'AUTH_KEY_VALUE',
                        'simple-jwt-login'
                    );
                }
                echo $jwtSettings->generateExampleLink(RouteService::RESET_PASSWORD_LINK, $sampleUrlParams);
                ?>
            </span>
            <span class="copy-button">
                <button class="btn btn-secondary btn-xs">
                    <?php
                    echo __('Copy', 'simple-jwt-login'); ?>
                </button>
            </span>
        </div>
    </div>
</div>
<br/>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php
            echo __('Reset password flow', 'simple-jwt-login'); ?>
        </h3>
        <ul>
            <li>
                <input type="radio"
                       value="<?php
                       echo ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB; ?>"
                    <?php
                    echo $jwtSettings->getResetPasswordSettings()->getFlowType() === ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB ? 'checked="checked"' : ''; ?>
                       name="jwt_reset_password_flow"
                       class="jwt_reset_password_flow"
                       id="jwt_reset_password_flow_db">
                <label for="jwt_reset_password_flow_db">
                    <?php
                    echo __(
                        'Do not send any email, just save reset code in the database',
                        'simple-jwt-login'
                    );
                    ?>
                </label>
            </li>
            <li>
                <input type="radio"
                       value="<?php
                       echo ResetPasswordSettings::FLOW_SEND_DEFAULT_WP_EMAIL; ?>"
                    <?php
                    echo $jwtSettings->getResetPasswordSettings()->getFlowType() === ResetPasswordSettings::FLOW_SEND_DEFAULT_WP_EMAIL ? 'checked="checked"' : ''; ?>
                       name="jwt_reset_password_flow"
                       class="jwt_reset_password_flow"
                       id="jwt_reset_password_flow_wordpress">
                <label for="jwt_reset_password_flow_wordpress">
                    <?php
                    echo __(
                        'Send the default WordPress reset password email',
                        'simple-jwt-login'
                    );
                    ?>
                </label>
            </li>
            <li>
                <input type="radio"
                       value="<?php
                       echo ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL; ?>"
                    <?php
                    echo $jwtSettings->getResetPasswordSettings()->getFlowType() === ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL ? 'checked="checked"' : ''; ?>
                       name="jwt_reset_password_flow"
                       id="jwt_reset_password_flow_custom"
                       class="jwt_reset_password_flow">
                <label for="jwt_reset_password_flow_custom">
                    <?php
                    echo __(
                        'Send custom email',
                        'simple-jwt-login'
                    );
                    ?>
                </label>
            </li>
        </ul>
    </div>
</div>
<div class="row" id="simple_jwt_reset_password_email_container">
    <div class="col-md-12">
        <div class="jwt_sub_container">
            <h4 class="sub-section-title">Email Subject</h4>
            <input type="text"
                   name="jwt_email_subject"
                   class="form-control"
                   placeholder="Email Subject"
                   value="<?php
                   echo $jwtSettings->getResetPasswordSettings()->getResetPasswordEmailSubject(); ?>"
            />
            <br/>
            <h4 class="sub-section-title">Email body</h4>
            <textarea class="form-control" name="jwt_reset_password_email_body" id="reset_password_email_body"
                      placeholder="Email Content"><?php
                echo htmlentities($jwtSettings->getResetPasswordSettings()->getResetPasswordEmailBody()); ?></textarea>
            <br/>
            <h4 class="sub-section-title">Email type</h4>
            <ul>
                <li>
                    <input type="radio"
                           name="jwt_email_type"
                           id="jwt_email_type_plain_text"
                           value="0"
                        <?php
                        echo $jwtSettings->getResetPasswordSettings()->getResetPasswordEmailType(
                        ) === 0 ? 'checked="checked"' : ''; ?>
                    />
                    <label for="jwt_email_type_plain_text">
                        Plain text
                    </label>
                </li>
                <li>
                    <input type="radio"
                           name="jwt_email_type"
                           id="jwt_email_type_html"
                           value="1"
                        <?php
                        echo $jwtSettings->getResetPasswordSettings()->getResetPasswordEmailType(
                        ) === 1 ? 'checked="checked"' : ''; ?>
                    />
                    <label for="jwt_email_type_html">
                        HTML
                    </label>
                </li>
            </ul>
            <br/>
            For the email content, you need to add <code class="code">{{CODE}}</code> as a variable.
            <br/>
            <br/>
            <b>Available variables</b>:
            <ul>
                <?php
                foreach ($jwtSettings->getResetPasswordSettings()->getEmailContentVariables() as $variable => $text)
                {
                    ?>
                    <li>
                        <code class="code"><?php echo $variable;?></code>:
                        <?php echo $text;?>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
    </div>
</div>
<hr/>


<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php
            echo __('Change user password', 'simple-jwt-login'); ?></h3>
        <p>
            <?php
            echo __(
                'This route changes the user password. It requires the reset password code that it was received on email and the new password.',
                'simple-jwt-login'
            );
            ?>
        </p>
        <p class="text-muted">
            Parameters:<Br/>
            <b>email</b><span class="required">*</span> : The email address that needs reset passowrd<Br/>
            <b>code</b><span class="required">*</span> : The code received on email<Br/>
            <b>new_passord</b><span class="required">*</span> : New password for the user<Br/>
            <br/>
            An email with the reset password link will be sent to this email address.
        </p>
        <div class="generated-code">
            <span class="method">PUT:</span>
            <span class="code">
                <?php
                $sampleUrlParams = [
                    'email'        => __('Email', 'simple-jwt-login'),
                    'code'         => __('Code', 'simple-jwt-login'),
                    'new_password' => __('New password', 'simple-jwt-login'),
                ];

                if ($jwtSettings->getResetPasswordSettings()->isAuthKeyRequired()) {
                    $sampleUrlParams[$jwtSettings->getAuthCodesSettings()->getAuthCodeKey()] = __(
                        'AUTH_KEY_VALUE',
                        'simple-jwt-login'
                    );
                }
                echo $jwtSettings->generateExampleLink(RouteService::RESET_PASSWORD_LINK, $sampleUrlParams);
                ?>
            </span>
            <span class="copy-button">
                <button class="btn btn-secondary btn-xs">
                    <?php
                    echo __('Copy', 'simple-jwt-login'); ?>
                </button>
            </span>
        </div>
    </div>
</div>
<hr/>