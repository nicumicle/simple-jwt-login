<?php

use SimpleJWTLogin\Helpers\Jwt\JwtKeyWpConfig;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
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
        <span class="dashicons dashicons-networking"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php echo __('Route Namespace', 'simple-jwt-login'); ?>
                <span class="required">*</span>
                <?php
                echo isset($errorCode)
                && $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_GENERAL,
                    SettingsErrors::ERR_GENERAL_EMPTY_NAMESPACE
                ) === $errorCode
                    ? '<span class="simple-jwt-error">!</span>'
                    : '';
                ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Base URL prefix for all Simple JWT Login REST endpoints. Change only if you need to avoid conflicts with other plugins.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="form-group">
            <input
                type="text"
                name="route_namespace"
                value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRouteNamespace()); ?>"
                class="form-control sjl-gen-input-medium"
                placeholder="<?php echo __('e.g. simple-jwt-login/v1', 'simple-jwt-login'); ?>"
            />
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('JWT Verification', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Configure how incoming JWTs are signed and verified.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <!-- Step 1: Key Source -->
        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">1</div>
            <div class="sjl-gen-step-content">
                <label class="sjl-gen-step-label" for="decryption_source">
                    <?php echo __('Where is your JWT secret stored?', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-step-desc">
                    <?php echo __('Choose where the plugin reads the JWT verification key from.', 'simple-jwt-login'); ?>
                </p>
                <select id="decryption_source" name="decryption_source" class="form-control sjl-gen-select">
                    <option
                        value="<?php echo GeneralSettings::DECRYPTION_SOURCE_SETTINGS; ?>"
                        <?php echo ($jwtSettings->getGeneralSettings()->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_SETTINGS ? 'selected' : ''); ?>
                    >
                        <?php echo __('Plugin Settings (recommended)', 'simple-jwt-login'); ?>
                    </option>
                    <option
                        value="<?php echo GeneralSettings::DECRYPTION_SOURCE_CODE; ?>"
                        <?php echo ($jwtSettings->getGeneralSettings()->getDecryptionSource() === GeneralSettings::DECRYPTION_SOURCE_CODE ? 'selected' : ''); ?>
                    >
                        <?php echo __('Code (wp-config.php or custom plugin)', 'simple-jwt-login'); ?>
                    </option>
                </select>
            </div>
        </div>

        <!-- Step 2: Algorithm -->
        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">2</div>
            <div class="sjl-gen-step-content">
                <label class="sjl-gen-step-label" for="simple-jwt-login-jwt-algorithm">
                    <?php echo __('JWT Algorithm', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-step-desc">
                    <?php echo __('Must match the algorithm used to sign the JWT. Check the "alg" field in the token header.', 'simple-jwt-login'); ?>
                </p>
                <select name="jwt_algorithm" class="form-control sjl-gen-select" id="simple-jwt-login-jwt-algorithm">
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

        <!-- Step 3: Verification Key -->
        <div class="sjl-gen-step">
            <div class="sjl-gen-step-number">
                <?php
                echo isset($errorCode)
                && (
                    $settingsErrors->generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_PRIVATE_KEY_MISSING_FROM_CODE_RS) === $errorCode
                    || $settingsErrors->generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_PRIVATE_KEY_NOT_PRESENT_IN_CODE_HS) === $errorCode
                    || $settingsErrors->generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_MISSING_PRIVATE_AND_PUBLIC_KEY) === $errorCode
                    || $settingsErrors->generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_DECRYPTION_KEY_REQUIRED) === $errorCode
                )
                    ? '<span class="simple-jwt-error">!</span>'
                    : '3';
                ?>
            </div>
            <div class="sjl-gen-step-content">
                <label class="sjl-gen-step-label">
                    <?php echo __('JWT Verification Key', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-step-desc">
                    <?php echo __('Secret or key used to verify incoming JWTs.', 'simple-jwt-login'); ?>
                </p>

                <!-- Symmetric key input (HS256, etc.) -->
                <div class="decryption-input-group">
                    <div class="input-group" id="decryption_key_container">
                        <input type="password" name="decryption_key" class="form-control" autocomplete="off"
                               id="decryption_key"
                               value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getDecryptionKey()); ?>"
                               placeholder="<?php echo __('Enter JWT secret key', 'simple-jwt-login'); ?>"
                        />
                        <div class="input-group-addon">
                            <a href="javascript:void(0)"
                               onclick="showDecryptionKey()"
                               class="toggle_key_button"
                               title="<?php echo __('Toggle key visibility', 'simple-jwt-login'); ?>"
                            >
                                <i class="toggle-image" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                    <div class="sjl-gen-strength-row">
                        <span><?php echo __('Strength', 'simple-jwt-login'); ?>:</span>
                        <progress id="decryption_progress" value="0" max="100"></progress>
                        <span id="decryption_progress_label" class="sjl-gen-strength-label"></span>
                    </div>
                    <div class="sjl-gen-checkbox-row">
                        <input
                            type="checkbox"
                            name="decryption_key_base64"
                            id="decryption_key_base64"
                            value="1"
                            <?php echo $jwtSettings->getGeneralSettings()->isDecryptionKeyBase64Encoded()
                                ? esc_html('checked="checked"')
                                : '';
                            ?>
                        />
                        <label for="decryption_key_base64">
                            <?php echo __('JWT key is Base64 encoded', 'simple-jwt-login'); ?>
                        </label>
                    </div>
                </div>

                <!-- Asymmetric key inputs (RS256, etc.) -->
                <div class="decryption-textarea-group">
                    <div class="form-group">
                        <label for="simple-jwt-login-public-key">
                            <?php echo __('Public Key', 'simple-jwt-login'); ?>
                            <span class="required">*</span>
                        </label>
                        <textarea
                            class="form-control"
                            id="simple-jwt-login-public-key"
                            rows="6"
                            name="decryption_key_public"
                        ><?php echo esc_html($jwtSettings->getGeneralSettings()->getDecryptionKeyPublic()); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="simple-jwt-login-private-key">
                            <?php echo __('Private Key', 'simple-jwt-login'); ?>
                            <span class="required">*</span>
                        </label>
                        <textarea
                            class="form-control"
                            id="simple-jwt-login-private-key"
                            rows="6"
                            name="decryption_key_private"
                        ><?php echo esc_html($jwtSettings->getGeneralSettings()->getDecryptionKeyPrivate()); ?></textarea>
                    </div>
                </div>

                <!-- Code-based key info -->
                <div class="decryption-code-info sjl-gen-code-block">
                    <p class="sjl-gen-code-block-intro">
                        <?php echo __('Define the following constants in your code (e.g. in', 'simple-jwt-login'); ?>
                        <code>wp-config.php</code>):
                    </p>
                    <code class="define_private_key sjl-gen-code-line">
                        define('<strong><?php echo JwtKeyWpConfig::SIMPLE_JWT_PRIVATE_KEY; ?></strong>', 'MY_SECRET_KEY');
                    </code>
                    <code class="define_public_key sjl-gen-code-line">
                        define('<strong><?php echo JwtKeyWpConfig::SIMPLE_JWT_PUBLIC_KEY; ?></strong>', 'MY_PUBLIC_KEY');
                    </code>
                </div>

            </div>
        </div>

    </div><!-- /.sjl-gen-card-body -->
