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
        'id'         => 'wpgraphql',
        'name'       => __('WPGraphQL', 'simple-jwt-login'),
        'desc'       => __('GraphQL authentication', 'simple-jwt-login'),
        'logo_class' => 'wpgraphql',
        'enabled'    => $jwtSettings->getApplicationsSettings()->wpgraphql()->isEnabled(),
        'view'       => plugin_dir_path(__FILE__) . 'wpgraphql.php',
        'beta'       => true,
    ],
];

$active3rdPartyApp = $sjl3rdPartyApps[0]['id'];

if (!empty($_REQUEST['active_3rdparty_panel'])) {
    $submitted3rdPartyApp = sanitize_text_field($_REQUEST['active_3rdparty_panel']);
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

        <div class="sjl-apps-catalog">
            <?php foreach ($sjl3rdPartyApps as $sjl3rdPartyApp) : ?>
                <div class="sjl-app-tile active"
                    data-app="<?php echo esc_attr($sjl3rdPartyApp['id']); ?>"
                    role="button"
                    tabindex="0"
                    aria-expanded="true">
                    <div class="logo <?php echo esc_attr($sjl3rdPartyApp['logo_class']); ?>"></div>
                    <span class="sjl-app-tile-name"><?php echo esc_html($sjl3rdPartyApp['name']); ?></span>
                    <span class="sjl-app-tile-dot <?php echo $sjl3rdPartyApp['enabled'] ? 'sjl-dot-on' : 'sjl-dot-off'; ?>"></span>
                </div>
            <?php endforeach; ?>
        </div>

        <input type="hidden" name="active_3rdparty_panel" id="active_3rdparty_panel"
               value="<?php echo esc_attr($active3rdPartyApp); ?>" />
        <div class="sjl-apps-panels">
            <?php
            foreach ($sjl3rdPartyApps as $sjl3rdPartyApp) {
                ?>
                <div class="sjl-app-panel"
                    id="sjl-app-panel-<?php echo esc_attr($sjl3rdPartyApp['id']); ?>">
                    <?php include $sjl3rdPartyApp['view']; ?>
                </div>
                <?php
            }
            ?>
        </div>

    </div>
</div>
