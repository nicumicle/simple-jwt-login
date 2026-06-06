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
    [
        'title'   => __('Authentication', 'simple-jwt-login'),
        'tooltip' => __('Enables JWT authentication for protected REST API endpoints.', 'simple-jwt-login'),
        'icon'    => 'dashicons-shield',
        'tab'     => SettingsErrors::PREFIX_AUTHENTICATION,
        'enabled' => $jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled(),
    ],
    [
        'title'   => __('Refresh Token', 'simple-jwt-login'),
        'tooltip' => __('Allows clients to exchange a refresh token for a new JWT without re-authenticating.', 'simple-jwt-login'),
        'icon'    => 'dashicons-update',
        'tab'     => SettingsErrors::PREFIX_REFRESH_TOKEN,
        'enabled' => $jwtSettings->getAuthenticationSettings()->isRefreshTokenEnabled(),
    ],
    [
        'title'   => __('Validate Token', 'simple-jwt-login'),
        'tooltip' => __('Provides an endpoint to verify whether a given JWT is valid.', 'simple-jwt-login'),
        'icon'    => 'dashicons-yes-alt',
        'tab'     => SettingsErrors::PREFIX_VALIDATE_TOKEN,
        'enabled' => $jwtSettings->getAuthenticationSettings()->isValidateTokenEnabled(),
    ],
    [
        'title'   => __('Revoke Token', 'simple-jwt-login'),
        'tooltip' => __('Allows clients to invalidate a JWT or refresh token before it expires.', 'simple-jwt-login'),
        'icon'    => 'dashicons-dismiss',
        'tab'     => SettingsErrors::PREFIX_REVOKE_TOKEN,
        'enabled' => $jwtSettings->getAuthenticationSettings()->isRevokeTokenEnabled(),
    ],
];

$securityCards = [
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
        'icon'    => 'dashicons-tickets-alt',
        'tab'     => SettingsErrors::PREFIX_AUTH_CODES,
        'count'   => count($jwtSettings->getAuthCodesSettings()->getAuthCodes()),
    ],
    [
        'title'   => __('API Keys', 'simple-jwt-login'),
        'tooltip' => __('Allow external clients to authenticate using scoped API keys instead of JWTs.', 'simple-jwt-login'),
        'icon'    => 'dashicons-admin-network',
        'tab'     => SettingsErrors::PREFIX_API_KEYS,
        'enabled' => $jwtSettings->getApiKeysSettings()->isEnabled(),
    ],
];

$integrations    = $jwtSettings->getIntegrationsSettings();
$oauthCount      = (int) $integrations->google()->isEnabled()
    + (int) $integrations->auth0()->isEnabled()
    + (int) $integrations->facebook()->isEnabled()
    + (int) $integrations->github()->isEnabled();
$thirdPartyCount = (int) $integrations->wpgraphql()->isEnabled()
    + (int) $integrations->twoFactor()->isEnabled()
    + (int) $integrations->forceLogin()->isEnabled();

$configCards = [
    [
        'title'   => __('General Settings', 'simple-jwt-login'),
        'tooltip' => __('Configure JWT decryption key, algorithm, and other global plugin settings.', 'simple-jwt-login'),
        'icon'    => 'dashicons-admin-settings',
        'tab'     => SettingsErrors::PREFIX_GENERAL,
        'link'    => __('Configure', 'simple-jwt-login'),
    ],
    [
        'title'       => __('OAuth', 'simple-jwt-login'),
        'tooltip'     => __('Configure OAuth providers such as Google, Facebook, GitHub, and Auth0.', 'simple-jwt-login'),
        'icon'        => 'dashicons-share',
        'tab'         => SettingsErrors::PREFIX_APPLICATIONS,
        'count' => $oauthCount,
    ],
    [
        'title'       => __('Third Party Integrations', 'simple-jwt-login'),
        'tooltip'     => __('Configure third-party plugin integrations such as WPGraphQL and Two-Factor.', 'simple-jwt-login'),
        'icon'        => 'dashicons-admin-plugins',
        'tab'         => SettingsErrors::PREFIX_3RD_PARTY_APPS,
        'count' => $thirdPartyCount,
    ],
    [
        'title'   => __('Hooks', 'simple-jwt-login'),
        'tooltip' => __('Shows how many Simple-JWT-Login hooks are currently enabled.', 'simple-jwt-login'),
        'icon'    => 'dashicons-hammer',
        'tab'     => SettingsErrors::PREFIX_HOOKS,
        'count'   => count($jwtSettings->getHooksSettings()->getEnabledHooks()),
    ],
];

