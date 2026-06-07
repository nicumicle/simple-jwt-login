<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Helpers\ViewLoader;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class LoginPageIntegration
{
    /**
     * @var array
     */
    protected $request;

    /**
     * @var SimpleJWTLoginSettings
     */
    private $jwtSettings;

    /**
     * @param array $request
     */
    public function __construct($request, SimpleJWTLoginSettings $jwtSettings)
    {
        $this->request = $request;
        $this->jwtSettings = $jwtSettings;
    }

    public function enqueueLoginAssets()
    {
        $pluginDirUrl = plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        wp_enqueue_style(
            'simple-jwt-login-login_header_css',
            $pluginDirUrl . 'assets/css/login.css',
            array(),
            SIMPLE_JWT_LOGIN_VERSION
        );
    }

    public function showLoginMessage()
    {
        $hasError = false;

        $integrationsSettings = $this->jwtSettings->getIntegrationsSettings();
        $anyEnabled = ($integrationsSettings->google()->isEnabled() && $integrationsSettings->google()->isOauthEnabled())
            || ($integrationsSettings->auth0()->isEnabled() && $integrationsSettings->auth0()->isOauthEnabled())
            || ($integrationsSettings->facebook()->isEnabled() && $integrationsSettings->facebook()->isOauthEnabled())
            || ($integrationsSettings->github()->isEnabled() && $integrationsSettings->github()->isOauthEnabled());

        if ($anyEnabled && isset($this->request['error'])) {
            $hasError = true;
        }

        if ($hasError) {
            ?>
            <div class="notice notice-error">
                <?php echo esc_html(__("OAuth Error:", 'simple-jwt-login') . ' ' . $this->request['error']);?>
            </div>
            <?php
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function renderLoginFooter()
    {
        $integrationsSettings = $this->jwtSettings->getIntegrationsSettings();
        $googleEnabled   = $integrationsSettings->google()->isEnabled()
            && $integrationsSettings->google()->isOauthEnabled();
        $auth0Enabled    = $integrationsSettings->auth0()->isEnabled()
            && $integrationsSettings->auth0()->isOauthEnabled();
        $facebookEnabled = $integrationsSettings->facebook()->isEnabled()
            && $integrationsSettings->facebook()->isOauthEnabled();
        $githubEnabled   = $integrationsSettings->github()->isEnabled()
            && $integrationsSettings->github()->isOauthEnabled();

        if (!$googleEnabled && !$auth0Enabled && !$facebookEnabled && !$githubEnabled) {
            return;
        }

        $layout = $integrationsSettings->getLoginButtonLayout();
        echo '<div class="sjl-oauth-buttons-wrapper layout-' . esc_attr($layout) . '">';

        $pluginDir = dirname(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        $viewLoader = new ViewLoader($pluginDir . '/views/integrations/oauth/');
        $viewData = array(
            'jwtSettings'   => $this->jwtSettings,
            'pluginDirUrl'  => plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE),
        );

        if ($googleEnabled) {
            $viewLoader->render('google-form.php', $viewData);
        }

        if ($auth0Enabled) {
            $viewLoader->render('auth0-form.php', $viewData);
        }

        if ($facebookEnabled) {
            $viewLoader->render('facebook-form.php', $viewData);
        }

        if ($githubEnabled) {
            $viewLoader->render('github-form.php', $viewData);
        }

        echo '</div>';
    }
}
