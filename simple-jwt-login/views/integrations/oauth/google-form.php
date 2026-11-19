<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\Oauth\AbstractOauth;

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
$loginForm = AbstractOauth::getLoginFormTarget($jwtSettings, 'google');
?>
<form method="GET" action="<?php echo esc_url($loginForm['action']);?>" class="simple-jwt-login-oauth-app google">
    <?php include __DIR__ . '/login-form-fields.php'; ?>
    <button name="google-auth" class="simple-jwt-login-auth-btn">
        <img src="<?php echo esc_url($pluginDirUrl . 'assets/images/integrations/google-icon.svg');?>" alt="google logo"/>
        <span class="simple-jwt-login-auth-txt">
            <?php echo esc_html__('Continue with Google', 'simple-jwt-login');?>
        </span>
    </button>
</form>
<?php
