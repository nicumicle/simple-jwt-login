<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Oauth\GithubOauth;
use SimpleJWTLogin\Services\RouteService;

if (! defined('ABSPATH')) {
    /**
    @phpstan-ignore-next-line
     */
    exit;
}
// @Generic.Files.LineLength

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 * @var string $pluginDirUrl
 */
?>
<form method="GET" action="<?php echo esc_url(GithubOauth::AUTH_URL); ?>"
      class="simple-jwt-login-oauth-app github">
    <input type="hidden" name="client_id" value="<?php echo esc_attr($jwtSettings->getIntegrationsSettings()->github()->getClientId()); ?>" />
    <input type="hidden" name="scope" value="user:email" />
    <input type="hidden" name="redirect_uri" value="<?php echo esc_url($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, ['provider' => 'github'])); ?>" />
    <button name="github-auth" class="simple-jwt-login-auth-btn">
        <img src="<?php echo esc_url($pluginDirUrl . 'images/integrations/github-icon.svg'); ?>" alt="GitHub logo"/>
        <span class="simple-jwt-login-auth-txt">
            <?php echo esc_html__('Continue with GitHub', 'simple-jwt-login'); ?>
        </span>
    </button>
</form>
<?php
