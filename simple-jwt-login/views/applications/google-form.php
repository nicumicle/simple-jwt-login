<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Applications\Google;
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
<form method="POST" action="<?php echo Google::AUTH_URL;?>" class="simple-jwt-login-oauth-app google">
    <input type="hidden" name="client_id" value="<?php echo esc_attr($jwtSettings->getApplicationsSettings()->getGoogleClientID());?>" />
    <input type="hidden" name="response_type" value="code" />
    <input type="hidden" name="scope" value="email" />
    <input type="hidden" name="redirect_uri" value="<?php echo $jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, ['provider' => 'google']);?>" />
    <button name="google-auth" class="simple-jwt-login-auth-btn">
        <img src="<?php echo $pluginDirUrl;?>/images/applications/google-60x60.png" alt="google logo"/>
        <span class="simple-jwt-login-auth-txt">
            <?php echo __('Continue with Google', 'simple-jwt-login');?>
        </span>
    </button>
</form>
<?php