</div><!-- /.sjl-gen-card -->

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-search"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                echo isset($errorCode)
                && (
                    $settingsErrors->generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_GET_JWT_FROM) === $errorCode
                    || $settingsErrors->generateCode(SettingsErrors::PREFIX_GENERAL, SettingsErrors::ERR_GENERAL_REQUEST_KEYS) === $errorCode
                )
                    ? '<span class="simple-jwt-error">!</span> '
                    : '';
                ?>
                <?php echo __('JWT Input Sources', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo __(
                    'Enable one or more locations where the JWT may be provided with the request. '
                    . 'When the JWT appears in multiple locations, higher-priority sources override lower ones.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <table class="sjl-gen-source-table">
            <thead>
                <tr>
                    <th class="sjl-gen-col-source"><?php echo __('Source', 'simple-jwt-login'); ?></th>
                    <th class="sjl-gen-col-param"><?php echo __('Parameter Name', 'simple-jwt-login'); ?></th>
                    <th class="sjl-gen-col-status"><?php echo __('Status', 'simple-jwt-login'); ?></th>
                    <th class="sjl-gen-col-example"><?php echo __('Example', 'simple-jwt-login'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- URL / REQUEST -->
                <tr>
                    <td>
                        <span class="sjl-gen-source-badge sjl-gen-source-request">REQUEST</span>
                    </td>
                    <td>
                        <input
                            type="text"
                            name="request_keys[url]"
                            required="required"
                            class="form-control sjl-gen-param-input"
                            placeholder="<?php echo __('e.g. jwt', 'simple-jwt-login'); ?>"
                            value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeyUrl()); ?>"
                        />
                    </td>
                    <td>
                        <select name="request_jwt_url" class="form-control onOff sjl-gen-onoff">
                            <option value="0" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromURLEnabled() === false ? 'selected' : ''; ?>>
                                <?php echo __('Off', 'simple-jwt-login'); ?>
                            </option>
                            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromURLEnabled() === true ? 'selected' : ''; ?>>
                                <?php echo __('On', 'simple-jwt-login'); ?>
                            </option>
                        </select>
                    </td>
                    <td>
                        <?php $requestKeyUrl = esc_html($jwtSettings->getGeneralSettings()->getRequestKeyUrl()); ?>
                        <code class="sjl-gen-example-code">?<?php echo $requestKeyUrl; ?>=<strong>YOUR_JWT</strong></code>
                    </td>
                </tr>

                <!-- SESSION -->
                <tr>
                    <td>
                        <span class="sjl-gen-source-badge sjl-gen-source-session">SESSION</span>
                    </td>
                    <td>
                        <input
                            type="text"
                            name="request_keys[session]"
                            required="required"
                            class="form-control sjl-gen-param-input"
                            placeholder="<?php echo __('e.g. jwt', 'simple-jwt-login'); ?>"
                            value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeySession()); ?>"
                        />
                    </td>
                    <td>
                        <select name="request_jwt_session" class="form-control onOff sjl-gen-onoff">
                            <option value="0" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled() === false ? 'selected' : ''; ?>>
                                <?php echo __('Off', 'simple-jwt-login'); ?>
                            </option>
                            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled() === true ? 'selected' : ''; ?>>
                                <?php echo __('On', 'simple-jwt-login'); ?>
                            </option>
                        </select>
                    </td>
                    <td>
                        <code class="sjl-gen-example-code">$_SESSION['<strong><?php echo esc_html($jwtSettings->getGeneralSettings()->getRequestKeySession()); ?></strong>']</code>
                    </td>
                </tr>

                <!-- COOKIE -->
                <tr>
                    <td>
                        <span class="sjl-gen-source-badge sjl-gen-source-cookie">COOKIE</span>
                    </td>
                    <td>
                        <input
                            type="text"
                            name="request_keys[cookie]"
                            required="required"
                            class="form-control sjl-gen-param-input"
                            placeholder="<?php echo __('e.g. jwt', 'simple-jwt-login'); ?>"
                            value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeyCookie()); ?>"
                        />
                    </td>
                    <td>
                        <select name="request_jwt_cookie" class="form-control onOff sjl-gen-onoff">
                            <option value="0" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromCookieEnabled() === false ? 'selected' : ''; ?>>
                                <?php echo __('Off', 'simple-jwt-login'); ?>
                            </option>
                            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromCookieEnabled() === true ? 'selected' : ''; ?>>
                                <?php echo __('On', 'simple-jwt-login'); ?>
                            </option>
                        </select>
                    </td>
                    <td>
                        <code class="sjl-gen-example-code">$_COOKIE['<strong><?php echo esc_html($jwtSettings->getGeneralSettings()->getRequestKeyCookie()); ?></strong>']</code>
                    </td>
                </tr>

                <!-- HEADER -->
                <tr>
                    <td>
                        <span class="sjl-gen-source-badge sjl-gen-source-header">HEADER</span>
                    </td>
                    <td>
                        <input
                            type="text"
                            name="request_keys[header]"
                            required="required"
                            class="form-control sjl-gen-param-input"
                            placeholder="<?php echo __('e.g. Authorization', 'simple-jwt-login'); ?>"
                            value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeyHeader()); ?>"
                        />
                    </td>
                    <td>
                        <select name="request_jwt_header" class="form-control onOff sjl-gen-onoff">
                            <option value="0" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromHeaderEnabled() === false ? 'selected' : ''; ?>>
                                <?php echo __('Off', 'simple-jwt-login'); ?>
                            </option>
                            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromHeaderEnabled() === true ? 'selected' : ''; ?>>
                                <?php echo __('On', 'simple-jwt-login'); ?>
                            </option>
                        </select>
                    </td>
                    <td>
                        <?php $headerKey = esc_html($jwtSettings->getGeneralSettings()->getRequestKeyHeader()); ?>
                        <code class="sjl-gen-example-code"><?php echo $headerKey; ?>: Bearer <strong>YOUR_JWT</strong></code>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-plugins"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Integration Options', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Control how the plugin integrates with the WordPress REST API and third-party tools.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="api_middleware[enabled]" id="api_middleware_enabled"
                       value="1"
                    <?php echo $jwtSettings->getGeneralSettings()->isMiddlewareEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="api_middleware_enabled" class="sjl-gen-feature-label">
                    <?php echo __('JWT Middleware for all WordPress endpoints', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo __(
                        'When enabled, any WordPress REST API call that includes a JWT will automatically authenticate the user before processing the request.',
                        'simple-jwt-login'
                    ); ?>
                </p>
            </div>
        </div>

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="wp_graphql[enabled]" id="wp_graphql_enabled"
                       value="1"
                    <?php echo $jwtSettings->getGeneralSettings()->isWpGraphqlAuthenticationEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="wp_graphql_enabled" class="sjl-gen-feature-label">
                    <span class="beta">beta</span>
                    <?php echo __(
                        sprintf('WPGraphQL authentication (%sWPGraphQL plugin required%s)', '<a href="https://www.wpgraphql.com/" target="_blank">', '</a>'),
                        'simple-jwt-login'
                    ); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo __(
                        'When a JWT is provided on WPGraphQL queries, the plugin will authenticate the user before executing the query.',
                        'simple-jwt-login'
                    ); ?>
                </p>
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-lock"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Security', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Additional security hardening options.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="security[safe_redirect]" id="security_safe_redirect"
                       value="1"
                    <?php echo $jwtSettings->getGeneralSettings()->isSafeRedirectEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="security_safe_redirect" class="sjl-gen-feature-label">
                    <?php echo __('Enable safe redirects', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo __(
                        'Use wp_safe_redirect() for all redirects to prevent open redirect vulnerabilities.',
                        'simple-jwt-login'
                    ); ?>
                </p>
            </div>
        </div>

    </div>
</div>
