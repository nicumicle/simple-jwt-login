<?php
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */

$routes = [
    [
        'title'   => __('Login', 'simple-jwt-login'),
        'tooltip' => __('Allows users to authenticate automatically using a valid JWT token.', 'simple-jwt-login'),
        'icon'    => 'dashicons-migrate',
        'tab'     => SettingsErrors::PREFIX_LOGIN,
        'enabled' => $jwtSettings->getLoginSettings()->isAutologinEnabled(),
    ],
    [
        'title'   => __('Register User', 'simple-jwt-login'),
        'tooltip' => __('Enables user registration through the JWT register endpoint.', 'simple-jwt-login'),
        'icon'    => 'dashicons-plus-alt2',
        'tab'     => SettingsErrors::PREFIX_REGISTER,
        'enabled' => $jwtSettings->getRegisterSettings()->isRegisterAllowed(),
    ],
    [
        'title'   => __('Delete User', 'simple-jwt-login'),
        'tooltip' => __('Allows users to delete their account using a JWT-authenticated request.', 'simple-jwt-login'),
        'icon'    => 'dashicons-trash',
        'tab'     => SettingsErrors::PREFIX_DELETE,
        'enabled' => $jwtSettings->getDeleteUserSettings()->isDeleteAllowed(),
    ],
    [
        'title'   => __('Reset Password', 'simple-jwt-login'),
        'tooltip' => __('Allows users to reset their password using a secure JWT endpoint.', 'simple-jwt-login'),
        'icon'    => 'dashicons-lock',
        'tab'     => SettingsErrors::PREFIX_RESET_PASSWORD,
        'enabled' => $jwtSettings->getResetPasswordSettings()->isResetPasswordEnabled(),
    ],
];

$securityCards = [
    [
        'title'   => __('Authentication', 'simple-jwt-login'),
        'tooltip' => __('Enables JWT authentication for protected REST API endpoints.', 'simple-jwt-login'),
        'icon'    => 'dashicons-shield',
        'tab'     => SettingsErrors::PREFIX_AUTHENTICATION,
        'enabled' => $jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled(),
    ],
    [
        'title'   => __('CORS', 'simple-jwt-login'),
        'tooltip' => __('Controls whether cross-origin requests are allowed for JWT endpoints.', 'simple-jwt-login'),
        'icon'    => 'dashicons-admin-site-alt3',
        'tab'     => SettingsErrors::PREFIX_CORS,
        'enabled' => $jwtSettings->getCorsSettings()->isCorsEnabled(),
    ],
    [
        'title'   => __('Protect Endpoints', 'simple-jwt-login'),
        'tooltip' => __('Restricts selected REST API endpoints to authenticated JWT requests only.', 'simple-jwt-login'),
        'icon'    => 'dashicons-shield-alt',
        'tab'     => SettingsErrors::PREFIX_PROTECT_ENDPOINTS,
        'enabled' => $jwtSettings->getProtectEndpointsSettings()->isEnabled(),
    ],
    [
        'title'   => __('Auth Codes', 'simple-jwt-login'),
        'tooltip' => __('Displays the total number of active authentication codes.', 'simple-jwt-login'),
        'icon'    => 'dashicons-admin-network',
        'tab'     => SettingsErrors::PREFIX_AUTH_CODES,
        'count'   => count($jwtSettings->getAuthCodesSettings()->getAuthCodes()),
    ],
];

$hooksCount = count($jwtSettings->getHooksSettings()->getEnabledHooks());
?>

