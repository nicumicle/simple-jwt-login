<?php

use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\RouteService;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Allow Authentication', 'simple-jwt-login'); ?></h3>
        <div class="form-group">
            <input type="radio" id="allow_authentication_no" name="allow_authentication" class="form-control"
                   value="0"
				<?php
                echo $jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled() === false
                    ? 'checked'
                    : '';
                ?>
            />
            <label for="allow_authentication_no">
				<?php echo __('No', 'simple-jwt-login'); ?>
            </label>

            <input type="radio" id="allow_authentication_yes" name="allow_authentication" class="form-control"
                   value="1"
                <?php
                echo $jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled() === true
                    ? 'checked'
                    : '';
                ?>
            />
            <label for="allow_authentication_yes">
				<?php echo __('Yes', 'simple-jwt-login'); ?>
            </label>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Authentication Requires Auth Code', 'simple-jwt-login'); ?></h3>
        <div class="form-group">
            <input type="radio" id="require_auth_code_no" name="auth_requires_auth_code" class="form-control"
                   value="0"
                <?php echo $jwtSettings->getAuthenticationSettings()->isAuthKeyRequired() === false ? 'checked' : '' ?>
            />
            <label for="require_login_auth_no">
                <?php echo __('No', 'simple-jwt-login'); ?>
            </label>
            <input type="radio" id="require_auth_code_yes" name="auth_requires_auth_code" class="form-control"
                   value="1"
                <?php echo $jwtSettings->getAuthenticationSettings()->isAuthKeyRequired() === true ? 'checked' : '' ?>
            />
            <label for="require_login_auth_yes">
                <?php echo __('Yes', 'simple-jwt-login'); ?>
            </label>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Authentication Example URL', 'simple-jwt-login'); ?></h3>
        <p>
			<?php
            echo __(
                    'This route allows you to generate a JWT based on your WordPress email'
                . ' ( or WordPress username ) and Password.',
                    'simple-jwt-login'
                );
            ?>
        </p>
        <p class="text-muted">
            Parameters:<Br />
            <b>email</b> -> <?php echo __('to login with email', 'simple-jwt-login');?><br />
            <b>username</b> -> <?php echo __('to login with username', 'simple-jwt-login');?><br />
            <b>password</b> -> <?php echo __('your password', 'simple-jwt-login');?><br />
            <b>password_hash</b> -> <?php echo __('your hashed password from the database', 'simple-jwt-login');?><br />
        </p>
        <div class="generated-code">
            <span class="method">POST:</span>
            <span class="code">
                <?php
                $sampleUrlParams = [
                    'email'    => __('Email', 'simple-jwt-login'),
                    'password' => __('Password', 'simple-jwt-login')
                ];

                echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_ROUTE, $sampleUrlParams));
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
        <h3 class="section-title"><?php echo __('JWT Header parameters', 'simple-jwt-login'); ?></h3>
        <div id="authentication_header_data" class="authentication_jwt_container">
            <ul>
                <li>{</li>
                <li>
                    <ul>
                        <li>
                            <span class="checkbox"></span>
                            <span class="key">"alg"</span>
                            <span class="delimiter">:</span>
                            <span class="value">HS256</span>
                            <span class="line-separator">,</span>
                        </li>
                        <li>
                            <span class="checkbox"></span>
                            <span class="key">"typ"</span>
                            <span class="delimiter">:</span>
                            <span class="value">"JWT"</span>
                            <span class="line-separator"></span>
                        </li>
                    </ul>
                </li>
                <li>}</li>
            </ul>

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
                SettingsErrors::PREFIX_AUTHENTICATION,
                SettingsErrors::ERR_AUTHENTICATION_EMPTY_PAYLOAD
            ) === $errorCode
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
            <?php echo __('JWT Payload parameters', 'simple-jwt-login'); ?>
        </h3>
        <div id="authentication_payload_data" class="authentication_jwt_container">
            <ul>
                <li>{</li>
                <li>
                    <ul>
						<?php
                        foreach ($jwtSettings
                                     ->getAuthenticationSettings()
                                     ->getJwtPayloadParameters()
                                 as $parameterIndex => $parameter) {
                            $numberOfLines = count(
                                $jwtSettings
                                        ->getAuthenticationSettings()
                                        ->getJwtPayloadParameters()
                            ) - 1;
                            $lineSeparator = $numberOfLines === $parameterIndex
                                ? ''
                                : ',';
                            switch ($parameter) {
                                case AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT:
                                    $sampleValue = time();
                                    break;
                                case AuthenticationSettings::JWT_PAYLOAD_PARAM_SITE:
                                    $sampleValue = $jwtSettings->getWordPressData()->getSiteUrl();
                                    break;
                                case AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL:
                                    $sampleValue = 'useremail@domain.com';
                                    break;
                                case AuthenticationSettings::JWT_PAYLOAD_PARAM_ID:
                                    $sampleValue = 123;
                                    break;
                                case AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP:
                                    $sampleValue = time() + 60 * 60;
                                    break;
                                case AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME:
                                    $sampleValue = 'WordPresUser_login';
                                    break;
                                default:
                                    $sampleValue = '';
                            } ?>
                            <li>
                            <span class="checkbox">
                                <?php
                                if ($parameter !== AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT) {
                                    ?>
                                    <input
                                            type="checkbox"
                                            id="jwt_payload_<?php echo esc_attr($parameter);?>"
                                            name="jwt_payload[]"
                                            value="<?php echo esc_attr($parameter); ?>"
                                         <?php
                                         echo esc_html($jwtSettings->getAuthenticationSettings()->isPayloadDataEnabled($parameter) ? 'checked' : '')
                                            ?>
                                    />
	                                <?php
                                } ?>
                            </span>
                            <label class="bold" for="jwt_payload_<?php echo esc_attr($parameter);?>">
                                <span class="key">"<?php echo esc_html($parameter); ?>"</span>
                                <span class="delimiter">:</span>
                                <span class="value">"<?php echo esc_html($sampleValue); ?>"</span>
                                <span class="line-separator"><?php echo esc_html($lineSeparator); ?></span>
                            </label>
                            </li>
							<?php
                        }
                        ?>
                    </ul>
                </li>
                <li>}</li>
            </ul>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
			<?php echo __('Verify Signature', 'simple-jwt-login'); ?>
        </h3>
        <div id="authentication_signature" class="authentication_jwt_container">
            <ul>
                <li>HMACSHA256(</li>
                <li>
                    <ul>
                        <li> base64UrlEncode(header) + "." +</li>
                        <li> base64UrlEncode(payload),</li>
                        <li><b>JWT Decryption Key</b></li>
                    </ul>
                </li>
                <li>)</li>
            </ul>
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
                SettingsErrors::PREFIX_AUTHENTICATION,
                SettingsErrors::ERR_AUTHENTICATION_TTL
            ) === $errorCode
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
			<?php echo __('JWT time to live', 'simple-jwt-login') ?>
        </h3>
        <label>
			<?php echo __(
                'Specify the length of time (in minutes) that the token will be valid for.',
                'simple-jwt-login'
            ); ?>
        </label>
        <input
                type="text"
                name="jwt_auth_ttl"
                class="form-control" id="jwt_auth_ttl"
                value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAuthJwtTtl()); ?>"
                placeholder="<?php echo __('Number of minutes', 'simple-jwt-login') ?>"
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
                SettingsErrors::PREFIX_AUTHENTICATION,
                SettingsErrors::ERR_AUTHENTICATION_REFRESH_TTL_ZERO
            ) === $errorCode
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
			<?php echo __('Refresh time to live', 'simple-jwt-login') ?>
        </h3>
        <label for="jwt_login_by_paramter">
			<?php echo __(
                'Specify the length of time (in minutes) that the token can be refreshed within.'
                . ' I.E. The user can refresh their token within a 2 week window of the original token'
                . ' being created until they must re-authenticate.Defaults to 2 weeks',
                'simple-jwt-login'
            ); ?>
        </label>
        <input
                type="text"
                name="jwt_auth_refresh_ttl"
                class="form-control"
                id="jwt_auth_refresh_ttl"
                value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAuthJwtRefreshTtl()); ?>"
                placeholder="<?php echo __('Number of minutes', 'simple-jwt-login') ?>"
        />
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Refresh JWT URL Example', 'simple-jwt-login'); ?></h3>
        <p>
			<?php
            echo __(
                'This route is for refreshing expired tokens.'
                . ' It accept as a parameter an expired token, and returns a new valid JWT.',
                'simple-jwt-login'
            );
            ?>
        </p>
        <div class="generated-code">
            <span class="method">POST:</span>
            <span class="code">
                <?php
                $sampleUrlParams = [
                    $jwtSettings->getGeneralSettings()->getRequestKeyUrl() => 'YOUR_JWT',
                ];
                echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_REFRESH_ROUTE, $sampleUrlParams));
                ?>
            </span>
            <span class="copy-button">
                <button class="btn btn-secondary btn-xs">
                    <?php echo __('Copy', 'simple-jwt-login'); ?>
                </button>
            </span>
        </div>
        <p class="text-muted">
            * <?php echo __(
                    'JWT can be sent via URL, SESSION, COOKIE or HEADER.'
                . ' Please enable the ones you want in the \'General\' section.',
                    'simple-jwt-login'
                );
