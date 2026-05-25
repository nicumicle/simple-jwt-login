<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

class LoginPageIntegration
{
    public function enqueueLoginAssets()
    {
        $pluginDirUrl = plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        wp_enqueue_style(
            'simple-jwt-login-login_header_css',
            $pluginDirUrl . 'css/login.css'
        );
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function showLoginMessage()
    {
        $wordpressData = new WordPressRepository();
        $jwtSettings   = new SimpleJWTLoginSettings($wordpressData);
        $hasError = false;

        if ($jwtSettings->getIntegrationsSettings()->google()->isEnabled()
            && $jwtSettings->getIntegrationsSettings()->google()->isOauthEnabled()
        ) {
            if (isset($_REQUEST['error'])) {
                $hasError = true;
            }
        }

        if ($jwtSettings->getIntegrationsSettings()->auth0()->isEnabled()
            && $jwtSettings->getIntegrationsSettings()->auth0()->isOauthEnabled()
        ) {
            if (isset($_REQUEST['error'])) {
                $hasError = true;
            }
        }

        if ($hasError) {
            ?>
            <div class="notice notice-error">
                <?php echo esc_html(__("OAuth Error:", 'simple-jwt-login') . ' ' . $_REQUEST['error']);?>
            </div>
            <?php
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function renderLoginFooter()
    {
        $wordpressData = new WordPressRepository();
        $jwtSettings = new SimpleJWTLoginSettings($wordpressData);
        $pluginDirUrl = plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE);

        $googleEnabled = $jwtSettings->getIntegrationsSettings()->google()->isEnabled()
            && $jwtSettings->getIntegrationsSettings()->google()->isOauthEnabled();
        $auth0Enabled  = $jwtSettings->getIntegrationsSettings()->auth0()->isEnabled()
            && $jwtSettings->getIntegrationsSettings()->auth0()->isOauthEnabled();

        if (!$googleEnabled && !$auth0Enabled) {
            return;
        }

        $layout = $jwtSettings->getIntegrationsSettings()->getLoginButtonLayout();
        echo '<div class="sjl-oauth-buttons-wrapper layout-' . esc_attr($layout) . '">';

        $pluginDir = dirname(SIMPLE_JWT_LOGIN_PLUGIN_FILE);

        if ($googleEnabled) {
            include_once $pluginDir . '/views/integrations/oauth/google-form.php';
        }

        if ($auth0Enabled) {
            include_once $pluginDir . '/views/integrations/oauth/auth0-form.php';
        }

        echo '</div>';
    }
}
