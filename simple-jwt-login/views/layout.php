<?php

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Helpers\ViewLoader;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Services\AuditLoggerService;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

$jwtSettings   = new SimpleJWTLoginSettings(new WordPressRepository());
$saved         = false;
$message       = __('Settings successfully saved', 'simple-jwt-login');
$showStatusBar = false;
$errorCode = null;


try {
    if (!empty($_POST)) {
        check_admin_referer(WordPressRepository::NONCE_NAME);
    }
    $saved         = $jwtSettings->watchForUpdates($_POST);
    $showStatusBar = $saved;
    if ($saved) {
        global $wpdb;
        $auditLogger = new AuditLoggerService(
            new AuditLogRepository($wpdb),
            $jwtSettings->getAuditLogSettings(),
            new ServerHelper($_SERVER)
        );
        $currentUser = wp_get_current_user();
        $diff        = $jwtSettings->getLastSettingsDiff();
        $auditLogger->log(
            AuditEvents::SETTINGS_SAVE_SUCCESS,
            $currentUser->ID ?: null,
            $currentUser->user_email ?: null,
            'success',
            !empty($diff) ? (string) json_encode($diff) : null
        );
    }
} catch (\Exception $e) {
    $showStatusBar = true;
    $message       = $e->getMessage();
    $errorCode     = $e->getCode();
}
$settingsErrors = new SettingsErrors();
$settingsPages = [
    [
        'id'   => 'simple-jwt-login-tab-dashboard',
        'name' => __('Dashboard', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_DASHBOARD,
        'index' => SettingsErrors::PREFIX_DASHBOARD,
    ],
    [
        'id'   => 'simple-jwt-login-tab-general',
        'name' => __('General', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_GENERAL,
        'index' => SettingsErrors::PREFIX_GENERAL,
    ],
    [
        'id'   => 'simple-jwt-login-tab-login',
        'name' => __('Login', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_LOGIN,
        'index' => SettingsErrors::PREFIX_LOGIN,
    ],
    [
        'id'   => 'simple-jwt-login-tab-register',
        'name' => __('Register User', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_REGISTER,
        'index' => SettingsErrors::PREFIX_REGISTER,
    ],
    [
        'id'   => 'simple-jwt-login-tab-delete',
        'name' => __('Delete User', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_DELETE,
        'index' => SettingsErrors::PREFIX_DELETE,
    ],
    [
        'id'   => 'simple-jwt-login-tab-reset-password',
        'name' => __('Reset Password', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_RESET_PASSWORD,
        'index' => SettingsErrors::PREFIX_RESET_PASSWORD,
    ],
    [
        'id'   => 'auth-tab-login',
        'name' => __('Authentication', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_AUTHENTICATION,
        'index' => SettingsErrors::PREFIX_AUTHENTICATION,
    ],
    [
        'id'   => 'simple-jwt-login-tab-refresh-token',
        'name' => __('Refresh Token', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_REFRESH_TOKEN,
        'index' => SettingsErrors::PREFIX_REFRESH_TOKEN,
    ],
    [
        'id'   => 'simple-jwt-login-tab-validate-token',
        'name' => __('Validate Token', 'simple-jwt-login'),
        'has_error' => false,
        'index' => SettingsErrors::PREFIX_VALIDATE_TOKEN,
    ],
    [
        'id'   => 'simple-jwt-login-tab-revoke-token',
        'name' => __('Revoke Token', 'simple-jwt-login'),
        'has_error' => false,
        'index' => SettingsErrors::PREFIX_REVOKE_TOKEN,
    ],
    [
        'id'   => 'simple-jwt-login-tab-auth-codes',
        'name' => __('Auth Codes', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_AUTH_CODES,
        'index' => SettingsErrors::PREFIX_AUTH_CODES,
    ],
    [
        'id'   => 'simple-jwt-login-tab-hooks',
        'name' => __('Hooks', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_HOOKS,
        'index' => SettingsErrors::PREFIX_HOOKS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-cors',
        'name' => __('CORS', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_CORS,
        'index' => SettingsErrors::PREFIX_CORS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-protect-endpoints',
        'name' => __('Protect endpoints', 'simple-jwt-login'),
        'has_error' =>  (
                $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_PROTECT_ENDPOINTS
        ),
        'index' => SettingsErrors::PREFIX_PROTECT_ENDPOINTS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-integrations',
        'name' => __('OAuth', 'simple-jwt-login'),
        'has_error' =>  (
            $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_APPLICATIONS
        ),
        'index' => SettingsErrors::PREFIX_APPLICATIONS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-webhooks',
        'name' => __('Webhooks', 'simple-jwt-login'),
        'has_error' => (
            $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_WEBHOOKS
        ),
        'index' => SettingsErrors::PREFIX_WEBHOOKS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-webhook-logs',
        'name' => __('Webhook Logs', 'simple-jwt-login'),
        'has_error' => false,
        'index' => SettingsErrors::PREFIX_WEBHOOK_LOGS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-audit-logs',
        'name' => __('Audit Logs', 'simple-jwt-login'),
        'has_error' => (
            $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_AUDIT_LOGS
        ),
        'index' => SettingsErrors::PREFIX_AUDIT_LOGS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-audit-log-logs',
        'name' => __('Audit Log Entries', 'simple-jwt-login'),
        'has_error' => false,
        'index' => SettingsErrors::PREFIX_AUDIT_LOG_LOGS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-api-keys',
        'name' => __('API Keys', 'simple-jwt-login'),
        'has_error' => false,
        'index' => SettingsErrors::PREFIX_API_KEYS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-3rd-party-apps',
        'name' => __('Third Party Integrations', 'simple-jwt-login'),
        'has_error' => false,
        'index' => SettingsErrors::PREFIX_3RD_PARTY_APPS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-jwt-decoder',
        'name' => __('JWT Decoder', 'simple-jwt-login'),
        'has_error' => false,
        'index' => SettingsErrors::PREFIX_JWT_DECODER,
    ],
];

$pagesByIndex = [];
foreach ($settingsPages as $p) {
    $pagesByIndex[$p['index']] = $p;
}

$sidebarGroups = [
    ['type' => 'item', 'index' => SettingsErrors::PREFIX_DASHBOARD, 'icon' => 'dashicons-dashboard'],
    ['type' => 'item', 'index' => SettingsErrors::PREFIX_GENERAL, 'icon' => 'dashicons-admin-settings'],
    [
        'type'  => 'group',
        'label' => __('Routes', 'simple-jwt-login'),
        'icon'  => 'dashicons-networking',
        'items' => [
            ['index' => SettingsErrors::PREFIX_LOGIN, 'icon' => 'dashicons-admin-users'],
            ['index' => SettingsErrors::PREFIX_REGISTER, 'name' => __('Register', 'simple-jwt-login'), 'icon' => 'dashicons-plus-alt'],
            ['index' => SettingsErrors::PREFIX_DELETE, 'name' => __('Delete', 'simple-jwt-login'), 'icon' => 'dashicons-trash'],
            ['index' => SettingsErrors::PREFIX_RESET_PASSWORD, 'icon' => 'dashicons-lock'],
            ['index' => SettingsErrors::PREFIX_AUTHENTICATION, 'name' => __('Authenticate', 'simple-jwt-login'), 'icon' => 'dashicons-shield'],
            ['index' => SettingsErrors::PREFIX_REFRESH_TOKEN, 'name' => __('Refresh Token', 'simple-jwt-login'), 'icon' => 'dashicons-update'],
            ['index' => SettingsErrors::PREFIX_VALIDATE_TOKEN, 'name' => __('Validate Token', 'simple-jwt-login'), 'icon' => 'dashicons-yes-alt'],
            ['index' => SettingsErrors::PREFIX_REVOKE_TOKEN, 'name' => __('Revoke Token', 'simple-jwt-login'), 'icon' => 'dashicons-dismiss'],
        ],
    ],
    [
        'type'  => 'group',
        'label' => __('Security', 'simple-jwt-login'),
        'icon'  => 'dashicons-shield',
        'items' => [
            ['index' => SettingsErrors::PREFIX_AUTH_CODES, 'icon' => 'dashicons-tickets-alt'],
            ['index' => SettingsErrors::PREFIX_PROTECT_ENDPOINTS, 'name' => __('Protect Endpoints', 'simple-jwt-login'), 'icon' => 'dashicons-shield-alt'],
            ['index' => SettingsErrors::PREFIX_CORS, 'icon' => 'dashicons-randomize'],
            ['index' => SettingsErrors::PREFIX_API_KEYS, 'name' => __('API Keys', 'simple-jwt-login'), 'icon' => 'dashicons-admin-network'],
        ],
    ],
    [
        'type'  => 'group',
        'label' => __('Integrations', 'simple-jwt-login'),
        'icon'  => 'dashicons-admin-plugins',
        'items' => [
            ['index' => SettingsErrors::PREFIX_APPLICATIONS, 'name' => __('OAuth', 'simple-jwt-login'), 'icon' => 'dashicons-cloud'],
            ['index' => SettingsErrors::PREFIX_3RD_PARTY_APPS, 'name' => __('Third Party Integrations', 'simple-jwt-login'), 'icon' => 'dashicons-admin-plugins'],
        ],
    ],
    [
        'type'  => 'group',
        'label' => __('Webhooks', 'simple-jwt-login'),
        'icon'  => 'dashicons-rest-api',
        'items' => [
            ['index' => SettingsErrors::PREFIX_WEBHOOKS, 'name' => __('Config', 'simple-jwt-login'), 'icon' => 'dashicons-admin-settings'],
            ['index' => SettingsErrors::PREFIX_WEBHOOK_LOGS, 'name' => __('Logs', 'simple-jwt-login'), 'icon' => 'dashicons-list-view'],
        ],
    ],
    [
        'type'  => 'group',
        'label' => __('Audit Logs', 'simple-jwt-login'),
        'icon'  => 'dashicons-backup',
        'items' => [
            ['index' => SettingsErrors::PREFIX_AUDIT_LOGS, 'name' => __('Config', 'simple-jwt-login'), 'icon' => 'dashicons-admin-settings'],
            ['index' => SettingsErrors::PREFIX_AUDIT_LOG_LOGS, 'name' => __('Logs', 'simple-jwt-login'), 'icon' => 'dashicons-list-view'],
        ],
    ],
    ['type' => 'item', 'index' => SettingsErrors::PREFIX_HOOKS, 'name' => __('Hooks', 'simple-jwt-login'), 'icon' => 'dashicons-admin-plugins'],
    ['type' => 'item', 'index' => SettingsErrors::PREFIX_JWT_DECODER, 'name' => __('JWT Decoder', 'simple-jwt-login'), 'icon' => 'dashicons-editor-code'],
];

?>
<div id="sjl-page-loader" aria-hidden="true">
    <div class="sjl-loader-spinner"></div>
</div>
<form method="post">
    <?php
    $activeTab = $settingsPages[0]['index'];
    $activeTabRaw = null;
    if (isset($_POST['active_tab'])) {
        check_admin_referer(WordPressRepository::NONCE_NAME);
        $activeTabRaw = absint(wp_unslash($_POST['active_tab']));
    } elseif (isset($_GET['active_tab'])) {
        $activeTabRaw = absint(wp_unslash($_GET['active_tab']));
    }
    if ($activeTabRaw !== null) {
        foreach ($settingsPages as $item) {
            if ($item['index'] === $activeTabRaw) {
                $activeTab = $activeTabRaw;
            }
        }
    }
    ?>
    <input type="hidden" name="active_tab" id="active_tab" value="<?php echo esc_attr($activeTab);?>"/>
    <input type="hidden" name="theme[mode]" id="sjl-theme-mode-input" value="<?php echo esc_attr($jwtSettings->getThemeSettings()->getMode()); ?>"/>
    <?php
    $jwtSettings
        ->getWordPressData()
        ->insertNonce(WordPressRepository::NONCE_NAME);
    ?>
    <div id="simple-jwt-login" class="wrapper" style="visibility:hidden">
		<?php
        if ($showStatusBar) {
            ?>
            <div class="row">
                <div class="col-md-12 mb-4 mt-3">
                    <div class="<?php echo $saved ? 'updated' : 'error' ?> notice my-acf-notice is-dismissible m-0">
                        <p>
                            <?php
                            if ($saved === false) {
                                ?>
                                <span class="simple-jwt-error">!</span>
                                <?php
                            } ?>
							<?php echo esc_html($message); ?>
                        </p>
                    </div>
                </div>
            </div>
			<?php
        }
        ?>
        <div class="">
            <div class="row main-title-container">
                <div class="col-md-8">
                    <h1 class="main-title">
                        <?php echo esc_html__('Simple JWT Login Settings', 'simple-jwt-login'); ?>
                    </h1>
                </div>
                <div class="col-md-4 sjl-header-actions text-right">
                    <button type="button" id="sjl-theme-toggle" class="sjl-theme-toggle">
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <span id="sjl-theme-label"><?php echo esc_html__('Dark mode', 'simple-jwt-login'); ?></span>
                    </button>
                    <input type="submit" class="btn btn-dark" value="<?php echo esc_attr__('Save', 'simple-jwt-login');?>">
                    <div class="sjl-plugin-version">v<?php echo esc_html(SIMPLE_JWT_LOGIN_VERSION); ?></div>
                </div>
            </div>
            <hr/>
            <div class="row">
                <div class="col-md-2 mb-3 sjl-sidebar-col">
                    <ul class="nav nav-pills flex-column nav-tabs" id="simple-jwt-login-tabs" role="tablist">
                        <?php
                        $sjlGroupIdx = 0;
                        foreach ($sidebarGroups as $groupEntry) : ?>
                            <?php if ($groupEntry['type'] === 'group') :
                                $sjlGroupId = 'sjlg-' . $sjlGroupIdx++;
                                ?>
                                <li class="sjl-nav-group-label"
                                    data-sjl-group="<?php echo esc_attr($sjlGroupId); ?>"
                                >
                                    <span class="sjl-nav-group-label-text">
                                        <span class="dashicons <?php echo esc_attr($groupEntry['icon']); ?> sjl-nav-group-icon" aria-hidden="true"></span>
                                        <span class="sjl-nav-label"><?php echo esc_html($groupEntry['label']); ?></span>
                                    </span>
                                    <button type="button" class="sjl-nav-group-toggle" aria-expanded="true">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                </li>
                                <?php foreach ($groupEntry['items'] as $item) :
                                    $idx      = $item['index'];
                                    $pg       = $pagesByIndex[$idx];
                                    $iName    = isset($item['name']) ? $item['name'] : $pg['name'];
                                    $isActive = (empty($errorCode) && $activeTab === $idx)
                                        || $settingsErrors->getSectionFromErrorCode($errorCode) === $idx;
                                    $linkId   = esc_attr($pg['id']) . '-tab';
                                    ?>
                                    <li class="nav-item sjl-nav-sub-item"
                                        data-sjl-group-item="<?php echo esc_attr($sjlGroupId); ?>"
                                    >
                                        <a class="nav-link <?php echo $isActive ? 'active' : ''; ?>"
                                           id="<?php echo esc_attr($linkId); ?>"
                                           data-toggle="tab"
                                           data-index="<?php echo esc_attr($idx); ?>"
                                           href="#<?php echo esc_attr($pg['id']); ?>"
                                           role="tab"
                                           aria-controls="<?php echo esc_attr($pg['id']); ?>"
                                           aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>"
                                           title="<?php echo esc_attr($iName); ?>"
                                        >
                                            <span class="dashicons <?php echo esc_attr($item['icon']); ?> sjl-nav-icon" aria-hidden="true"></span>
                                            <?php if ($pg['has_error']) : ?>
                                                <span class="simple-jwt-error">!</span>
                                            <?php endif; ?>
                                            <span class="sjl-nav-label"><?php echo esc_html($iName); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <?php
                                $idx      = $groupEntry['index'];
                                $pg       = $pagesByIndex[$idx];
                                $iName    = isset($groupEntry['name']) ? $groupEntry['name'] : $pg['name'];
                                $isActive = (empty($errorCode) && $activeTab === $idx)
                                    || $settingsErrors->getSectionFromErrorCode($errorCode) === $idx;
                                ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $isActive ? 'active' : ''; ?>"
                                       id="<?php echo esc_attr($pg['id']); ?>-tab"
                                       data-toggle="tab"
                                       data-index="<?php echo esc_attr($idx); ?>"
                                       href="#<?php echo esc_attr($pg['id']); ?>"
                                       role="tab"
                                       aria-controls="<?php echo esc_attr($pg['id']); ?>"
                                       aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>"
                                       title="<?php echo esc_attr($iName); ?>"
                                    >
                                        <span class="dashicons <?php echo esc_attr($groupEntry['icon']); ?> sjl-nav-icon" aria-hidden="true"></span>
                                        <?php if ($pg['has_error']) : ?>
                                            <span class="simple-jwt-error">!</span>
                                        <?php endif; ?>
                                        <span class="sjl-nav-label"><?php echo esc_html($iName); ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-md-10">
                    <div class="tab-content card-shadow" id="simple-jwt-login-tab-content">
						<?php
                        foreach ($settingsPages as $page) {
                            $index = $page['index'];
                            $isActive = (empty($errorCode) && ($activeTab === $page['index']))
                                ||  $settingsErrors->getSectionFromErrorCode($errorCode) === $index
                            ?>
                            <div class="tab-pane fade <?php echo $isActive ? 'active' : '' ?> show"
                                 id="<?php echo esc_attr($page['id']); ?>"
                                 role="tabpanel"
                                 aria-labelledby="<?php echo esc_attr($page['id']); ?>-tab"
                            >
								<?php
                                $viewMap = array(
                                    SettingsErrors::PREFIX_DASHBOARD       => 'dashboard-view.php',
                                    SettingsErrors::PREFIX_GENERAL         => 'general-view.php',
                                    SettingsErrors::PREFIX_LOGIN           => 'login-view.php',
                                    SettingsErrors::PREFIX_REGISTER        => 'register-view.php',
                                    SettingsErrors::PREFIX_DELETE          => 'delete-view.php',
                                    SettingsErrors::PREFIX_RESET_PASSWORD  => 'reset-password-view.php',
                                    SettingsErrors::PREFIX_AUTHENTICATION  => 'auth-view.php',
                                    SettingsErrors::PREFIX_REFRESH_TOKEN   => 'refresh-token-view.php',
                                    SettingsErrors::PREFIX_VALIDATE_TOKEN  => 'validate-token-view.php',
                                    SettingsErrors::PREFIX_REVOKE_TOKEN    => 'revoke-token-view.php',
                                    SettingsErrors::PREFIX_AUTH_CODES      => 'auth-codes-view.php',
                                    SettingsErrors::PREFIX_HOOKS           => 'hooks-view.php',
                                    SettingsErrors::PREFIX_CORS            => 'cors-view.php',
                                    SettingsErrors::PREFIX_PROTECT_ENDPOINTS => 'protect-endpoints-view.php',
                                    SettingsErrors::PREFIX_APPLICATIONS    => 'integrations/oauth/oauth-apps.php',
                                    SettingsErrors::PREFIX_AUDIT_LOGS      => 'audit-logs-view.php',
                                    SettingsErrors::PREFIX_AUDIT_LOG_LOGS  => 'audit-logs-logs-view.php',
                                    SettingsErrors::PREFIX_WEBHOOKS        => 'webhooks-view.php',
                                    SettingsErrors::PREFIX_WEBHOOK_LOGS    => 'webhooks-logs-view.php',
                                    SettingsErrors::PREFIX_API_KEYS        => 'api-keys-view.php',
                                    SettingsErrors::PREFIX_3RD_PARTY_APPS  => 'integrations/3rd-party/3rd-party-apps.php',
                                    SettingsErrors::PREFIX_JWT_DECODER     => 'jwt-decoder-view.php',
                                );
                                $viewLoader = new ViewLoader(plugin_dir_path(__FILE__));
                                $viewData = array(
                                    'jwtSettings'    => $jwtSettings,
                                    'settingsErrors' => $settingsErrors,
                                    'errorCode'      => $errorCode,
                                );
                                if (array_key_exists($page['index'], $viewMap)) {
                                    $viewLoader->render($viewMap[$page['index']], $viewData);
                                } else {
                                    echo esc_html__('View file does not exists.', 'simple-jwt-login');
                                }
                                ?>
                            </div>
							<?php
                        }
                        ?>
                    </div>
                </div>
                <!-- /.col-md-8 -->
            </div>
        </div>
        <!-- /.container -->
    </div>
</form>

<script>
(function () {
    window._sjlConfig = {
        theme: <?php echo wp_json_encode($jwtSettings->getThemeSettings()->getMode()); ?>
    };
}());
</script>

<div id="sjl-payload-claim-line" style="display:none">
    <div class="sjl-claims-row">
        <input type="text"
               name="custom_claims_payload[key][]"
               class="form-control sjl-auth-input"
               placeholder="<?php echo esc_attr__('e.g. department', 'simple-jwt-login'); ?>"
        />
        <input type="text"
               name="custom_claims_payload[value][]"
               class="form-control sjl-auth-input"
               placeholder="<?php echo esc_attr__('e.g. engineering', 'simple-jwt-login'); ?>"
        />
        <button type="button"
                class="sjl-endpoint-remove"
                onclick="sjlRemoveClaimRow(this)"
                title="<?php echo esc_attr__('Remove', 'simple-jwt-login'); ?>">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
</div>

<div id="sjl-header-claim-line" style="display:none">
    <div class="sjl-claims-row">
        <input type="text"
               name="custom_claims_header[key][]"
               class="form-control sjl-auth-input"
               placeholder="<?php echo esc_attr__('e.g. x-app-id', 'simple-jwt-login'); ?>"
        />
        <input type="text"
               name="custom_claims_header[value][]"
               class="form-control sjl-auth-input"
               placeholder="<?php echo esc_attr__('e.g. my-app', 'simple-jwt-login'); ?>"
        />
        <button type="button"
                class="sjl-endpoint-remove"
                onclick="sjlRemoveClaimRow(this)"
                title="<?php echo esc_attr__('Remove', 'simple-jwt-login'); ?>">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
</div>

<div id="code_line" style="display:none">
    <div class="auth_row sjl-auth-row">
        <input type="text"
               name="auth_codes[code][]"
               class="form-control sjl-auth-input"
               placeholder="<?php echo esc_attr__('Authentication Key', 'simple-jwt-login'); ?>"
        />
        <input type="text"
               name="auth_codes[role][]"
               class="form-control sjl-auth-input"
               placeholder="<?php echo esc_attr__(
                   'WordPress new user Role ( when new users are created )',
                   'simple-jwt-login'
               ); ?>"
        />
        <input type="text"
               name="auth_codes[expiration_date][]"
               class="form-control sjl-auth-input"
               placeholder="<?php echo esc_attr__(
                   'Expiration date: YYYY-MM-DD HH:MM:SS ( Example: 2020-12-23 23:34:59)',
                   'simple-jwt-login'
               ); ?>"
        />
        <button type="button"
                class="sjl-endpoint-remove"
                onclick="jwt_login_remove_auth_line(jQuery(this));"
                title="<?php echo esc_attr__('Remove', 'simple-jwt-login'); ?>">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
</div>
