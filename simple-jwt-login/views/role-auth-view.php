<?php

use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\RouteService;
use WP_Role;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly
/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */


/** @phpstan-ignore-next-line */
$wpRoles = get_roles();
/** @phpstan-ignore-next-line */
$result = count_users();
?>

<div class="form-group">
    <div class="input-group" style="margin-top:10px">
        <input
                type="checkbox"
                name="role_authentication_enabled"
                id="role_authentication_enabled"
                value="1"
                style="margin-top:1px;"
            <?php echo $jwtSettings->getAuthenticationSettings()->isRoleAuthenticationEnabled()
                ? esc_html('checked="checked"')
                : '';
            ?>

        />
        <label for="role_authentication_enabled">
            <b><?php echo __('Role authentication', 'simple-jwt-login'); ?></b>
        </label>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
            <?php
            echo isset($errorCode)
            && $settingsErrors->generateCode(
                SettingsErrors::PREFIX_AUTHENTICATION,
                SettingsErrors::ERR_AUTHENTICATION_EMPTY_PAYLOAD
            ) === $errorCode
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
            <?php echo __('Roles Allowed', 'simple-jwt-login'); ?>
        </h3>
        <div id="authentication_role_data" class="authentication_jwt_container">
        <div class="columns is-multiline is-mobile">
                <div>
                    
						<?php
                        // $payloadParameters = $jwtSettings->getAuthenticationSettings()->getJwtPayloadParameters();
                        foreach ($wpRoles->roles as $roleIndex => $role) {
                            $numberOfLines = count($wpRoles->roles) - 1;
                            $lineSeparator = $numberOfLines === $roleIndex
                                ? ''
                                : ',';
                            $roleName = strtolower($role['name']);
                            
                            $roleCount = $result['avail_roles'][$roleName] ? $result['avail_roles'][$roleName] : 0;
                            ?>
                            <div class="column">
                            <div class="card">
                                <div class="card-header">
                                    <span class="checkbox">
                                        
                                            <input
                                                    type="checkbox"
                                                    id="role_auth_<?php echo esc_attr($roleName); ?>"
                                                    name="role_auth[]"
                                                    value="<?php echo esc_attr($roleName); ?>"
                                                    <?php
                                                    echo esc_html(
                                                        $jwtSettings
                                                            ->getAuthenticationSettings()
                                                            ->isRoleEnabled($roleName)
                                                            ? 'checked'
                                                            : ''
                                                    )
                                                    ?>
                                            />
                                        
                                    </span>
                                    <label class="bold" for="role_auth_<?php echo esc_attr($roleName);?>">
                                        <?php
										echo esc_attr($roleName) . ' ';
										// echo $role_count . ' users.';
										?>
                                    </label>
                                </div>
                            </div>
                                    </div>
							<?php
                        }
                        ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>
<hr/>