?>
        </p>
    </div>
</div>
<hr/>


<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Validate JWT URL Example', 'simple-jwt-login'); ?></h3>
        <p>
			<?php
            echo __(
    'This endpoint validates a JWT.'
                . ' If it is valid,it will return the WordPress user details and some JWT details.',
    'simple-jwt-login'
);
            ?>
        </p>
        <div class="generated-code">
            <span class="method">GET:</span>
            <span class="code">
                <?php
                $sampleUrlParams = [
                    $jwtSettings->getGeneralSettings()->getRequestKeyUrl() => 'YOUR_JWT',
                ];
                echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_VALIDATE_ROUTE, $sampleUrlParams));
                ?>
            </span>
            <span class="copy-button">
                <button class="btn btn-secondary btn-xs">
                    <?php echo __('Copy', 'simple-jwt-login'); ?>
                </button>
            </span>
        </div>
        <p class="text-muted">
            * <?php echo __(
                    'JWT can be sent via URL, SESSION, COOKIE or HEADER.'
                . ' Please enable the ones you want in the \'General\' section.',
                    'simple-jwt-login'
                );
?>
        </p>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Revoke JWT URL Example', 'simple-jwt-login'); ?></h3>
        <p>
            <?php
            echo __(
    'This endpoint revokes a JWT. If it is valid, it will be marked as invalid.',
    'simple-jwt-login'
);
            ?>
        </p>
        <div class="generated-code">
            <span class="method">POST:</span>
            <span class="code">
                <?php
                $sampleUrlParams = [
                    $jwtSettings->getGeneralSettings()->getRequestKeyUrl() => 'YOUR_JWT',
                ];
                echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_REVOKE, $sampleUrlParams));
                ?>
            </span>
            <span class="copy-button">
                <button class="btn btn-secondary btn-xs">
                    <?php echo __('Copy', 'simple-jwt-login'); ?>
                </button>
            </span>
        </div>
        <p class="text-muted">
            * <?php echo __(
                    'JWT can be sent via URL, SESSION, COOKIE or HEADER.'
                . ' Please enable the ones you want in the \'General\' section.',
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
                'Allow Authentication only from the following IP addresses',
                'simple-jwt-login'
            ); ?>:</h3>
        <div class="form-group">
            <input type="text" id="auth_ip" name="auth_ip" class="form-control"
                   value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAllowedIps()); ?>"
                   placeholder="<?php echo __('Enter IP here', 'simple-jwt-login'); ?>"/>
            <p class="text-muted">
                <?php echo __("If you want to add more IP's, separate them by comma", 'simple-jwt-login'); ?>.
                <br/>
                <?php echo __('Leave blank to allow all IP addresses', 'simple-jwt-login'); ?>.
            </p>
        </div>
    </div>
</div>
<hr />