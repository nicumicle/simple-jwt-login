<?php

use SimpleJWTLogin\Helpers\Jwt\JwtKeyWpConfig;
use SimpleJWTLogin\Libraries\JWT;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\SettingsErrors;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
?>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
            <?php
            echo isset($errorCode)
            && SettingsErrors::generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_EMPTY_NAMESPACE) === $errorCode
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
            <?php echo __('Route Namespace', 'simple-jwt-login'); ?>
        </h3>
        <div class="form-group">
            <input type="text" name="route_namespace" value="<?php echo $jwtSettings->getRouteNamespace(); ?>"
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
                    value="<?php echo SimpleJWTLoginSettings::DECRYPTION_SOURCE_SETTINGS;?>"
                    <?php echo $jwtSettings->getDecryptionSource() === SimpleJWTLoginSettings::DECRYPTION_SOURCE_SETTINGS ? 'selected' : ''?>
                >
                    Plugin Settings
                </option>
                <option
                    value="<?php echo SimpleJWTLoginSettings::DECRYPTION_SOURCE_CODE;?>"
                    <?php echo $jwtSettings->getDecryptionSource() === SimpleJWTLoginSettings::DECRYPTION_SOURCE_CODE ? 'selected' : ''?>
                >
                    Code
                </option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><span class="step-number">2</span> <?php echo __('JWT Decrypt Algorithm', 'simple-jwt-login'); ?></h3>
        <div class="info"><?php echo __('The algorithm that should be used to verify the JWT signature.',
                'simple-jwt-login'); ?></div>
        <div class="form-group">
            <select name="jwt_algorithm" class="form-control" id="simple-jwt-login-jwt-algorithm">
                <?php
                foreach (JWT::$supported_algs as $alg => $arr) {
                    $selected = $jwtSettings->getJWTDecryptAlgorithm() === $alg
                        ? 'selected'
                        : '';
                    echo "<option value=\"" . $alg . "\" " . $selected . ">" . $alg . "</option>\n";
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
                    SettingsErrors::generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_PRIVATE_KEY_MISSING_FROM_CODE_RS) === $errorCode
                    || SettingsErrors::generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_PRIVATE_KEY_NOT_PRESENT_IN_CODE_HS) === $errorCode
                    || SettingsErrors::generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_MISSING_PRIVATE_AND_PUBLIC_KEY) === $errorCode
                    || SettingsErrors::generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_DECRYPTION_KEY_REQUIRED) === $errorCode
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
                       value="<?php echo $jwtSettings->getDecryptionKey(); ?>"
                       placeholder="<?php echo __('JWT decryption key here', 'simple-jwt-login'); ?>"
                />
                <div class="input-group-addon">
                    <a href="javascript:void(0)"
                       onclick="showDecryptionKey()"
                       class="toggle_key_button"
                       title="<?php echo __('Toggle decryption key', 'simple-jwt-login'); ?>"
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
                    <?php echo($jwtSettings->getDecryptionKeyIsBase64Encoded() ? 'checked="checked"' : '') ?>

                />
                <label for="decryption_key_base64">
                    <?php echo __('JWT Decryption Key is base64 encoded', 'simple-jwt-login'); ?>
                </label>
            </div>
        </div>

        <div class="form-group decryption-textarea-group">
            <label for="simple-jwt-login-public-key">Public Key</label>
            <textarea
                    class="form-control"
                    id="simple-jwt-login-public-key"
                    rows="6"
                    name="decryption_key_public"
            ><?php echo $jwtSettings->getDecryptionKeyPublic(); ?></textarea>
        </div>
        <div class="form-group  decryption-textarea-group">
            <label for="simple-jwt-login-private-key">Private Key</label>
            <textarea
                    class="form-control"
                    id="simple-jwt-login-private-key"
                    rows="6"
                    name="decryption_key_private"
            ><?php echo $jwtSettings->getDecryptionKeyPrivate(); ?></textarea>
        </div>

        <div class="decryption-code-info">
            You have to defined in your code the following constants ( for example in  wp-config.php ) :
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
                    SettingsErrors::generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_GET_JWT_FROM) === $errorCode
                  || SettingsErrors::generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_REQUEST_KEYS) === $errorCode
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
        1. <span class="simple-jwt-request-parameter-label">URL parameter</span>
        <input type="text" name="request_keys[url]" required="required" style="display: inline-block"  placeholder="Parameter name" value="<?php echo $jwtSettings->getRequestKeyUrl();?>"/>
    </div>
    <div class="col-md-2">
        <select name="request_jwt_url" class="form-control onOff">
            <option value="0" <?php echo $jwtSettings->getJwtFromURLEnabled() === false ? "selected" : ""; ?> >
                <?php echo __('Off', 'simple-jwt-login'); ?>
            </option>
            <option value="1" <?php echo $jwtSettings->getJwtFromURLEnabled() === true ? "selected" : ""; ?>>
                <?php echo __('On', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
    <div class="col-md-5">
        <div class="code">&<?php echo $jwtSettings->getRequestKeyUrl();?>=<b>YOUR JWT HERE</b></div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        2. <span class="simple-jwt-request-parameter-label">SESSION</span>
        <input type="text" name="request_keys[session]" required="required"  style="display: inline-block"  placeholder="Parameter name" value="<?php echo $jwtSettings->getRequestKeySession();?>" />
    </div>
    <div class="col-md-2">
        <select name="request_jwt_session" class="form-control onOff">
            <option value="0" <?php echo $jwtSettings->getJwtFromSessionEnabled() === false ? "selected" : ""; ?>>
                <?php echo __('Off', 'simple-jwt-login'); ?>
            </option>
            <option value="1" <?php echo $jwtSettings->getJwtFromSessionEnabled() === true ? "selected" : ""; ?>>
                <?php echo __('On', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
    <div class="col-md-5">
        <div class="code">$_SESSION['<b><?php echo $jwtSettings->getRequestKeySession();?></b>']</div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        3. <span class="simple-jwt-request-parameter-label">COOKIE</span>
        <input type="text" name="request_keys[cookie]" required="required" style="display: inline-block" placeholder="Parameter name" value="<?php echo $jwtSettings->getRequestKeyCookie();?>"/>
    </div>
    <div class="col-md-2">
        <select name="request_jwt_cookie" class="form-control onOff">
            <option value="0" <?php echo $jwtSettings->getJwtFromCookieEnabled() === false ? "selected" : ""; ?>>
                <?php echo __('Off', 'simple-jwt-login'); ?>
            </option>
            <option value="1" <?php echo $jwtSettings->getJwtFromCookieEnabled() === true ? "selected" : ""; ?>>
                <?php echo __('On', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
    <div class="col-md-5">
        <div class="code">$_COOKIE['<b><?php echo $jwtSettings->getRequestKeyCookie();?></b>']</div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        4. <span class="simple-jwt-request-parameter-label">Header</span>
        <input type="text" name="request_keys[header]" required="required" placeholder="Parameter name" style="display: inline-block" value="<?php echo $jwtSettings->getRequestKeyHeader();?>"/>
    </div>
    <div class="col-md-2">
        <select name="request_jwt_header" class="form-control onOff">
            <option value="0" <?php echo $jwtSettings->getJwtFromHeaderEnabled() === false ? "selected" : ""; ?>>
                <?php echo __('Off', 'simple-jwt-login'); ?>
            </option>
            <option value="1" <?php echo $jwtSettings->getJwtFromHeaderEnabled() === true ? "selected" : ""; ?>>
                <?php echo __('On', 'simple-jwt-login'); ?>
            </option>
        </select>
    </div>
    <div class="col-md-5">
        <div class="code"><?php echo $jwtSettings->getRequestKeyHeader();?>: Bearer <b>YOUR_JWT_HERE</b></div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <p class="text-muted">
            * <?php echo __('If the JWT is present in multiple places, the higher number of the option overwrites the smaller number.',
                'simple-jwt-login'); ?>
        </p>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <input type="checkbox" name="api_middleware[enabled]"
               value="1" <?php echo $jwtSettings->isMiddlewareEnabled() ? 'checked="checked"' : "" ?> />
        <span class="beta">beta</span>
        All WordPress endpoints checks for JWT authentication <Br/>
        <p class="text-muted">
            * If the JWT is provided on other endpoints, the plugin will try to authenticate the user from the JWT in
            order to perform that API call.
        </p>
    </div>
</div>