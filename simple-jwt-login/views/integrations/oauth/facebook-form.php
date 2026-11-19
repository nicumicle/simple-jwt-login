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
$loginForm = AbstractOauth::getLoginFormTarget($jwtSettings, 'facebook');
?>
<form method="GET" action="<?php echo esc_url($loginForm['action']); ?>"
      class="simple-jwt-login-oauth-app facebook">
    <?php include __DIR__ . '/login-form-fields.php'; ?>
    <button name="facebook-auth" class="simple-jwt-login-auth-btn">
        <img src="<?php echo esc_url($pluginDirUrl . 'assets/images/integrations/facebook-icon.svg'); ?>" alt="Facebook logo"/>
        <span class="simple-jwt-login-auth-txt">
            <?php echo esc_html__('Continue with Facebook', 'simple-jwt-login'); ?>
        </span>
    </button>
</form>
<?php
