<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Oauth\FacebookOauth;
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
<form method="GET" action="<?php echo esc_url(FacebookOauth::AUTH_URL); ?>"
      class="simple-jwt-login-oauth-app facebook">
    <input type="hidden" name="client_id" value="<?php echo esc_attr($jwtSettings->getIntegrationsSettings()->facebook()->getClientId()); ?>" />
    <input type="hidden" name="response_type" value="code" />
    <input type="hidden" name="scope" value="email" />
    <input type="hidden" name="redirect_uri" value="<?php echo esc_url($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, ['provider' => 'facebook'])); ?>" />
    <button name="facebook-auth" class="simple-jwt-login-auth-btn">
        <img src="<?php echo esc_url($pluginDirUrl . 'images/integrations/facebook-icon.svg'); ?>" alt="Facebook logo"/>
        <span class="simple-jwt-login-auth-txt">
            <?php echo esc_html__('Continue with Facebook', 'simple-jwt-login'); ?>
        </span>
    </button>
</form>
<?php
