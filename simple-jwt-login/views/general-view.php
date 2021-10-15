<?php

use SimpleJWTLogin\Helpers\Jwt\JwtKeyWpConfig;
use SimpleJWTLogin\Libraries\JWT;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
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
        <h3 class="section-title">
            <?php
            echo isset($errorCode)
            && $settingsErrors->generateCode(
                SettingsErrors::PREFIX_GENERAL,
                SettingsErrors::ERR_GENERAL_EMPTY_NAMESPACE
            ) === $errorCode
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
            <?php echo __('Route Namespace', 'simple-jwt-login'); ?> <span class="required">*</span>
        </h3>
        <div class="form-group">
            <input type="text" name="route_namespace" value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRouteNamespace()); ?>"
                   class="form-control"
                   placeholder="<?php echo __('Default route namespace', 'simple-jwt-login'); ?>"
            />
        </div>
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-12">
        <h2 class="section-title">JWT Signature</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <h2 class="section-title" ><span class="step-number">1</span> Decryption key source</h2>
            <select id="decryption_source" name="decryption_source" class="form-control">
                <option
                    value="<?php echo GeneralSettings::DECRYPTION_SOURCE_SETTINGS;?>"
                    <?php
                    echo $jwtSettings->getGeneralSettings()->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_SETTINGS
                        ? 'selected'
                        : ''
                    ?>
                >
                    Plugin Settings
                </option>
                <option
                    value="<?php echo GeneralSettings::DECRYPTION_SOURCE_CODE;?>"
                    <?php echo $jwtSettings->getGeneralSettings()->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_CODE
                        ? 'selected'
                        : ''
                    ?>
                >
                    Code
                </option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
            <span class="step-number">2</span>
            <spam> </spam>
            <?php echo __('JWT Decrypt Algorithm', 'simple-jwt-login'); ?>
        </h3>
        <div class="info"><?php echo __(
                        'The algorithm that should be used to verify the JWT signature.',
                        'simple-jwt-login'
                    ); ?></div>
        <div class="form-group">
            <select name="jwt_algorithm" class="form-control" id="simple-jwt-login-jwt-algorithm">
                <?php
                foreach (JWT::$supportedAlgs as $alg => $arr) {
                    $selected = $jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm() === $alg
                        ? 'selected'
                        : '';
                    echo "<option value=\"" . esc_attr($alg) . "\" " . $selected . ">" . esc_html($alg) . "</option>\n";
                }
                ?>
            </select>

        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
            <span class="step-number">3</span>
            <?php
            echo isset($errorCode)
            && (
                $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_GENERAL,
                    SettingsErrors::ERR_GENERAL_PRIVATE_KEY_MISSING_FROM_CODE_RS
                ) === $errorCode
                    || $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_GENERAL,
                        SettingsErrors::ERR_GENERAL_PRIVATE_KEY_NOT_PRESENT_IN_CODE_HS
                    ) === $errorCode
                    ||  $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_GENERAL,
                        SettingsErrors::ERR_GENERAL_MISSING_PRIVATE_AND_PUBLIC_KEY
                    ) === $errorCode
                    ||  $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_GENERAL,
                        SettingsErrors::ERR_GENERAL_DECRYPTION_KEY_REQUIRED
                    ) === $errorCode
            )
                ? '<span class="simple-jwt-error">!</span>'
                : '';
            ?>
            <?php echo __('JWT Decryption Key', 'simple-jwt-login'); ?>
        </h3>
        <div class="info">
            <?php echo __('JWT decryption signature | JWT Verify Signature', 'simple-jwt-login'); ?>
        </div>
        <br />

        <div class="form-group decryption-input-group">
            <div class="input-group" id="decryption_key_container">
                <input type="password" name="decryption_key" class="form-control"
                       id="decryption_key"
                       value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getDecryptionKey()); ?>"
                       placeholder="<?php echo __('JWT decryption key here', 'simple-jwt-login'); ?>"
                />
                <div class="input-group-addon">
                    <a href="javascript:void(0)"
                       onclick="showDecryptionKey()"
                       class="toggle_key_button"
                       title="<?php
                        echo __('Toggle decryption key', 'simple-jwt-login');
                        ?>"
                    >
                        <i class="toggle-image" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <div class="input-group" style="margin-top:10px">
                <input
                        type="checkbox"
                        name="decryption_key_base64"
                        id="decryption_key_base64"
                        value="1"
                        style="margin-top:1px;"
                    <?php echo $jwtSettings->getGeneralSettings()->isDecryptionKeyBase64Encoded() ? 'checked="checked"' : ''; ?>

                />
                <label for="decryption_key_base64">
                    <?php echo __('JWT Decryption Key is base64 encoded', 'simple-jwt-login'); ?>
                </label>
            </div>
        </div>

        <div class="form-group decryption-textarea-group">
            <label for="simple-jwt-login-public-key">Public Key <span class="required">*</span></label>
            <textarea
                    class="form-control"
                    id="simple-jwt-login-public-key"
                    rows="6"
                    name="decryption_key_public"
            ><?php echo esc_html($jwtSettings->getGeneralSettings()->getDecryptionKeyPublic()); ?></textarea>
        </div>
        <div class="form-group  decryption-textarea-group">
            <label for="simple-jwt-login-private-key">Private Key <span class="required">*</span></label>
            <textarea
                    class="form-control"
                    id="simple-jwt-login-private-key"
                    rows="6"
                    name="decryption_key_private"
            ><?php echo esc_html($jwtSettings->getGeneralSettings()->getDecryptionKeyPrivate()); ?></textarea>
        </div>

        <div class="decryption-code-info">
            <?php echo __('You have to defined in your code the following constants','simple-jwt-login');?>
            ( <?php echo __('for example in wp-config.php','simple-jwt-login');?> ) :
            <br />
            <code class="define_private_key" style="display: block">
                define('<b><?php echo JwtKeyWpConfig::SIMPLE_JWT_PRIVATE_KEY;?></b>','MY_SECRET_KEY');<br />
            </code>
            <code class="define_public_key" style="display: block">
                define('<b><?php echo JwtKeyWpConfig::SIMPLE_JWT_PUBLIC_KEY;?></b>','MY_SECRET_KEY_2');<br />
            </code>
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
                    SettingsErrors::PREFIX_GENERAL,
                    SettingsErrors::ERR_GENERAL_GET_JWT_FROM
                ) === $errorCode
                  ||  $settingsErrors->generateCode(
                      SettingsErrors::PREFIX_GENERAL,
                      SettingsErrors::ERR_GENERAL_REQUEST_KEYS
                  ) === $errorCode
            )
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
            <?php echo __('Get JWT token from', 'simple-jwt-login'); ?>
        </h3>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        1. <span class="simple-jwt-request-parameter-label">REQUEST</span>
        <input
                type="text"
                name="request_keys[url]"
                required="required"
                style="display: inline-block"
                placeholder="<?php echo __('Parameter name', 'simple-jwt-login');?>"
                value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeyUrl());?>"
        />
    </div>
    <div class="col-md-2">
        <select name="request_jwt_url" class="form-control onOff">
            <option value="0" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromURLEnabled() === false ? "selected" : ""; ?> >
                <?php echo __('Off', 'simple-jwt-login'); ?>
            </option>
            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromURLEnabled() === true ? "selected" : ""; ?>>
                <?php echo __('On', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
    <div class="col-md-5">
        <div class="code">&<?php echo esc_html($jwtSettings->getGeneralSettings()->getRequestKeyUrl());?>=<b>YOUR JWT HERE</b></div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        2. <span class="simple-jwt-request-parameter-label">SESSION</span>
        <input
                type="text"
                name="request_keys[session]"
                required="required"
                style="display: inline-block"
                placeholder="Parameter name"
                value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeySession());?>"
        />
    </div>
    <div class="col-md-2">
        <select name="request_jwt_session" class="form-control onOff">
            <option value="0" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled() === false ? "selected" : ""; ?>>
                <?php echo __('Off', 'simple-jwt-login'); ?>
            </option>
            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled() === true ? "selected" : ""; ?>>
                <?php echo __('On', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
    <div class="col-md-5">
        <div class="code">$_SESSION['<b><?php echo esc_html($jwtSettings->getGeneralSettings()->getRequestKeySession());?></b>']</div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        3. <span class="simple-jwt-request-parameter-label">COOKIE</span>
        <input
                type="text"
                name="request_keys[cookie]"
                required="required"
                style="display: inline-block"
                placeholder="Parameter name"
                value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeyCookie());?>"
        />
    </div>
    <div class="col-md-2">
        <select name="request_jwt_cookie" class="form-control onOff">
            <option value="0" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromCookieEnabled() === false ? "selected" : ""; ?>>
                <?php echo __('Off', 'simple-jwt-login'); ?>
            </option>
            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromCookieEnabled() === true ? "selected" : ""; ?>>
                <?php echo __('On', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
    <div class="col-md-5">
        <div class="code">$_COOKIE['<b><?php echo esc_html($jwtSettings->getGeneralSettings()->getRequestKeyCookie());?></b>']</div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        4. <span class="simple-jwt-request-parameter-label">Header</span>
        <input
                type="text"
                name="request_keys[header]"
                required="required"
                placeholder="<?php echo __('Parameter name', 'simple-jwt-login');?>"
                style="display: inline-block"
                value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeyHeader());?>"
        />
    </div>
    <div class="col-md-2">
        <select name="request_jwt_header" class="form-control onOff">
            <option value="0" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromHeaderEnabled() === false ? "selected" : ""; ?>>
                <?php echo __('Off', 'simple-jwt-login'); ?>
            </option>
            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromHeaderEnabled() === true ? "selected" : ""; ?>>
                <?php echo __('On', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
    <div class="col-md-5">
        <div class="code"><?php echo esc_html($jwtSettings->getGeneralSettings()->getRequestKeyHeader());?>: Bearer <b>YOUR_JWT_HERE</b></div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <p class="text-muted">
            * <?php echo __(
                'If the JWT is present in multiple places,'
                . ' the higher number of the option overwrites the smaller number.',
                'simple-jwt-login'
            ); ?>
        </p>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <input type="checkbox" name="api_middleware[enabled]"
               value="1" <?php echo $jwtSettings->getGeneralSettings()->isMiddlewareEnabled() ? 'checked="checked"' : "" ?> />
        <span class="beta">beta</span>
        <?php echo __('All WordPress endpoints checks for JWT authentication','simple-jwt-login');?>
        <br/>
        <p class="text-muted">
            * <?php echo __('If the JWT is provided on other endpoints, the plugin will try to authenticate the user from the JWT in
            order to perform that API call.','simple-jwt-login');?>
        </p>
    </div>
</div>