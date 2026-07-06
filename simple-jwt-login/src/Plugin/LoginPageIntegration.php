<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Helpers\ViewLoader;
use SimpleJWTLogin\Modules\Settings\Oauth\OauthProviderRegistry;
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

        $anyEnabled = !empty($this->oauthLoginEnabledSlugs());

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

    public function renderLoginFooter()
    {
        $enabledSlugs = $this->oauthLoginEnabledSlugs();
        if (empty($enabledSlugs)) {
            return;
        }

        $layout = $this->jwtSettings->getIntegrationsSettings()->getLoginButtonLayout();
        echo '<div class="sjl-oauth-buttons-wrapper layout-' . esc_attr($layout) . '">';

        $pluginDir = dirname(SIMPLE_JWT_LOGIN_PLUGIN_FILE);
        $viewLoader = new ViewLoader($pluginDir . '/views/integrations/oauth/');
        $viewData = array(
            'jwtSettings'   => $this->jwtSettings,
            'pluginDirUrl'  => plugin_dir_url(SIMPLE_JWT_LOGIN_PLUGIN_FILE),
        );

        foreach ($enabledSlugs as $slug) {
            $viewLoader->render($slug . '-form.php', $viewData);
        }

        echo '</div>';
    }

    /**
     * Slugs of OAuth providers that are both enabled and have the OAuth login flow turned on.
     *
     * @return string[]
     */
    private function oauthLoginEnabledSlugs()
    {
        $integrationsSettings = $this->jwtSettings->getIntegrationsSettings();
        $slugs = array();
        foreach (array_keys(OauthProviderRegistry::all()) as $slug) {
            $provider = $integrationsSettings->getProvider($slug);
            if ($provider->isEnabled() && $provider->isOauthEnabled()) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }
}