$monitoringCards = [
    [
        'title'   => __('Webhooks', 'simple-jwt-login'),
        'tooltip' => __('Fire HTTP callbacks on plugin events such as login, register, or auth.', 'simple-jwt-login'),
        'icon'    => 'dashicons-networking',
        'tab'     => SettingsErrors::PREFIX_WEBHOOKS,
        'count'   => count($jwtSettings->getWebhooksSettings()->getWebhooks()),
        'count_label' => __('configured', 'simple-jwt-login'),
    ],
    [
        'title'   => __('Webhook Logs', 'simple-jwt-login'),
        'tooltip' => __('View the history of outbound webhook calls and their results.', 'simple-jwt-login'),
        'icon'    => 'dashicons-list-view',
        'tab'     => SettingsErrors::PREFIX_WEBHOOK_LOGS,
        'enabled' => $jwtSettings->getWebhooksSettings()->isWebhookLogsEnabled(),
        'link'    => __('View Logs', 'simple-jwt-login'),
    ],
    [
        'title'   => __('Audit Logs', 'simple-jwt-login'),
        'tooltip' => __('Record plugin events such as logins, registrations, and settings changes for auditing.', 'simple-jwt-login'),
        'icon'    => 'dashicons-clipboard',
        'tab'     => SettingsErrors::PREFIX_AUDIT_LOGS,
        'enabled' => $jwtSettings->getAuditLogSettings()->isEnabled(),
    ],
    [
        'title'   => __('Audit Log Entries', 'simple-jwt-login'),
        'tooltip' => __('Browse recorded audit log entries for this plugin.', 'simple-jwt-login'),
        'icon'    => 'dashicons-search',
        'tab'     => SettingsErrors::PREFIX_AUDIT_LOG_LOGS,
        'enabled' => $jwtSettings->getAuditLogSettings()->isEnabled(),
        'link'    => __('View Entries', 'simple-jwt-login'),
    ],
];

/**
 * @param array{title: string, tooltip: string, icon: string, tab: int, enabled?: bool, count?: int, count_label?: string, link?: string} $card
 */
function sjl_render_dash_card(array $card): void
{
    $link       = isset($card['link']) ? $card['link'] : __('Configure', 'simple-jwt-login');
    $hasCount   = array_key_exists('count', $card);
    $countLabel = isset($card['count_label']) ? $card['count_label'] : __('active', 'simple-jwt-login');
    ?>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="sjl-dash-card card card-shadow" data-sjl-tab="<?php echo esc_attr((string) $card['tab']); ?>">
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
                <?php if ($hasCount) : ?>
                    <span class="sjl-badge sjl-badge-<?php echo $card['count'] > 0 ? 'count' : 'off'; ?>">
                        <?php echo esc_html($card['count'] . ' ' . $countLabel); ?>
                    </span>
                <?php elseif (isset($card['enabled'])) : ?>
                    <span class="sjl-badge sjl-badge-<?php echo $card['enabled'] ? 'on' : 'off'; ?>">
                        <?php echo esc_html($card['enabled'] ? __('Enabled', 'simple-jwt-login') : __('Disabled', 'simple-jwt-login')); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="sjl-dash-card-link">
                <?php echo esc_html($link); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </div>
        </div>
    </div>
    <?php
}
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
                <?php sjl_render_dash_card($card); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="sjl-dash-section">
        <h3 class="sjl-dash-section-title">
            <span class="dashicons dashicons-shield-alt"></span>
            <?php echo esc_html(__('Security', 'simple-jwt-login')); ?>
        </h3>
        <p class="sjl-dash-section-desc">
            <?php echo esc_html(__('Access control, CORS policy, and authentication codes.', 'simple-jwt-login')); ?>
        </p>
        <div class="row">
            <?php foreach ($securityCards as $card) : ?>
                <?php sjl_render_dash_card($card); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="sjl-dash-section">
        <h3 class="sjl-dash-section-title">
            <span class="dashicons dashicons-admin-plugins"></span>
            <?php echo esc_html(__('Configuration', 'simple-jwt-login')); ?>
        </h3>
        <p class="sjl-dash-section-desc">
            <?php echo esc_html(__('Global plugin settings, third-party integrations, and WordPress hooks.', 'simple-jwt-login')); ?>
        </p>
        <div class="row">
            <?php foreach ($configCards as $card) : ?>
                <?php sjl_render_dash_card($card); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="sjl-dash-section">
        <h3 class="sjl-dash-section-title">
            <span class="dashicons dashicons-chart-area"></span>
            <?php echo esc_html(__('Monitoring & Logs', 'simple-jwt-login')); ?>
        </h3>
        <p class="sjl-dash-section-desc">
            <?php echo esc_html(__('Webhooks and audit logging for plugin events.', 'simple-jwt-login')); ?>
        </p>
        <div class="row">
            <?php foreach ($monitoringCards as $card) : ?>
                <?php sjl_render_dash_card($card); ?>
            <?php endforeach; ?>
        </div>
    </div>

</div>
