<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Helpers\ViewLoader;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Services\AuditLoggerService;

class AdminUI
{
    /**
     * @var array
     */
    private $server;

    /**
     * @var array
     */
    private $post;

    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

    /**
     * @var AuditLogRepository
     */
    private $auditLogRepository;

    /**
     * @var WebhookLogRepository
     */
    private $webhookLogRepository;

    /**
     * @var ApiKeyRepository
     */
    private $apiKeyRepository;

    /**
     * @param array                  $server      $_SERVER
     * @param array                  $post        $_POST
     * @param SimpleJWTLoginSettings $jwtSettings
     * @param AuditLogRepository     $auditLogRepository
     * @param WebhookLogRepository   $webhookLogRepository
     * @param ApiKeyRepository       $apiKeyRepository
     */
    public function __construct(
        array $server,
        array $post,
        SimpleJWTLoginSettings $jwtSettings,
        AuditLogRepository $auditLogRepository,
        WebhookLogRepository $webhookLogRepository,
        ApiKeyRepository $apiKeyRepository
    ) {
        $this->server               = $server;
        $this->post                 = $post;
        $this->jwtSettings          = $jwtSettings;
        $this->auditLogRepository   = $auditLogRepository;
        $this->webhookLogRepository = $webhookLogRepository;
        $this->apiKeyRepository     = $apiKeyRepository;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function registerMenuEntry()
    {
        $icon = plugins_url('/assets/images/simple-jwt-login-16x16.png', SIMPLE_JWT_LOGIN_PLUGIN_FILE);

        add_menu_page(
            'Simple-JWT-Login Plugin',
            'Simple JWT Login',
            'manage_options',
            'main-page-simple-jwt-login-plugin',
            array($this, 'showMainPage'),
            $icon
        );
    }

    public function showMainPage()
    {
        $pluginData    = get_plugin_data(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        $pluginVersion = isset($pluginData['Version'])
            ? $pluginData['Version']
            : false;
        $pluginDirUrl = plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        $loadScriptsInFooter = false;
        wp_enqueue_style(
            'simple-jwt-login-bootstrap',
            $pluginDirUrl . 'assets/vendor/bootstrap/bootstrap.min.css',
            array(),
            $pluginVersion
        );
        wp_enqueue_style(
            'simple-jwt-login-style',
            $pluginDirUrl . 'assets/css/style.css',
            array(),
            $pluginVersion
        );
        wp_enqueue_style(
            'simple-jwt-login-dark',
            $pluginDirUrl . 'assets/css/dark.css',
            array('simple-jwt-login-style'),
            $pluginVersion
        );
        wp_enqueue_script(
            'simple-jwt-bootstrap-min',
            $pluginDirUrl . 'assets/vendor/bootstrap/bootstrap.min.js',
            array('jquery'),
            $pluginVersion,
            $loadScriptsInFooter
        );

        wp_enqueue_script(
            'simple-jwt-login-scripts',
            $pluginDirUrl . 'assets/js/scripts.js',
            array('simple-jwt-bootstrap-min'),
            $pluginVersion,
            $loadScriptsInFooter
        );

        $viewLoader = new ViewLoader(dirname(SIMPLE_JWT_LOGIN_PLUGIN_FILE) . '/views/');
        $viewLoader->render('layout.php', $this->buildViewData());
    }

    /**
     * Handle the settings form submission and assemble every object the
     * layout (and the sub-views it loads) needs. Views must not instantiate
     * services or repositories themselves - everything is prepared here.
     *
     * @return array
     */
    protected function buildViewData()
    {
        $jwtSettings   = $this->jwtSettings;
        $saved         = false;
        $message       = __('Settings successfully saved', 'simple-jwt-login');
        $showStatusBar = false;
        $errorCode     = null;

        try {
            if (!empty($this->post)) {
                check_admin_referer(WordPressRepository::NONCE_NAME);
            }
            $saved         = $jwtSettings->watchForUpdates($this->post);
            $showStatusBar = $saved;
            if ($saved) {
                $this->logSettingsSaved();
            }
        } catch (\Exception $exception) {
            $showStatusBar = true;
            $message       = $exception->getMessage();
            $errorCode     = $exception->getCode();
        }

        return array(
            'jwtSettings'          => $jwtSettings,
            'saved'                => $saved,
            'message'              => $message,
            'showStatusBar'        => $showStatusBar,
            'errorCode'            => $errorCode,
            'settingsErrors'       => new SettingsErrors(),
            'auditLogRepository'   => $this->auditLogRepository,
            'webhookLogRepository' => $this->webhookLogRepository,
            'apiKeyRepository'     => $this->apiKeyRepository,
        );
    }

    /**
     * Record a successful settings save in the audit log.
     */
    protected function logSettingsSaved()
    {
        $jwtSettings  = $this->jwtSettings;
        $serverHelper = $jwtSettings->getGeneralSettings()->isTrustIpHeadersEnabled()
            ? ServerHelper::withTrustedProxyHeaders($this->server)
            : new ServerHelper($this->server);
        $auditLogger = new AuditLoggerService(
            $this->auditLogRepository,
            $jwtSettings->getAuditLogSettings(),
            $serverHelper,
            $jwtSettings->getWordPressData()
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

    public function addPluginActionLinks($links)
    {
        $links['donate'] = sprintf(
            '<a href="%1$s" target="_blank" style="color: rgb(166, 146, 25); font-weight: bold;">%2$s</a>',
            'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PK9BCD6AYF58Y&source=url',
            'Buy me a coffee'
        );

        $links['documentation'] = sprintf(
            '<a href="%1$s" target="_blank" style="color: #42b983; font-weight: bold;">%2$s</a>',
            'https://simplejwtlogin.com?utm_source=plugin_page',
            'Documentation'
        );
        $links['github'] = sprintf(
            '<a href="%1$s" target="_blank" style="color: #24292f; font-weight: bold;">%2$s</a>',
            'https://github.com/nicumicle/simple-jwt-login',
            'GitHub'
        );

        return $links;
    }
}
