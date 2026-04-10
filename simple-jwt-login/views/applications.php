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

$sjlApps = [
    [
        'id'         => 'google',
        'name'       => __('Google', 'simple-jwt-login'),
        'desc'       => __('OAuth 2.0', 'simple-jwt-login'),
        'logo_class' => 'google',
        'enabled'    => $jwtSettings->getApplicationsSettings()->google()->isEnabled(),
        'view'       => plugin_dir_path(__FILE__) . 'applications/google.php',
        'beta'       => true,
    ],
    [
        'id'         => 'auth0',
        'name'       => __('Auth0', 'simple-jwt-login'),
        'desc'       => __('OAuth 2.0 / OIDC', 'simple-jwt-login'),
        'logo_class' => 'auth0',
        'enabled'    => $jwtSettings->getApplicationsSettings()->auth0()->isEnabled(),
        'view'       => plugin_dir_path(__FILE__) . 'applications/auth0.php',
        'beta'       => true,
    ],
];
?>
<div class="sjl-apps-catalog">
    <?php foreach ($sjlApps as $i => $sjlApp) : ?>
    <div class="sjl-app-card<?php echo $i === 0 ? ' active' : ''; ?>"
         data-app="<?php echo esc_attr($sjlApp['id']); ?>"
         role="button"
         tabindex="0"
         aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>">
        <div class="sjl-app-card-logo">
            <div class="logo <?php echo esc_attr($sjlApp['logo_class']); ?>"></div>
        </div>
        <div class="sjl-app-card-name">
            <?php echo esc_html($sjlApp['name']); ?>
            <?php if ($sjlApp['beta']) : ?>
                <span class="beta"><?php echo esc_html(__('beta', 'simple-jwt-login')); ?></span>
            <?php endif; ?>
        </div>
        <span class="sjl-app-card-badge <?php echo $sjlApp['enabled'] ? 'sjl-badge-on' : 'sjl-badge-off'; ?>">
            <?php echo $sjlApp['enabled']
                ? esc_html(__('Enabled', 'simple-jwt-login'))
                : esc_html(__('Disabled', 'simple-jwt-login'));
            ?>
        </span>
    </div>
    <?php endforeach; ?>
</div>

<div class="sjl-apps-panels">
    <?php foreach ($sjlApps as $i => $sjlApp) : ?>
    <div class="sjl-app-panel"
         id="sjl-app-panel-<?php echo esc_attr($sjlApp['id']); ?>"
         <?php echo $i !== 0 ? 'style="display:none;"' : ''; ?>>
        <?php include $sjlApp['view']; ?>
    </div>
    <?php endforeach; ?>
</div>
