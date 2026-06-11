<?php

if (!defined('ABSPATH')) {
    /**
     * @phpstan-ignore-next-line
     */
    exit;
}

/**
 * @var \SimpleJWTLogin\Modules\SimpleJWTLoginSettings $jwtSettings
 * @var \SimpleJWTLogin\Modules\Settings\SettingsErrors $settingsErrors
 * @var int|null $errorCode
 */

$sjl3rdPartyApps = [
    [
        'id'                 => 'wpgraphql',
        'name'               => __('WPGraphQL', 'simple-jwt-login'),
        'desc'               => __('GraphQL authentication', 'simple-jwt-login'),
        'logo_class'         => 'wpgraphql',
        'enabled'            => $jwtSettings->getIntegrationsSettings()->wpgraphql()->isEnabled(),
        'plugin_installed'   => file_exists(WP_PLUGIN_DIR . '/wp-graphql/wp-graphql.php'),
        'plugin_activated'   => class_exists('\WPGraphQL'),
        'view'               => plugin_dir_path(__FILE__) . 'wpgraphql.php',
        'beta'               => false,
    ],
    [
        'id'                 => 'two_factor',
        'name'               => __('Two-Factor', 'simple-jwt-login'),
        'desc'               => __('2FA challenge before JWT issuance', 'simple-jwt-login'),
        'logo_class'         => 'two-factor',
        'enabled'            => $jwtSettings->getIntegrationsSettings()->twoFactor()->isEnabled(),
        'plugin_installed'   => file_exists(WP_PLUGIN_DIR . '/two-factor/two-factor.php'),
        'plugin_activated'   => class_exists('\Two_Factor_Core'),
        'view'               => plugin_dir_path(__FILE__) . 'two-factor.php',
        'beta'               => false,
    ],
    [
        'id'                 => 'force_login',
        'name'               => __('Force Login', 'simple-jwt-login'),
        'desc'               => __('Bypass Force Login for JWT endpoints', 'simple-jwt-login'),
        'logo_class'         => 'force-login',
        'enabled'            => $jwtSettings->getIntegrationsSettings()->forceLogin()->isEnabled(),
        'plugin_installed'   => file_exists(WP_PLUGIN_DIR . '/wp-force-login/wp-force-login.php'),
        'plugin_activated'   => class_exists('\ForceLogin'),
        'view'               => plugin_dir_path(__FILE__) . 'force-login.php',
        'beta'               => false,
    ],
];

$active3rdPartyApp = $sjl3rdPartyApps[0]['id'];

//phpcs:ignore WordPress.Security.NonceVerification.Recommended
if (!empty($_REQUEST['active_3rdparty_panel'])) {
    //phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $submitted3rdPartyApp = sanitize_text_field(wp_unslash($_REQUEST['active_3rdparty_panel']));
    if (in_array($submitted3rdPartyApp, array_column($sjl3rdPartyApps, 'id'), true)) {
        $active3rdPartyApp = $submitted3rdPartyApp;
    }
}

?>
<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-plugins"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Third-Party Integrations', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Connect Simple JWT Login with third-party WordPress plugins and tools.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body sjl-apps-body">

        <div class="sjl-apps-search-wrap">
            <span class="dashicons dashicons-search sjl-apps-search-icon"></span>
            <input type="text"
                   id="sjl-3rdparty-search"
                   class="sjl-apps-search"
                   placeholder="<?php echo esc_attr__('Filter integrations...', 'simple-jwt-login'); ?>"
                   autocomplete="off" />
        </div>

        <div class="sjl-apps-catalog">
            <?php foreach ($sjl3rdPartyApps as $sjl3rdPartyApp) :
                    $isActive = $sjl3rdPartyApp['id'] === $active3rdPartyApp;
				?>
                <div class="sjl-app-tile<?php echo $isActive ? ' active' : ''; ?>"
                    data-app="<?php echo esc_attr($sjl3rdPartyApp['id']); ?>"
                    data-name="<?php echo esc_attr(strtolower($sjl3rdPartyApp['name'])); ?>"
                    role="button"
                    tabindex="0"
                    aria-expanded="<?php echo $isActive ? 'true' : 'false'; ?>">
                    <div class="logo <?php echo esc_attr($sjl3rdPartyApp['logo_class']); ?>"></div>
                    <span class="sjl-app-tile-name"><?php echo esc_html($sjl3rdPartyApp['name']); ?></span>
                    <?php if (!$sjl3rdPartyApp['plugin_installed']) : ?>
                        <span class="sjl-app-tile-not-installed dashicons dashicons-warning"
                              title="<?php echo esc_attr__('Plugin not installed', 'simple-jwt-login'); ?>"></span>
                    <?php elseif (!$sjl3rdPartyApp['plugin_activated']) : ?>
                        <span class="sjl-app-tile-not-installed dashicons dashicons-warning"
                              title="<?php echo esc_attr__('Plugin not activated', 'simple-jwt-login'); ?>"></span>
                    <?php else : ?>
                        <span class="sjl-app-tile-dot <?php echo $sjl3rdPartyApp['enabled'] ? 'sjl-dot-on' : 'sjl-dot-off'; ?>"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="sjl-apps-no-results" style="display:none;">
            <?php echo esc_html__('No integrations found.', 'simple-jwt-login'); ?>
        </p>

        <input type="hidden" name="active_3rdparty_panel" id="active_3rdparty_panel"
               value="<?php echo esc_attr($active3rdPartyApp); ?>" />
        <div class="sjl-apps-panels">
            <?php
            foreach ($sjl3rdPartyApps as $sjl3rdPartyApp) {
                $isActive = $sjl3rdPartyApp['id'] === $active3rdPartyApp;
                $sjlPluginInstalled  = $sjl3rdPartyApp['plugin_installed'];
                $sjlPluginActivated = $sjl3rdPartyApp['plugin_activated'];
                ?>
                <div class="sjl-app-panel"
                    id="sjl-app-panel-<?php echo esc_attr($sjl3rdPartyApp['id']); ?>"
                    <?php echo $isActive ? '' : 'style="display:none;"'; ?>>
                    <?php include $sjl3rdPartyApp['view']; ?>
                </div>
                <?php
            }
            ?>
        </div>

    </div>
</div>
