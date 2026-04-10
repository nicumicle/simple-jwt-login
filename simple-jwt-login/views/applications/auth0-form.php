<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Applications\Auth0;
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
<form method="POST" action="<?php
$auth0Domain = $jwtSettings->getApplicationsSettings()->auth0()->getDomain();
echo esc_url($auth0Domain ? sprintf('https://%s/authorize', $auth0Domain) : '#');
?>"
      class="simple-jwt-login-oauth-app auth0">
    <input type="hidden" name="client_id" value="<?php echo esc_attr($jwtSettings->getApplicationsSettings()->auth0()->getClientId());?>" />
    <input type="hidden" name="response_type" value="code" />
    <input type="hidden" name="scope" value="openid email profile" />
    <input type="hidden" name="redirect_uri" value="<?php echo esc_url($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, ['provider' => 'auth0']));?>" />
    <button name="auth0-auth" class="simple-jwt-login-auth-btn">
        <img src="<?php echo esc_url($pluginDirUrl . 'images/applications/auth0-60x60.png');?>" alt="Auth0 logo"/>
        <span class="simple-jwt-login-auth-txt">
            <?php echo esc_html__('Continue with Auth0', 'simple-jwt-login');?>
        </span>
    </button>
</form>
<?php
