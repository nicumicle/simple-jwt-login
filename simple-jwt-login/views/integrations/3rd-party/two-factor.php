<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\RouteService;

if (!defined('ABSPATH')) {
    /**
     * @phpstan-ignore-next-line
     */
    exit;
}

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 * @var bool $sjlPluginInstalled
 * @var bool $sjlPluginActivated
 */
$tfaSettings = $jwtSettings->getIntegrationsSettings()->twoFactor();
?>

<?php if (!$sjlPluginInstalled) : ?>
<div class="notice notice-warning inline sjl-plugin-not-installed-notice">
    <p>
        <?php echo wp_kses(
            sprintf(
                /* translators: 1: opening anchor tag, 2: closing anchor tag */
                __('The %1$sTwo Factor%2$s plugin is not installed. This integration will not work until the plugin is installed and activated.', 'simple-jwt-login'),
                '<a href="https://wordpress.org/plugins/two-factor/" target="_blank">',
                '</a>'
            ),
            ['a' => ['href' => [], 'target' => []]]
        ); ?>
    </p>
</div>
<?php elseif (!$sjlPluginActivated) : ?>
<div class="notice notice-warning inline sjl-plugin-not-installed-notice">
    <p>
        <?php echo wp_kses(
            sprintf(
                /* translators: 1: opening anchor tag, 2: closing anchor tag */
                __('The %1$sTwo Factor%2$s plugin is not activated. This integration will not work until the plugin is active.', 'simple-jwt-login'),
                '<a href="https://wordpress.org/plugins/two-factor/" target="_blank">',
                '</a>'
            ),
            ['a' => ['href' => [], 'target' => []]]
        ); ?>
    </p>
</div>
<?php endif; ?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="two-factor logo"></div>
            <div>
                <h3 class="sjl-gen-card-title">
                    <?php echo esc_html__('Two-Factor Authentication', 'simple-jwt-login'); ?>
                </h3>
                <p class="sjl-gen-card-desc">
                    <?php echo wp_kses(
                        sprintf(
                            /* translators: 1: opening anchor tag, 2: closing anchor tag */
                            __('Require a 2FA Code before issuing a JWT, using the %1$sTwo Factor%2$s plugin.', 'simple-jwt-login'),
                            '<a href="https://wordpress.org/plugins/two-factor/" target="_blank">',
                            '</a>'
                        ),
                        ['a' => ['href' => [], 'target' => []]]
                    ); ?>
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="hidden" name="two_factor[enabled]" value="0">
            <label class="sjl-toggle-switch"
                   title="<?php echo esc_attr(__('Enable / Disable Two-Factor integration', 'simple-jwt-login')); ?>"
                   style="margin: 0;">
                <input type="checkbox" id="two_factor_enabled" name="two_factor[enabled]" value="1"
                    <?php echo $tfaSettings->isEnabled() ? 'checked' : ''; ?>>
                <span class="sjl-toggle-slider"></span>
            </label>
            <span class="sjl-toggle-enable-label">
                <?php echo esc_html(__('Enable', 'simple-jwt-login')); ?>
            </span>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Settings', 'simple-jwt-login'); ?></h3>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <table class="form-table sjl-form-table">
            <tr>
                <th scope="row">
                    <label for="two_factor_interim_ttl">
                        <?php echo esc_html__('Interim JWT TTL (minutes)', 'simple-jwt-login'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" id="two_factor_interim_ttl"
                           name="two_factor[interim_ttl]"
                           value="<?php echo esc_attr((string) $tfaSettings->getInterimTtl()); ?>"
                           min="1" max="60" class="small-text">
                    <p class="description">
                        <?php echo esc_html__(
                            'How long the interim JWT (issued after password check, before 2FA verification) remains valid.',
                            'simple-jwt-login'
                        ); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-rest-api"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Try Now', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'Exchange an interim JWT (from POST /auth) and a 2FA code for a full JWT.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::AUTHENTICATION_2FA_ROUTE, [
                        'JWT'  => __('interim_jwt', 'simple-jwt-login'),
                        'code' => __('123456', 'simple-jwt-login'),
                    ]));
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
        <span class="dashicons dashicons-info-outline"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('How it works', 'simple-jwt-login'); ?></h3>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <ol class="sjl-gen-card-desc" style="padding-left: 20px;">
            <li style="margin-bottom: 6px;">
                <?php echo esc_html__(
                    'Client POSTs credentials to /auth. If the user has 2FA configured, an interim JWT is returned instead of a full JWT.',
                    'simple-jwt-login'
                ); ?>
            </li>
            <li style="margin-bottom: 6px;">
                <?php echo esc_html__(
                    'Client submits the interim JWT + the 2FA code to POST /auth/2fa.',
                    'simple-jwt-login'
                ); ?>
            </li>
            <li style="margin-bottom: 6px;">
                <?php echo esc_html__(
                    'On success, a full JWT (and optional refresh token) is returned, identical to a normal /auth response.',
                    'simple-jwt-login'
                ); ?>
            </li>
        </ol>
        <p class="sjl-gen-card-desc" style="margin-top: 12px;">
            <?php echo wp_kses(
                sprintf(
                    /* translators: 1: opening anchor tag, 2: closing anchor tag */
                    __('Requires the %1$sTwo Factor%2$s plugin to be installed and activated.', 'simple-jwt-login'),
                    '<a href="https://wordpress.org/plugins/two-factor/" target="_blank">',
                    '</a>'
                ),
                ['a' => ['href' => [], 'target' => []]]
            ); ?>
        </p>
        <p class="sjl-gen-card-desc">
            <?php echo esc_html__(
                'Users with 2FA enabled but who need to bypass it for API access can use the two_factor_user_api_login_enable WordPress filter.',
                'simple-jwt-login'
            ); ?>
        </p>
    </div>
</div>
