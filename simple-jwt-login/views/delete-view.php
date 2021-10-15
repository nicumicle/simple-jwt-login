<?php

use SimpleJWTLogin\Services\RouteService;
use SimpleJWTLogin\Modules\Settings\DeleteUserSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
            <?php echo __('Allow Delete', 'simple-jwt-login'); ?>
        </h3>
        <div class="form-group">
            <input type="radio" id="allow_delete_no" name="allow_delete" class="form-control"
                   value="0"
				<?php echo $jwtSettings->getDeleteUserSettings()->isDeleteAllowed() === false ? 'checked' : ''; ?>
            />
            <label for="allow_delete_no">
				<?php echo __('No', 'simple-jwt-login'); ?>
            </label>

            <input type="radio" id="allow_delete_yes" name="allow_delete" class="form-control"
                   value="1" <?php echo($jwtSettings->getDeleteUserSettings()->isDeleteAllowed() === true ? 'checked' : ''); ?> />
            <label for="allow_delete_yes">
				<?php echo __('Yes', 'simple-jwt-login'); ?>
            </label>
        </div>
    </div>
</div>
<hr />

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('URL Example', 'simple-jwt-login'); ?></h3>
        <div class="generated-code">
            <span class="method">DELETE:</span>
            <span class="code">
                <?php
                $sampleUrlParams = [
                    $jwtSettings->getGeneralSettings()->getRequestKeyUrl() => __('JWT', 'simple-jwt-login'),
                ];
                if ($jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete()) {
                    $sampleUrlParams[ $jwtSettings->getAuthCodesSettings()->getAuthCodeKey() ] = __('AUTH_KEY_VALUE', 'simple-jwt-login');
                }
                echo esc_html($jwtSettings->generateExampleLink(RouteService::USER_ROUTE, $sampleUrlParams));
                ?>
            </span>
            <span class="copy-button">
                <button class="btn btn-secondary btn-xs">
                    <?php echo __('Copy', 'simple-jwt-login'); ?>
                </button>
            </span>
        </div>
        <div class="code-info">
            * <?php
            echo __(
                    'You can also send the JWT in Authorization header. Example:',
                    'simple-jwt-login'
                )
            ?> <b>Authorization: Bearer YOURJWTTOKEN</b>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Delete User Requires Auth Code', 'simple-jwt-login'); ?></h3>
        <div class="form-group">
            <input type="radio" id="require_delete_auth_no" name="require_delete_auth" class="form-control"
                   value="0"
				<?php echo $jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete() === false ? 'checked' : '' ?>
            />
            <label for="require_delete_auth_no">
				<?php echo __('No', 'simple-jwt-login'); ?>
            </label>
            <input type="radio" id="require_delete_auth_yes" name="require_delete_auth" class="form-control"
                   value="1"
				<?php echo $jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete() === true ? 'checked' : '' ?>
            />
            <label for="require_delete_auth_yes">
				<?php echo __('Yes', 'simple-jwt-login'); ?>
            </label>
            <div id="require_delete_auth_alert" class="alert alert-warning" role="alert"
                 style="<?php echo $jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete() === true ? 'display:none;' : ''; ?>">
				<?php echo __(
                " Warning! It's not recommended to allow delete users without Auth Codes",
                'simple-jwt-login'
            ); ?>.
            </div>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">

        <h3 class="section-title">
            <?php
            echo isset($errorCode)
            && $settingsErrors->generateCode(
                SettingsErrors::PREFIX_DELETE,
                SettingsErrors::ERR_DELETE_MISSING_JWT_PARAM
            ) === $errorCode
                ? '<span class="simple-jwt-error">!</span>'
                : ''
            ?>
            <?php echo __('JWT Delete User Config', 'simple-jwt-login'); ?>
        </h3>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <label for="delete_user_by"><?php echo __('Action', 'simple-jwt-login'); ?></label>
        <select name="delete_user_by" class="form-control" id="delete_user_by">
            <option value="0"
				<?php
                echo $jwtSettings->getDeleteUserSettings()->getDeleteUserBy() === DeleteUserSettings::DELETE_USER_BY_EMAIL
                    ? 'selected'
                    : ''
                ?>
            ><?php echo __('Delete User by Email', 'simple-jwt-login'); ?></option>
            <option value="1"
				<?php
                echo $jwtSettings->getDeleteUserSettings()->getDeleteUserBy() === DeleteUserSettings::DELETE_USER_BY_ID
                    ? 'selected'
                    : ''
                ?>
            ><?php echo __('Delete User by WordPress User ID', 'simple-jwt-login'); ?></option>
            <option value="2"
                <?php
                echo $jwtSettings->getDeleteUserSettings()->getDeleteUserBy() === DeleteUserSettings::DELETE_USER_BY_USER_LOGIN
                    ? 'selected'
                    : ''
                ?>
            ><?php echo __('Delete User by WordPress Username', 'simple-jwt-login'); ?></option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="jwt_login_by_paramter"><?php echo __(
                    'JWT parameter key | JWT payload data id (key name where the option is saved)',
                    'simple-jwt-login'
                ); ?></label>

        <input type="text" name="jwt_delete_by_parameter" class="form-control"
               id="jwt_delete_by_parameter"
               value="<?php echo esc_attr($jwtSettings->getDeleteUserSettings()->getJwtDeleteByParameter()); ?>"
               placeholder="<?php echo __('JWT Parameter here. Example: email', 'simple-jwt-login'); ?>"
        />
        <br/>
        <p class="text-muted">
			<?php echo __('You can use `.` (dot) as a separator for sub-array values.', 'simple-jwt-login'); ?>
            <br/>
			<?php echo __('Example: Use `user.id` for getting key `id` from array `user`', 'simple-jwt-login'); ?>
        </p>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __(
            'Allow Delete user only from the following IP addresses',
            'simple-jwt-login'
        ); ?>:</h3>
        <div class="form-group">
            <input type="text" id="delete_ip" name="delete_ip" class="form-control"
                   value="<?php echo esc_attr($jwtSettings->getDeleteUserSettings()->getAllowedDeleteIps()); ?>"
                   placeholder="<?php echo __('Enter IP here', 'simple-jwt-login'); ?>"/>
            <p class="text-muted">
				<?php echo __("If you want to add more IP's, separate them by comma", 'simple-jwt-login'); ?>.
                <br/>
				<?php echo __('Leave blank to allow all IP addresses', 'simple-jwt-login'); ?>.
            </p>
        </div>
    </div>
</div>

