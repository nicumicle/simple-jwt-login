<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;
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
<form method="POST" action="<?php echo esc_url(GoogleOauth::AUTH_URL);?>" class="simple-jwt-login-oauth-app google">
    <input type="hidden" name="client_id" value="<?php echo esc_attr($jwtSettings->getIntegrationsSettings()->google()->getClientId());?>" />
    <input type="hidden" name="response_type" value="code" />
    <input type="hidden" name="scope" value="email" />
    <input type="hidden" name="redirect_uri" value="<?php echo esc_url($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, ['provider' => 'google']));?>" />
    <button name="google-auth" class="simple-jwt-login-auth-btn">
        <img src="<?php echo esc_url($pluginDirUrl . 'images/integrations/google-icon.svg');?>" alt="google logo"/>
        <span class="simple-jwt-login-auth-txt">
            <?php echo esc_html__('Continue with Google', 'simple-jwt-login');?>
        </span>
    </button>
</form>
<?php
