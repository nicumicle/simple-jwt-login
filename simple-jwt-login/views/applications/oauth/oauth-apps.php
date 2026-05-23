<?php

if (!defined('ABSPATH')) {
    /**
     * @phpstan-ignore-next-line
     */
    exit;
}

/**
 * @var \SimpleJWTLogin\Modules\Settings\SettingsErrors $settingsErrors
 * @var \SimpleJWTLogin\Modules\SimpleJWTLoginSettings $jwtSettings
 * @var int|null $errorCode
 */

use SimpleJWTLogin\Modules\Settings\ApplicationsSettings;

$sjlApps = [
    [
        'id'         => 'google',
        'name'       => __('Google', 'simple-jwt-login'),
        'desc'       => __('OAuth 2.0', 'simple-jwt-login'),
        'logo_class' => 'google',
        'enabled'    => $jwtSettings->getApplicationsSettings()->google()->isEnabled(),
        'view'       => plugin_dir_path(__FILE__) . 'google.php',
        'beta'       => true,
    ],
    [
        'id'         => 'auth0',
        'name'       => __('Auth0', 'simple-jwt-login'),
        'desc'       => __('OAuth 2.0 / OIDC', 'simple-jwt-login'),
        'logo_class' => 'auth0',
        'enabled'    => $jwtSettings->getApplicationsSettings()->auth0()->isEnabled(),
        'view'       => plugin_dir_path(__FILE__) . 'auth0.php',
        'beta'       => true,
    ],
];

$activeApp = $sjlApps[0]['id'];

if (!empty($_REQUEST['active_app_panel'])) {
    $submittedApp = sanitize_text_field($_REQUEST['active_app_panel']);
    if (in_array($submittedApp, array_column($sjlApps, 'id'), true)) {
        $activeApp = $submittedApp;
    }
}

$sjlCurrentLayout = $jwtSettings->getApplicationsSettings()->getLoginButtonLayout();

$sjlLayouts = [
    [
        'value' => ApplicationsSettings::LAYOUT_STACKED,
        'label' => __('Stacked', 'simple-jwt-login'),
        'desc'  => __('One button per line', 'simple-jwt-login'),
    ],
    [
        'value' => ApplicationsSettings::LAYOUT_INLINE,
        'label' => __('Side by side', 'simple-jwt-login'),
        'desc'  => __('Buttons in a row', 'simple-jwt-login'),
    ],
    [
        'value' => ApplicationsSettings::LAYOUT_ICON_STACKED,
        'label' => __('Icons stacked', 'simple-jwt-login'),
        'desc'  => __('Icon only, one per line', 'simple-jwt-login'),
    ],
    [
        'value' => ApplicationsSettings::LAYOUT_ICON_INLINE,
        'label' => __('Icons side by side', 'simple-jwt-login'),
        'desc'  => __('Icon only, in a row', 'simple-jwt-login'),
    ],
];
?>
<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-align-center"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Login Page Button Layout', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Choose how OAuth buttons are displayed on the WordPress login page.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-layout-picker">
            <?php foreach ($sjlLayouts as $sjlLayout) : ?>
                <?php $isSelected = $sjlLayout['value'] === $sjlCurrentLayout; ?>
                <label class="sjl-layout-option<?php echo $isSelected ? ' selected' : ''; ?>">
                    <input type="radio"
                           name="login_button_layout"
                           value="<?php echo esc_attr($sjlLayout['value']); ?>"
                           <?php echo $isSelected ? 'checked' : ''; ?> />
                    <div class="sjl-layout-preview sjl-layout-preview--<?php echo esc_attr($sjlLayout['value']); ?>">
                        <?php if ($sjlLayout['value'] === ApplicationsSettings::LAYOUT_STACKED) : ?>
                            <span class="sjl-lp-btn"><span class="sjl-lp-icon"></span><span class="sjl-lp-text"></span></span>
                            <span class="sjl-lp-btn"><span class="sjl-lp-icon"></span><span class="sjl-lp-text"></span></span>
                        <?php elseif ($sjlLayout['value'] === ApplicationsSettings::LAYOUT_INLINE) : ?>
                            <div class="sjl-lp-row">
                                <span class="sjl-lp-btn"><span class="sjl-lp-icon"></span><span class="sjl-lp-text"></span></span>
                                <span class="sjl-lp-btn"><span class="sjl-lp-icon"></span><span class="sjl-lp-text"></span></span>
                            </div>
                        <?php elseif ($sjlLayout['value'] === ApplicationsSettings::LAYOUT_ICON_STACKED) : ?>
                            <span class="sjl-lp-btn sjl-lp-btn--icon"><span class="sjl-lp-icon"></span></span>
                            <span class="sjl-lp-btn sjl-lp-btn--icon"><span class="sjl-lp-icon"></span></span>
                        <?php else : ?>
                            <div class="sjl-lp-row">
                                <span class="sjl-lp-btn sjl-lp-btn--icon"><span class="sjl-lp-icon"></span></span>
                                <span class="sjl-lp-btn sjl-lp-btn--icon"><span class="sjl-lp-icon"></span></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="sjl-layout-option-label"><?php echo esc_html($sjlLayout['label']); ?></div>
                    <div class="sjl-layout-option-desc"><?php echo esc_html($sjlLayout['desc']); ?></div>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('OAuth Applications', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Configure OAuth 2.0 providers to allow users to sign in with their existing accounts.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body sjl-apps-body">

        <div class="sjl-apps-catalog">
            <?php foreach ($sjlApps as $sjlApp) : ?>
                <?php $isActive = $sjlApp['id'] === $activeApp; ?>
                <div class="sjl-app-tile<?php echo $isActive ? ' active' : ''; ?>"
                    data-app="<?php echo esc_attr($sjlApp['id']); ?>"
                    role="button"
                    tabindex="0"
                    aria-expanded="<?php echo $isActive ? 'true' : 'false'; ?>">
                    <div class="logo <?php echo esc_attr($sjlApp['logo_class']); ?>"></div>
                    <span class="sjl-app-tile-name"><?php echo esc_html($sjlApp['name']); ?></span>
                    <span class="sjl-app-tile-dot <?php echo $sjlApp['enabled'] ? 'sjl-dot-on' : 'sjl-dot-off'; ?>"></span>
                </div>
            <?php endforeach; ?>
        </div>

        <input type="hidden" name="active_app_panel" id="active_app_panel" value="<?php echo esc_attr($activeApp); ?>" />
        <div class="sjl-apps-panels">
            <?php
            foreach ($sjlApps as $sjlApp) {
                $isActive = $sjlApp['id'] === $activeApp;
                ?>
                <div class="sjl-app-panel"
                    id="sjl-app-panel-<?php echo esc_attr($sjlApp['id']); ?>"
                    <?php echo $isActive ? '' : 'style="display:none;"'; ?>>
                    <?php include $sjlApp['view']; ?>
                </div>
                <?php
            }
            ?>
        </div>

    </div>
</div>
