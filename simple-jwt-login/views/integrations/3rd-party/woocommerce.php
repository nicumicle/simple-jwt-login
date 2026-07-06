<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

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
?>

<?php if (!$sjlPluginInstalled) : ?>
<div class="notice notice-warning inline sjl-plugin-not-installed-notice">
    <p>
        <?php echo wp_kses(
            sprintf(
                /* translators: 1: opening anchor tag, 2: closing anchor tag */
                __('The %1$sWooCommerce%2$s plugin is not installed. This integration will not work until the plugin is installed and activated.', 'simple-jwt-login'),
                '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">',
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
                __('The %1$sWooCommerce%2$s plugin is not activated. This integration will not work until the plugin is active.', 'simple-jwt-login'),
                '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">',
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
            <div class="woocommerce logo"></div>
            <div>
                <h3 class="sjl-gen-card-title">
                    <?php echo esc_html__('WooCommerce', 'simple-jwt-login'); ?>
                </h3>
                <p class="sjl-gen-card-desc">
                    <?php echo wp_kses(
                        sprintf(
                            /* translators: 1: opening anchor tag, 2: closing anchor tag */
                            __('JWT authentication for the %1$sWooCommerce%2$s REST API.', 'simple-jwt-login'),
                            '<a href="https://woocommerce.com/" target="_blank">',
                            '</a>'
                        ),
                        ['a' => ['href' => [], 'target' => []]]
                    ); ?>
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="hidden" name="woocommerce[enabled]" value="0">
            <label class="sjl-toggle-switch"
                   title="<?php echo esc_attr(__('Enable / Disable WooCommerce authentication', 'simple-jwt-login')); ?>"
                   style="margin: 0;">
                <input type="checkbox" id="woocommerce_enabled" name="woocommerce[enabled]" value="1"
                    <?php echo $jwtSettings->getIntegrationsSettings()->woocommerce()->isEnabled() ? 'checked' : ''; ?>>
                <span class="sjl-toggle-slider"></span>
            </label>
            <span class="sjl-toggle-enable-label">
                <?php echo esc_html(__('Enable', 'simple-jwt-login')); ?>
            </span>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="dashicons dashicons-cart"></span>
            <div>
                <h3 class="sjl-gen-card-title">
                    <?php echo esc_html__('Store API cart & checkout', 'simple-jwt-login'); ?>
                </h3>
                <p class="sjl-gen-card-desc">
                    <?php echo esc_html__(
                        'Allow header (Bearer) JWT requests to skip the Store API CSRF nonce, so a headless client can manage the cart and place orders with the token alone.',
                        'simple-jwt-login'
                    ); ?>
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="hidden" name="woocommerce[store_api_disable_nonce]" value="0">
            <label class="sjl-toggle-switch"
                   title="<?php echo esc_attr(__('Allow JWT cart & checkout without the Store API nonce', 'simple-jwt-login')); ?>"
                   style="margin: 0;">
                <input type="checkbox" id="woocommerce_store_api_disable_nonce"
                       name="woocommerce[store_api_disable_nonce]" value="1"
                    <?php echo $jwtSettings->getIntegrationsSettings()->woocommerce()->isStoreApiNonceDisabled() ? 'checked' : ''; ?>>
                <span class="sjl-toggle-slider"></span>
            </label>
            <span class="sjl-toggle-enable-label">
                <?php echo esc_html(__('Enable', 'simple-jwt-login')); ?>
            </span>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="notice notice-warning inline" style="margin: 0;">
            <p>
                <?php echo esc_html__(
                    'Security note: the nonce is WooCommerce\'s CSRF protection for the Store API. It is only skipped for tokens sent in the Authorization header (which browsers do not send automatically, so they are not exposed to CSRF) - cookie and URL tokens always keep the nonce. Leave this off unless you run a headless / decoupled storefront, and always serve the API over HTTPS.',
                    'simple-jwt-login'
                ); ?>
            </p>
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
        <p class="sjl-gen-card-desc">
            <?php echo esc_html__(
                'When a JWT is provided on WooCommerce REST API requests (/wc/v3, /wc/store, ...), the plugin authenticates the user before WooCommerce checks permissions - so you can use a JWT instead of consumer key / secret.',
                'simple-jwt-login'
            ); ?>
        </p>
        <p class="sjl-gen-card-desc" style="margin-top: 8px;">
            <?php echo esc_html__(
                'This also covers the Store API cart & checkout (/wc/store/v1/cart, /wc/store/v1/checkout). Those write endpoints normally require a CSRF nonce; enable the "Store API cart & checkout" option above to let header (Bearer) JWT requests skip it, so a headless client can manage the cart and place orders with the token alone.',
                'simple-jwt-login'
            ); ?>
        </p>
        <p class="sjl-gen-card-desc" style="margin-top: 8px;">
            <?php echo esc_html__(
                'This only applies to WooCommerce routes and works even when the global JWT middleware is disabled. Always send the token over HTTPS.',
                'simple-jwt-login'
            ); ?>
        </p>
        <p class="sjl-gen-card-desc" style="margin-top: 8px;">
            <?php echo wp_kses(
                sprintf(
                    /* translators: 1: opening anchor tag, 2: closing anchor tag */
                    __('Requires the %1$sWooCommerce%2$s plugin to be installed and activated.', 'simple-jwt-login'),
                    '<a href="https://woocommerce.com/" target="_blank">',
                    '</a>'
                ),
                ['a' => ['href' => [], 'target' => []]]
            ); ?>
        </p>
    </div>
</div>
