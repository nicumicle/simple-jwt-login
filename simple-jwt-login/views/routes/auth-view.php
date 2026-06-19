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
        <span class="dashicons dashicons-shield"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Allow JWT Authentication', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Allow users to authenticate and receive JWT tokens via API endpoints.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_authentication" value="0"
                    <?php echo !$jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled() ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="allow_authentication" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled() ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('Endpoint examples:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    $sampleUrlParams = [
                        'email'    => __('Email', 'simple-jwt-login'),
                        'password' => __('Password', 'simple-jwt-login'),
                    ];
                    if ($jwtSettings->getAuthenticationSettings()->isAuthKeyRequired()) {
                        $sampleUrlParams[ $jwtSettings->getAuthCodesSettings()->getAuthCodeKey() ] =
                            __('AUTH_KEY_VALUE', 'simple-jwt-login');
                    }
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_ROUTE, $sampleUrlParams));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo esc_html__('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
            <p class="sjl-gen-card-desc" style="margin: 6px 0 6px 2px;"><strong><?php echo esc_html__('OR', 'simple-jwt-login'); ?></strong></p>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    $sampleUrlParams = [
                        'username' => __('Username', 'simple-jwt-login'),
                        'password' => __('Password', 'simple-jwt-login'),
                    ];
                    if ($jwtSettings->getAuthenticationSettings()->isAuthKeyRequired()) {
                        $sampleUrlParams[ $jwtSettings->getAuthCodesSettings()->getAuthCodeKey() ] =
                            __('AUTH_KEY_VALUE', 'simple-jwt-login');
                    }
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_ROUTE, $sampleUrlParams));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo esc_html__('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
            <p class="sjl-gen-card-desc" style="margin: 6px 0 6px 2px;"><strong><?php echo esc_html__('OR', 'simple-jwt-login'); ?></strong></p>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    $sampleUrlParams = [
                        'login'    => __('Username_or_email', 'simple-jwt-login'),
                        'password' => __('Password', 'simple-jwt-login'),
                    ];
                    if ($jwtSettings->getAuthenticationSettings()->isAuthKeyRequired()) {
                        $sampleUrlParams[ $jwtSettings->getAuthCodesSettings()->getAuthCodeKey() ] =
                            __('AUTH_KEY_VALUE', 'simple-jwt-login');
                    }
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_ROUTE, $sampleUrlParams));
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
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Require Authentication Code for JWT Generation', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('If enabled, an additional authentication code must be provided to generate JWT tokens.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="auth_requires_auth_code" value="0"
                    <?php echo !$jwtSettings->getAuthenticationSettings()->isAuthKeyRequired() ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Not required', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="auth_requires_auth_code" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isAuthKeyRequired() ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Required', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Authentication Options', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Additional settings for the authentication process.', 'simple-jwt-login'); ?>
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
                    <?php echo esc_html__('Authentication password / passhash is base64 encoded', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('Enable this if the password or password hash sent in the request is base64 encoded.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>
        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="auth_password_hash_enabled" id="auth_password_hash_enabled" value="1"
                    <?php echo $jwtSettings->getAuthenticationSettings()->isAuthPasswordHashAllowed()
                        ? esc_html('checked="checked"')
                        : '';
                    ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="auth_password_hash_enabled" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Allow authentication with password hash', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('Allow the password_hash parameter to authenticate by comparing it directly with the stored WordPress password hash. Warning: anyone who obtains a stored password hash can authenticate without knowing the password. Leave this disabled unless you really need it.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-editor-code"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                if (isset($errorCode) && $errorCode === $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_CUSTOM_CLAIM_PROTECTED_HEADER
                )) {
                    echo '<span class="simple-jwt-error">!</span> ';
                }
                ?>
                <?php echo esc_html__('JWT Header Configuration', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('The standard header included in generated JWT tokens.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div id="authentication_header_data" class="authentication_jwt_container">
            <ul>
                <li>{</li>
                <li>
                    <ul>
                        <?php
                        $headerAlg = $jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm();
                        $headerCustomClaims = $jwtSettings->getAuthenticationSettings()->getCustomHeaderClaims();
                        $headerItemIndex = 0;
                        ?>
                        <li>
                            <span class="checkbox"></span>
                            <span class="key">"alg"</span>
                            <span class="delimiter">:</span>
                            <span class="value">"<?php echo esc_html($headerAlg); ?>"</span>
                            <span class="line-separator">,</span>
                        </li>
                        <li>
                            <span class="checkbox"></span>
                            <span class="key">"typ"</span>
                            <span class="delimiter">:</span>
                            <span class="value">"JWT"</span>
                            <span class="line-separator"><?php echo count($headerCustomClaims) > 0 ? ',' : ''; ?></span>
                        </li>
                        <?php foreach ($headerCustomClaims as $claimKey => $claimValue) {
                            $headerItemIndex++;
                            $isLast = $headerItemIndex === count($headerCustomClaims);
                            ?>
                            <li>
                                <span class="checkbox"></span>
                                <span class="key">"<?php echo esc_html($claimKey); ?>"</span>
                                <span class="delimiter">:</span>
                                <span class="value">"<?php echo esc_html($claimValue); ?>"</span>
                                <span class="line-separator"><?php echo $isLast ? '' : ','; ?></span>
                            </li>
                        <?php } ?>
                    </ul>
                </li>
                <li>}</li>
            </ul>
        </div>

        <div class="sjl-webhook-subsection">
            <div class="sjl-webhook-subsection-header">
                <?php echo esc_html__('Custom Header Claims', 'simple-jwt-login'); ?>
            </div>
            <p class="sjl-gen-card-desc" style="margin-bottom: 10px;">
                <?php echo esc_html__('Add custom key-value pairs to the JWT header. Reserved header fields cannot be overwritten:', 'simple-jwt-login'); ?>
                <?php foreach (AuthenticationSettings::$protectedHeaderKeys as $protectedKey) { ?>
                    <span class="sjl-claim-badge"><?php echo esc_html($protectedKey); ?></span>
                <?php } ?>
            </p>
            <div id="sjl-header-claims-table">
                <div class="sjl-claims-header">
                    <span><?php echo esc_html__('Claim Key', 'simple-jwt-login'); ?></span>
                    <span><?php echo esc_html__('Claim Value', 'simple-jwt-login'); ?></span>
                    <span></span>
                </div>
                <?php
                $headerClaims = $jwtSettings->getAuthenticationSettings()->getCustomHeaderClaims();
                foreach ($headerClaims as $claimKey => $claimValue) {
                    ?>
                    <div class="sjl-claims-row">
                        <input type="text"
                               name="custom_claims_header[key][]"
                               class="form-control sjl-auth-input"
                               value="<?php echo esc_attr($claimKey); ?>"
                               placeholder="<?php echo esc_attr__('e.g. x-app-id', 'simple-jwt-login'); ?>"
                        />
                        <input type="text"
                               name="custom_claims_header[value][]"
                               class="form-control sjl-auth-input"
                               value="<?php echo esc_attr($claimValue); ?>"
                               placeholder="<?php echo esc_attr__('e.g. my-app', 'simple-jwt-login'); ?>"
                        />
                        <button type="button"
                                class="sjl-endpoint-remove"
                                onclick="sjlRemoveClaimRow(this)"
                                title="<?php echo esc_attr__('Remove', 'simple-jwt-login'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button type="button" id="sjl-add-header-claim" class="btn btn-outline-secondary sjl-add-claim-btn" style="margin-top: 10px;">
                <?php echo esc_html__('+ Add Header Claim', 'simple-jwt-login'); ?>
            </button>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-editor-ul"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                $payloadErrorCodes = array(
                    $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTHENTICATION,
                        SettingsErrors::ERR_AUTHENTICATION_EMPTY_PAYLOAD
                    ),
                    $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTHENTICATION,
                        SettingsErrors::ERR_AUTHENTICATION_CUSTOM_CLAIM_PROTECTED_PAYLOAD
                    ),
                    $settingsErrors->generateCode(
                        SettingsErrors::PREFIX_AUTHENTICATION,
                        SettingsErrors::ERR_AUTHENTICATION_CUSTOM_CLAIM_EMPTY_KEY
                    ),
                );
                if (isset($errorCode) && in_array($errorCode, $payloadErrorCodes, true)) {
                    echo '<span class="simple-jwt-error">!</span> ';
                }
                ?>
                <?php echo esc_html__('JWT Payload Configuration', 'simple-jwt-login'); ?>
                <span class="required">*</span>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Select which user data to include in the JWT payload.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-payload-select-all-row">
            <label class="sjl-gen-feature-label">
                <input type="checkbox" id="sjl-payload-check-all" />
                <?php echo esc_html__('Select all', 'simple-jwt-login'); ?>
            </label>
        </div>
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

        <div class="sjl-webhook-subsection">
            <div class="sjl-webhook-subsection-header">
                <?php
                if (isset($errorCode) && $errorCode === $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_EMPTY_ISS
                )) {
                    echo '<span class="simple-jwt-error">!</span> ';
                }
                ?>
                <?php echo esc_html__('Issuer (iss)', 'simple-jwt-login'); ?>
            </div>
            <label class="sjl-gen-field-label" for="jwt_auth_iss">
                <?php echo esc_html__('Issuer value', 'simple-jwt-login'); ?>
            </label>
            <input type="text" name="jwt_auth_iss" id="jwt_auth_iss"
                   class="form-control sjl-gen-input-medium"
                   value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAuthIss()); ?>"
                   placeholder="<?php echo esc_attr__('Default issuer', 'simple-jwt-login'); ?>"
            />
        </div>

        <div class="sjl-webhook-subsection">
            <div class="sjl-webhook-subsection-header">
                <?php echo esc_html__('Custom Payload Claims', 'simple-jwt-login'); ?>
            </div>
            <p class="sjl-gen-card-desc" style="margin-bottom: 10px;">
                <?php echo esc_html__('Add custom key-value pairs to the JWT payload. Reserved claims cannot be overwritten:', 'simple-jwt-login'); ?>
                <?php foreach (AuthenticationSettings::$protectedPayloadKeys as $protectedKey) { ?>
                    <span class="sjl-claim-badge"><?php echo esc_html($protectedKey); ?></span>
                <?php } ?>
            </p>
            <div id="sjl-payload-claims-table">
                <div class="sjl-claims-header">
                    <span><?php echo esc_html__('Claim Key', 'simple-jwt-login'); ?></span>
                    <span><?php echo esc_html__('Claim Value', 'simple-jwt-login'); ?></span>
                    <span></span>
                </div>
                <?php
                $payloadClaims = $jwtSettings->getAuthenticationSettings()->getCustomPayloadClaims();
                foreach ($payloadClaims as $claimKey => $claimValue) {
                    ?>
                    <div class="sjl-claims-row">
                        <input type="text"
                               name="custom_claims_payload[key][]"
                               class="form-control sjl-auth-input"
                               value="<?php echo esc_attr($claimKey); ?>"
                               placeholder="<?php echo esc_attr__('e.g. department', 'simple-jwt-login'); ?>"
                        />
                        <input type="text"
                               name="custom_claims_payload[value][]"
                               class="form-control sjl-auth-input"
                               value="<?php echo esc_attr($claimValue); ?>"
                               placeholder="<?php echo esc_attr__('e.g. engineering', 'simple-jwt-login'); ?>"
                        />
                        <button type="button"
                                class="sjl-endpoint-remove"
                                onclick="sjlRemoveClaimRow(this)"
                                title="<?php echo esc_attr__('Remove', 'simple-jwt-login'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button type="button" id="sjl-add-payload-claim" class="btn btn-outline-secondary sjl-add-claim-btn" style="margin-top: 10px;">
                <?php echo esc_html__('+ Add Payload Claim', 'simple-jwt-login'); ?>
            </button>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-clock"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                if (isset($errorCode) && $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_AUTHENTICATION,
                    SettingsErrors::ERR_AUTHENTICATION_TTL
                ) === $errorCode) {
                    echo '<span class="simple-jwt-error">!</span> ';
                }
                ?>
                <?php echo esc_html__('JWT Expiration', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Configure how long generated JWT tokens are valid.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <label class="sjl-gen-field-label" for="jwt_auth_ttl">
            <?php echo esc_html__('Expiration time (minutes)', 'simple-jwt-login'); ?>
            <span class="required">*</span>
        </label>
        <input type="number" name="jwt_auth_ttl" id="jwt_auth_ttl"
               class="form-control sjl-gen-input-medium"
               value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAuthJwtTtl()); ?>"
               placeholder="<?php echo esc_attr__('Number of minutes', 'simple-jwt-login'); ?>"
        />
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Access Control', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Restrict JWT authentication to specific IP addresses. Leave blank to allow from any IP.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <label class="sjl-gen-field-label" for="auth_ip">
            <?php echo esc_html__('Allowed IP Addresses', 'simple-jwt-login'); ?>
        </label>
        <input type="text" id="auth_ip" name="auth_ip"
               class="form-control sjl-gen-input-medium"
               value="<?php echo esc_attr($jwtSettings->getAuthenticationSettings()->getAllowedIps()); ?>"
               placeholder="<?php echo esc_attr__('e.g. 192.168.1.1, 10.0.0.0', 'simple-jwt-login'); ?>"
        />
        <p class="sjl-gen-card-desc" style="margin-top: 4px;">
            <?php echo esc_html__('Comma-separated. Leave blank to allow all IP addresses.', 'simple-jwt-login'); ?>
        </p>
    </div>
</div>
