<?php

use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\RouteService;

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
        <span class="dashicons dashicons-admin-network"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Allow JWT Authentication', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Allow users to authenticate and receive JWT tokens via API endpoints.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_authentication" value="0"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_authentication" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled() === true ? 'checked' : ''; ?>
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
            <h3 class="sjl-gen-card-title"><?php echo __('Require Authentication Code for JWT Generation', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('If enabled, an additional authentication code must be provided to generate JWT tokens.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="auth_requires_auth_code" value="0"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isAuthKeyRequired() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Not required', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="auth_requires_auth_code" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isAuthKeyRequired() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Required', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-rest-api"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('JWT Generation Endpoint', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Generate a JWT using WordPress credentials. Pass one of the credential parameters along with a password.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-code-block" style="margin-bottom: 16px;">
            <p class="sjl-gen-code-block-intro"><?php echo __('Accepted request parameters:', 'simple-jwt-login'); ?></p>
            <div class="sjl-gen-params-table">
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip">email</code>
                    <span class="sjl-gen-card-desc"><?php echo __('Login with email address', 'simple-jwt-login'); ?></span>
                </div>
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip">username</code>
                    <span class="sjl-gen-card-desc"><?php echo __('Login with WordPress username', 'simple-jwt-login'); ?></span>
                </div>
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip">login</code>
                    <span class="sjl-gen-card-desc"><?php echo __('Login with username or email', 'simple-jwt-login'); ?></span>
                </div>
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip">password</code>
                    <span class="sjl-gen-card-desc"><?php echo __('Your account password', 'simple-jwt-login'); ?></span>
                </div>
                <div class="sjl-gen-param-def">
                    <code class="sjl-gen-var-chip">password_hash</code>
                    <span class="sjl-gen-card-desc"><?php echo __('Hashed password from the database', 'simple-jwt-login'); ?></span>
                </div>
            </div>
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo __('Endpoint examples:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_ROUTE, [
                        'email'    => __('Email', 'simple-jwt-login'),
                        'password' => __('Password', 'simple-jwt-login'),
                    ]));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo __('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
            <p class="sjl-gen-card-desc" style="margin: 6px 0 6px 2px;"><strong><?php echo __('OR', 'simple-jwt-login'); ?></strong></p>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_ROUTE, [
                        'username' => __('Username', 'simple-jwt-login'),
                        'password' => __('Password', 'simple-jwt-login'),
                    ]));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo __('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
            <p class="sjl-gen-card-desc" style="margin: 6px 0 6px 2px;"><strong><?php echo __('OR', 'simple-jwt-login'); ?></strong></p>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_ROUTE, [
                        'login'    => __('Username_or_email', 'simple-jwt-login'),
                        'password' => __('Password', 'simple-jwt-login'),
                    ]));
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
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Authentication Options', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Additional settings for the authentication process.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="auth_password_base64" id="auth_password_base64" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isAuthPasswordBase64Encoded()
                        ? esc_html('checked="checked"')
                        : '';
                    ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="auth_password_base64" class="sjl-gen-feature-label">
                    <?php echo __('Authentication password / passhash is base64 encoded', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo __('Enable this if the password or password hash sent in the request is base64 encoded.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-editor-code"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('JWT Header Configuration', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('The standard header included in generated JWT tokens.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
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

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-editor-ul"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                echo isset($errorCode)
                && $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_EMPTY_PAYLOAD
                ) === $errorCode
                    ? '<span class="simple-jwt-error">!</span> '
                    : '';
                ?>
                <?php echo __('JWT Payload Configuration', 'simple-jwt-login'); ?>
                <span class="required">*</span>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Select which user data to include in the JWT payload.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div id="authentication_payload_data" class="authentication_jwt_container">
            <ul>
                <li>{</li>
                <li>
                    <ul>
                        <?php
                        $payloadParameters = $jwtSettings->getAuthenticationSettings()->getJwtPayloadParameters();
                        sort($payloadParameters, SORT_ASC);
                        foreach ($payloadParameters as $parameterIndex => $parameter) {
                            $numberOfLines = count($payloadParameters) - 1;
                            $lineSeparator = $numberOfLines === $parameterIndex ? '' : ',';
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
                                case AuthenticationSettings::JWT_PAYLOAD_PARAM_ISS:
                                    $sampleValue = $jwtSettings->getAuthenticationSettings()->getAuthIss();
                                    break;
                                default:
                                    $sampleValue = '';
                            }
                            ?>
                            <li>
                                <span class="checkbox">
                                    <?php if ($parameter !== AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT) { ?>
                                        <input
                                            type="checkbox"
                                            id="jwt_payload_<?php echo esc_attr($parameter); ?>"
                                            name="jwt_payload[]"
                                            value="<?php echo esc_attr($parameter); ?>"
                                            <?php echo esc_html(
                                                $jwtSettings
                                                    ->getAuthenticationSettings()
                                                    ->isPayloadDataEnabled($parameter)
                                                    ? 'checked'
                                                    : ''
                                            ) ?>
                                        />
                                    <?php } ?>
                                </span>
                                <label class="bold" for="jwt_payload_<?php echo esc_attr($parameter); ?>">
                                    <span class="key">"<?php echo esc_html($parameter); ?>"</span>
                                    <span class="delimiter">:</span>
                                    <span class="value">"<?php echo esc_html($sampleValue); ?>"</span>
                                    <span class="line-separator"><?php echo esc_html($lineSeparator); ?></span>
                                </label>
                            </li>
                        <?php } ?>
                    </ul>
                </li>
                <li>}</li>
            </ul>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('JWT Signature Verification', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('How the JWT signature is verified for authenticity.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
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

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-clock"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                $hasTtlError = isset($errorCode) && (
                    $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTHENTICATION,
                        SettingsErrors::ERR_AUTHENTICATION_TTL
                    ) === $errorCode
                    || $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTHENTICATION,
                        SettingsErrors::ERR_AUTHENTICATION_REFRESH_TTL_ZERO
                    ) === $errorCode
                );
                if ($hasTtlError) {
                    echo '<span class="simple-jwt-error">!</span> ';
                }
                ?>
                <?php echo __('Token Lifetime', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Configure how long generated JWT tokens are valid and how long they can be refreshed.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">1</div>
            <div class="sjl-gen-step-content">
                <label class="sjl-gen-step-label" for="jwt_auth_ttl">
                    <?php echo __('JWT Expiration Time', 'simple-jwt-login'); ?>
                    <span class="required">*</span>
                </label>
                <p class="sjl-gen-step-desc">
                    <?php echo __('Specify the length of time (in minutes) that the token will be valid for.', 'simple-jwt-login'); ?>
                </p>
                <input type="text" name="jwt_auth_ttl" id="jwt_auth_ttl"
                       class="form-control sjl-gen-input-medium"
                       value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAuthJwtTtl()); ?>"
                       placeholder="<?php echo __('Number of minutes', 'simple-jwt-login'); ?>"
                />
            </div>
        </div>

        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">2</div>
            <div class="sjl-gen-step-content">
                <label class="sjl-gen-step-label" for="jwt_auth_refresh_ttl">
                    <?php echo __('JWT Refresh Window', 'simple-jwt-login'); ?>
                    <span class="required">*</span>
                </label>
                <p class="sjl-gen-step-desc">
                    <?php echo __('Specify the length of time (in minutes) that the token can be refreshed within. The user can refresh their token within this window of the original token being created until they must re-authenticate. Defaults to 2 weeks.', 'simple-jwt-login'); ?>
                </p>
                <input type="text" name="jwt_auth_refresh_ttl" id="jwt_auth_refresh_ttl"
                       class="form-control sjl-gen-input-medium"
                       value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAuthJwtRefreshTtl()); ?>"
                       placeholder="<?php echo __('Number of minutes', 'simple-jwt-login'); ?>"
                />
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-site"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('JWT Issuer (iss)', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Specify the issuer claim included in generated JWT tokens.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <label class="sjl-gen-field-label" for="jwt_auth_iss">
            <?php echo __('Issuer value (iss payload claim)', 'simple-jwt-login'); ?>
        </label>
        <input type="text" name="jwt_auth_iss" id="jwt_auth_iss"
               class="form-control sjl-gen-input-medium"
               value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAuthIss()); ?>"
               placeholder="<?php echo __('Default issuer', 'simple-jwt-login'); ?>"
        />
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-update"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Token Management Endpoints', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Endpoints for refreshing, validating, and revoking JWT tokens. JWT can be sent via URL, SESSION, COOKIE, or Authorization header.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <!-- Refresh -->
        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">1</div>
            <div class="sjl-gen-step-content">
                <span class="sjl-gen-step-label"><?php echo __('Refresh Endpoint', 'simple-jwt-login'); ?></span>
                <p class="sjl-gen-step-desc">
                    <?php echo __('Accepts an expired token and returns a new valid JWT.', 'simple-jwt-login'); ?>
                </p>
                <div class="generated-code">
                    <span class="method">POST</span>
                    <span class="code">
                        <?php
                        echo esc_html($jwtSettings->generateExampleLink(
                            RouteService::AUTHENTICATION_REFRESH_ROUTE,
                            [$jwtSettings->getGeneralSettings()->getRequestKeyUrl() => 'YOUR_JWT']
                        ));
                        ?>
                    </span>
                    <span class="copy-button">
                        <button class="btn btn-secondary btn-xs"><?php echo __('Copy', 'simple-jwt-login'); ?></button>
                    </span>
                </div>
            </div>
        </div>

        <!-- Validate -->
        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">2</div>
            <div class="sjl-gen-step-content">
                <span class="sjl-gen-step-label"><?php echo __('Validate Endpoint', 'simple-jwt-login'); ?></span>
                <p class="sjl-gen-step-desc">
                    <?php echo __('Validates a JWT and returns the WordPress user details and token metadata.', 'simple-jwt-login'); ?>
                </p>
                <div class="generated-code">
                    <span class="method">GET</span>
                    <span class="method">POST</span>
                    <span class="code">
                        <?php
                        echo esc_html($jwtSettings->generateExampleLink(
                            RouteService::AUTHENTICATION_VALIDATE_ROUTE,
                            [$jwtSettings->getGeneralSettings()->getRequestKeyUrl() => 'YOUR_JWT']
                        ));
                        ?>
                    </span>
                    <span class="copy-button">
                        <button class="btn btn-secondary btn-xs"><?php echo __('Copy', 'simple-jwt-login'); ?></button>
                    </span>
                </div>
            </div>
        </div>

        <!-- Revoke -->
        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">3</div>
            <div class="sjl-gen-step-content">
                <span class="sjl-gen-step-label"><?php echo __('Revoke Endpoint', 'simple-jwt-login'); ?></span>
                <p class="sjl-gen-step-desc">
                    <?php echo __('Revokes a valid JWT, marking it as invalid for future requests.', 'simple-jwt-login'); ?>
                </p>
                <div class="generated-code">
                    <span class="method">POST</span>
                    <span class="code">
                        <?php
                        echo esc_html($jwtSettings->generateExampleLink(
                            RouteService::AUTHENTICATION_REVOKE,
                            [$jwtSettings->getGeneralSettings()->getRequestKeyUrl() => 'YOUR_JWT']
                        ));
                        ?>
                    </span>
                    <span class="copy-button">
                        <button class="btn btn-secondary btn-xs"><?php echo __('Copy', 'simple-jwt-login'); ?></button>
                    </span>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Access Control', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Restrict JWT authentication to specific IP addresses. Leave blank to allow from any IP.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <label class="sjl-gen-field-label" for="auth_ip">
            <?php echo __('Allowed IP Addresses', 'simple-jwt-login'); ?>
        </label>
        <input type="text" id="auth_ip" name="auth_ip"
               class="form-control sjl-gen-input-medium"
               value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAllowedIps()); ?>"
               placeholder="<?php echo __('e.g. 192.168.1.1, 10.0.0.0', 'simple-jwt-login'); ?>"
        />
        <p class="sjl-gen-card-desc" style="margin-top: 4px;">
            <?php echo __('Comma-separated. Leave blank to allow all IP addresses.', 'simple-jwt-login'); ?>
        </p>
    </div>
</div>
