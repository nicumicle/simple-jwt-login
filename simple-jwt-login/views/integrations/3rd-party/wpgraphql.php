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
 */
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="wpgraphql logo"></div>
            <div>
                <h3 class="sjl-gen-card-title">
                    <?php echo esc_html__('WPGraphQL', 'simple-jwt-login'); ?>
                    <span class="beta">beta</span>
                </h3>
                <p class="sjl-gen-card-desc">
                    <?php echo wp_kses(
                        sprintf(
                            __('JWT authentication for %sWPGraphQL%s queries.', 'simple-jwt-login'),
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
            <span style="font-size: 12px; color: #555; white-space: nowrap;">
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
                    __('Requires the %sWPGraphQL plugin%s to be installed and activated.', 'simple-jwt-login'),
                    '<a href="https://www.wpgraphql.com/" target="_blank">',
                    '</a>'
                ),
                ['a' => ['href' => [], 'target' => []]]
            ); ?>
        </p>
    </div>
</div>
