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
                __('The %1$sWPGraphQL%2$s plugin is not installed. This integration will not work until the plugin is installed and activated.', 'simple-jwt-login'),
                '<a href="https://wordpress.org/plugins/wp-graphql/" target="_blank">',
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
                __('The %1$sWPGraphQL%2$s plugin is not activated. This integration will not work until the plugin is active.', 'simple-jwt-login'),
                '<a href="https://wordpress.org/plugins/wp-graphql/" target="_blank">',
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
            <div class="wpgraphql logo"></div>
            <div>
                <h3 class="sjl-gen-card-title">
                    <?php echo esc_html__('WPGraphQL', 'simple-jwt-login'); ?>
                </h3>
                <p class="sjl-gen-card-desc">
                    <?php echo wp_kses(
                        sprintf(
                            /* translators: 1: opening anchor tag, 2: closing anchor tag */
                            __('JWT authentication for %1$sWPGraphQL%2$s queries.', 'simple-jwt-login'),
                            '<a href="https://www.wpgraphql.com/" target="_blank">',
                            '</a>'
                        ),
                        ['a' => ['href' => [], 'target' => []]]
                    ); ?>
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="hidden" name="wpgraphql[enabled]" value="0">
            <label class="sjl-toggle-switch"
                   title="<?php echo esc_attr(__('Enable / Disable WPGraphQL authentication', 'simple-jwt-login')); ?>"
                   style="margin: 0;">
                <input type="checkbox" id="wpgraphql_enabled" name="wpgraphql[enabled]" value="1"
                    <?php echo $jwtSettings->getIntegrationsSettings()->wpgraphql()->isEnabled() ? 'checked' : ''; ?>>
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
        <span class="dashicons dashicons-info-outline"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('How it works', 'simple-jwt-login'); ?></h3>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <p class="sjl-gen-card-desc">
            <?php echo esc_html__(
                'When a JWT is provided on WPGraphQL queries, the plugin will authenticate the user before executing the query.',
                'simple-jwt-login'
            ); ?>
        </p>
        <p class="sjl-gen-card-desc" style="margin-top: 8px;">
            <?php echo wp_kses(
                sprintf(
                    /* translators: 1: opening anchor tag, 2: closing anchor tag */
                    __('Requires the %1$sWPGraphQL%2$s plugin to be installed and activated.', 'simple-jwt-login'),
                    '<a href="https://www.wpgraphql.com/" target="_blank">',
                    '</a>'
                ),
                ['a' => ['href' => [], 'target' => []]]
            ); ?>
        </p>
    </div>
</div>
