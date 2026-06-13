<?php

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
                <?php
                echo isset($errorCode)
                && $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_GENERAL,
                    SettingsErrors::ERR_GENERAL_EMPTY_NAMESPACE
                ) === $errorCode
                    ? '<span class="simple-jwt-error">!</span>'
                    : '';
                ?>
                <?php echo esc_html__('Route Namespace', 'simple-jwt-login'); ?>
                <span class="required">*</span>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Base URL prefix for all Simple JWT Login REST endpoints. Change only if you need to avoid conflicts with other plugins.', 'simple-jwt-login'); ?>
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
                placeholder="<?php echo esc_attr__('e.g. simple-jwt-login/v1', 'simple-jwt-login'); ?>"
            />
        </div>
    </div>
</div>

<?php include_once plugin_dir_path(__FILE__) . 'jwt-rules-view.php'; ?>

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
                <?php echo esc_html__('JWT Input Sources', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'Enable one or more locations where the JWT may be provided with the request. When the JWT appears in multiple locations, higher-priority sources override lower ones.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <table class="sjl-gen-source-table">
            <thead>
                <tr>
                    <th class="sjl-gen-col-source"><?php echo esc_html__('Source', 'simple-jwt-login'); ?></th>
                    <th class="sjl-gen-col-param"><?php echo esc_html__('Parameter Name', 'simple-jwt-login'); ?></th>
                    <th class="sjl-gen-col-status"><?php echo esc_html__('Status', 'simple-jwt-login'); ?></th>
                    <th class="sjl-gen-col-example"><?php echo esc_html__('Example', 'simple-jwt-login'); ?></th>
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
                            placeholder="<?php echo esc_attr__('e.g. jwt', 'simple-jwt-login'); ?>"
                            value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeyUrl()); ?>"
                        />
                    </td>
                    <td>
                        <select name="request_jwt_url" class="form-control onOff sjl-gen-onoff">
                            <option value="0" <?php echo !$jwtSettings->getGeneralSettings()->isJwtFromURLEnabled() ? 'selected' : ''; ?>>
                                <?php echo esc_html__('Off', 'simple-jwt-login'); ?>
                            </option>
                            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromURLEnabled() ? 'selected' : ''; ?>>
                                <?php echo esc_html__('On', 'simple-jwt-login'); ?>
                            </option>
                        </select>
                    </td>
                    <td>
                        <code class="sjl-gen-example-code">?<?php echo esc_html($jwtSettings->getGeneralSettings()->getRequestKeyUrl()); ?>=<strong>YOUR_JWT</strong></code>
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
                            placeholder="<?php echo esc_attr__('e.g. jwt', 'simple-jwt-login'); ?>"
                            value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeySession()); ?>"
                        />
                    </td>
                    <td>
                        <select name="request_jwt_session" class="form-control onOff sjl-gen-onoff">
                            <option value="0" <?php echo !$jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled() ? 'selected' : ''; ?>>
                                <?php echo esc_html__('Off', 'simple-jwt-login'); ?>
                            </option>
                            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled() ? 'selected' : ''; ?>>
                                <?php echo esc_html__('On', 'simple-jwt-login'); ?>
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
                            placeholder="<?php echo esc_attr__('e.g. jwt', 'simple-jwt-login'); ?>"
                            value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeyCookie()); ?>"
                        />
                    </td>
                    <td>
                        <select name="request_jwt_cookie" class="form-control onOff sjl-gen-onoff">
                            <option value="0" <?php echo !$jwtSettings->getGeneralSettings()->isJwtFromCookieEnabled() ? 'selected' : ''; ?>>
                                <?php echo esc_html__('Off', 'simple-jwt-login'); ?>
                            </option>
                            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromCookieEnabled() ? 'selected' : ''; ?>>
                                <?php echo esc_html__('On', 'simple-jwt-login'); ?>
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
                            placeholder="<?php echo esc_attr__('e.g. Authorization', 'simple-jwt-login'); ?>"
                            value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getRequestKeyHeader()); ?>"
                        />
                    </td>
                    <td>
                        <select name="request_jwt_header" class="form-control onOff sjl-gen-onoff">
                            <option value="0" <?php echo !$jwtSettings->getGeneralSettings()->isJwtFromHeaderEnabled() ? 'selected' : ''; ?>>
                                <?php echo esc_html__('Off', 'simple-jwt-login'); ?>
                            </option>
                            <option value="1" <?php echo $jwtSettings->getGeneralSettings()->isJwtFromHeaderEnabled() ? 'selected' : ''; ?>>
                                <?php echo esc_html__('On', 'simple-jwt-login'); ?>
                            </option>
                        </select>
                    </td>
                    <td>
                        <code class="sjl-gen-example-code"><?php echo esc_html($jwtSettings->getGeneralSettings()->getRequestKeyHeader()); ?>: Bearer <strong>YOUR_JWT</strong></code>
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
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Integration Options', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Control how the plugin integrates with the WordPress REST API and third-party tools.', 'simple-jwt-login'); ?>
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
                    <?php echo esc_html__('JWT Middleware for all WordPress endpoints', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__(
                        'When enabled, any WordPress REST API call that includes a JWT will automatically authenticate the user before processing the request.',
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
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Security', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Additional security hardening options.', 'simple-jwt-login'); ?>
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
                    <?php echo esc_html__('Enable safe redirects', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__(
                        'Use wp_safe_redirect() for all redirects to prevent open redirect vulnerabilities.',
                        'simple-jwt-login'
                    ); ?>
                </p>
            </div>
        </div>

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="security[trust_ip_headers]" id="security_trust_ip_headers"
                       value="1"
                    <?php echo $jwtSettings->getGeneralSettings()->isTrustIpHeadersEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="security_trust_ip_headers" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Trust reverse proxy IP headers', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__(
                        'Detect the client IP from the Client-IP / X-Forwarded-For headers. Enable this only when the site runs behind a trusted reverse proxy or load balancer. When disabled, IP restrictions use the connection address (REMOTE_ADDR), which cannot be spoofed.',
                        'simple-jwt-login'
                    ); ?>
                </p>
            </div>
        </div>

    </div>
</div>
