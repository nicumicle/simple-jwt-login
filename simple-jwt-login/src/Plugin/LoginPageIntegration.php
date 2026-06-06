<?php

namespace SimpleJWTLogin\Plugin;

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
        $pluginDirUrl = plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE);

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

        if ($googleEnabled) {
            include_once $pluginDir . '/views/integrations/oauth/google-form.php';
        }

        if ($auth0Enabled) {
            include_once $pluginDir . '/views/integrations/oauth/auth0-form.php';
        }

        if ($facebookEnabled) {
            include_once $pluginDir . '/views/integrations/oauth/facebook-form.php';
        }

        if ($githubEnabled) {
            include_once $pluginDir . '/views/integrations/oauth/github-form.php';
        }

        echo '</div>';
    }
}
