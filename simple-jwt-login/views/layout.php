<?php

use SimpleJWTLogin\Helpers\ViewLoader;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\Settings\SettingsTabRegistry;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * All objects below are prepared by SimpleJWTLogin\Plugin\AdminUI and injected
 * into this view - the layout never instantiates services or repositories.
 *
 * @var SimpleJWTLoginSettings $jwtSettings
 * @var bool                   $saved
 * @var string                 $message
 * @var bool                   $showStatusBar
 * @var int|null               $errorCode
 * @var SettingsErrors         $settingsErrors
 * @var AuditLogRepository     $auditLogRepository
 * @var WebhookLogRepository   $webhookLogRepository
 * @var ApiKeyRepository       $apiKeyRepository
 */

$settingsPages = [];
foreach (SettingsTabRegistry::pages() as $sjlTab) {
    $settingsPages[] = [
        'id'        => $sjlTab['id'],
        'name'      => $sjlTab['name'],
        'has_error' => $sjlTab['check_error']
            && $settingsErrors->getSectionFromErrorCode($errorCode) === $sjlTab['index'],
        'index'     => $sjlTab['index'],
    ];
}

$pagesByIndex = [];
foreach ($settingsPages as $p) {
    $pagesByIndex[$p['index']] = $p;
}

$sidebarGroups = SettingsTabRegistry::sidebar();

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
                                $viewMap = SettingsTabRegistry::views();
                                $viewLoader = new ViewLoader(plugin_dir_path(__FILE__));
                                $viewData = array(
                                    'jwtSettings'          => $jwtSettings,
                                    'settingsErrors'       => $settingsErrors,
                                    'errorCode'            => $errorCode,
                                    'auditLogRepository'   => $auditLogRepository,
                                    'webhookLogRepository' => $webhookLogRepository,
                                    'apiKeyRepository'     => $apiKeyRepository,
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
                onclick="sjlRemoveAuthLine(jQuery(this));"
                title="<?php echo esc_attr__('Remove', 'simple-jwt-login'); ?>">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
</div>