<div class="sjl-dashboard">

    <div class="sjl-dash-section">
        <h3 class="sjl-dash-section-title">
            <span class="dashicons dashicons-rest-api"></span>
            <?php echo esc_html(__('Routes', 'simple-jwt-login')); ?>
        </h3>
        <p class="sjl-dash-section-desc">
            <?php echo esc_html(__('REST API endpoints exposed by this plugin.', 'simple-jwt-login')); ?>
        </p>
        <div class="row">
            <?php foreach ($routes as $card) : ?>
                <div class="col-lg-3 col-md-6 col-xs-12">
                    <div class="sjl-dash-card card card-shadow" data-sjl-tab="<?php echo esc_attr($card['tab']); ?>">
                        <div class="sjl-dash-card-icon">
                            <span class="dashicons <?php echo esc_attr($card['icon']); ?>"></span>
                        </div>
                        <div class="sjl-dash-card-title">
                            <?php echo esc_html($card['title']); ?>
                            <span
                                class="dashicons dashicons-info-outline sjl-tooltip sjl-dash-info"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="<?php echo esc_attr($card['tooltip']); ?>"
                            ></span>
                        </div>
                        <div class="sjl-dash-card-status">
                            <?php if ($card['enabled']) : ?>
                                <span class="sjl-badge sjl-badge-on">
                                    <?php echo esc_html(__('Enabled', 'simple-jwt-login')); ?>
                                </span>
                            <?php else : ?>
                                <span class="sjl-badge sjl-badge-off">
                                    <?php echo esc_html(__('Disabled', 'simple-jwt-login')); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="sjl-dash-card-link">
                            <?php echo esc_html(__('Configure', 'simple-jwt-login')); ?>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="sjl-dash-section">
        <h3 class="sjl-dash-section-title">
            <span class="dashicons dashicons-shield-alt"></span>
            <?php echo esc_html(__('Security & Access', 'simple-jwt-login')); ?>
        </h3>
        <p class="sjl-dash-section-desc">
            <?php echo esc_html(__('Controls for authentication, authorization, and access restrictions.', 'simple-jwt-login')); ?>
        </p>
        <div class="row">
            <?php foreach ($securityCards as $card) : ?>
                <div class="col-lg-3 col-md-6 col-xs-12">
                    <div class="sjl-dash-card card card-shadow" data-sjl-tab="<?php echo esc_attr($card['tab']); ?>">
                        <div class="sjl-dash-card-icon">
                            <span class="dashicons <?php echo esc_attr($card['icon']); ?>"></span>
                        </div>
                        <div class="sjl-dash-card-title">
                            <?php echo esc_html($card['title']); ?>
                            <span
                                class="dashicons dashicons-info-outline sjl-tooltip sjl-dash-info"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="<?php echo esc_attr($card['tooltip']); ?>"
                            ></span>
                        </div>
                        <div class="sjl-dash-card-status">
                            <?php if (isset($card['count'])) : ?>
                                <span class="sjl-badge sjl-badge-<?php echo $card['count'] > 0 ? 'count' : 'off'; ?>">
                                    <?php echo esc_html($card['count'] . ' ' . __('active', 'simple-jwt-login')); ?>
                                </span>
                            <?php elseif ($card['enabled']) : ?>
                                <span class="sjl-badge sjl-badge-on">
                                    <?php echo esc_html(__('Enabled', 'simple-jwt-login')); ?>
                                </span>
                            <?php else : ?>
                                <span class="sjl-badge sjl-badge-off">
                                    <?php echo esc_html(__('Disabled', 'simple-jwt-login')); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="sjl-dash-card-link">
                            <?php echo esc_html(__('Configure', 'simple-jwt-login')); ?>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="sjl-dash-section">
        <h3 class="sjl-dash-section-title">
            <span class="dashicons dashicons-admin-plugins"></span>
            <?php echo esc_html(__('Configuration', 'simple-jwt-login')); ?>
        </h3>
        <p class="sjl-dash-section-desc">
            <?php echo esc_html(__('Plugin-wide settings and integrations.', 'simple-jwt-login')); ?>
        </p>
        <div class="row">
            <div class="col-lg-3 col-md-6 col-xs-12">
                <div class="sjl-dash-card card card-shadow" data-sjl-tab="<?php echo esc_attr(SettingsErrors::PREFIX_HOOKS); ?>">
                    <div class="sjl-dash-card-icon">
                        <span class="dashicons dashicons-hammer"></span>
                    </div>
                    <div class="sjl-dash-card-title">
                        <?php echo esc_html(__('Hooks', 'simple-jwt-login')); ?>
                        <span
                            class="dashicons dashicons-info-outline sjl-tooltip sjl-dash-info"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="<?php echo esc_attr__(
                                'Shows how many Simple-JWT-Login hooks are currently enabled.',
                                'simple-jwt-login'
                            ); ?>"
                        ></span>
                    </div>
                    <div class="sjl-dash-card-status">
                        <span class="sjl-badge sjl-badge-<?php echo $hooksCount > 0 ? 'count' : 'off'; ?>">
                            <?php echo esc_html($hooksCount . ' ' . __('active', 'simple-jwt-login')); ?>
                        </span>
                    </div>
                    <div class="sjl-dash-card-link">
                        <?php echo esc_html(__('Configure', 'simple-jwt-login')); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-12">
                <div class="sjl-dash-card card card-shadow" data-sjl-tab="<?php echo esc_attr(SettingsErrors::PREFIX_GENERAL); ?>">
                    <div class="sjl-dash-card-icon">
                        <span class="dashicons dashicons-admin-settings"></span>
                    </div>
                    <div class="sjl-dash-card-title">
                        <?php echo esc_html(__('General Settings', 'simple-jwt-login')); ?>
                        <span
                            class="dashicons dashicons-info-outline sjl-tooltip sjl-dash-info"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="<?php echo esc_attr__(
                                'Configure JWT decryption key, algorithm, and other global plugin settings.',
                                'simple-jwt-login'
                            ); ?>"
                        ></span>
                    </div>
                    <div class="sjl-dash-card-status">
                        <span class="sjl-badge sjl-badge-on">
                            <?php echo esc_html(__('Active', 'simple-jwt-login')); ?>
                        </span>
                    </div>
                    <div class="sjl-dash-card-link">
                        <?php echo esc_html(__('Configure', 'simple-jwt-login')); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-xs-12">
                <div class="sjl-dash-card card card-shadow" data-sjl-tab="<?php echo esc_attr(SettingsErrors::PREFIX_APPLICATIONS); ?>">
                    <div class="sjl-dash-card-icon">
                        <span class="dashicons dashicons-share"></span>
                    </div>
                    <div class="sjl-dash-card-title">
                        <?php echo esc_html(__('Applications', 'simple-jwt-login')); ?>
                        <span
                            class="dashicons dashicons-info-outline sjl-tooltip sjl-dash-info"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="<?php echo esc_attr__(
                                'Configure third-party application integrations such as Google.',
                                'simple-jwt-login'
                            ); ?>"
                        ></span>
                    </div>
                    <div class="sjl-dash-card-status">
                        <span class="sjl-badge sjl-badge-on">
                            <?php echo esc_html(__('Active', 'simple-jwt-login')); ?>
                        </span>
                    </div>
                    <div class="sjl-dash-card-link">
                        <?php echo esc_html(__('Configure', 'simple-jwt-login')); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
