<?php
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
			<?php echo __('Routes Status', 'simple-jwt-login'); ?>
        </h3>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __('Login status', 'simple-jwt-login'); ?>
                </h5>
                <div class="box-status box-status-<?php echo $jwtSettings->getLoginSettings()->isAutologinEnabled()
                    ? "on"
                    : 'off'
                ?>">
                </div>
                <div class="text-center">
					<?php
                    echo $jwtSettings->getLoginSettings()->isAutologinEnabled()
                        ? __('On', 'simple-jwt-login')
                        : __('Off', 'simple-jwt-login');
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __('Register Status', 'simple-jwt-login'); ?>
                </h5>
                <div class="box-status box-status-<?php echo $jwtSettings->getRegisterSettings()->isRegisterAllowed() ? "on" : 'off' ?>">
                </div>
                <div class="text-center">
					<?php
                    echo $jwtSettings->getRegisterSettings()->isRegisterAllowed()
                        ? __('On', 'simple-jwt-login')
                        : __('Off', 'simple-jwt-login');
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __('Delete Status', 'simple-jwt-login'); ?>
                </h5>
                <div class="box-status box-status-<?php echo $jwtSettings->getDeleteUserSettings()->isDeleteAllowed() ? "on" : 'off' ?>">
                </div>
                <div class="text-center">
					<?php
                    echo $jwtSettings->getDeleteUserSettings()->isDeleteAllowed()
                        ? __('On', 'simple-jwt-login')
                        : __('Off', 'simple-jwt-login');
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __('Authentication Status', 'simple-jwt-login'); ?>
                </h5>
                <div class="box-status box-status-<?php
                    echo $jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled()
                    ? "on"
                    : 'off' ?>">
                </div>
                <div class="text-center">
					<?php
                    echo $jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled()
                        ? __('On', 'simple-jwt-login')
                        : __('Off', 'simple-jwt-login');
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __('CORS Status', 'simple-jwt-login'); ?>
                </h5>
                <div
                        class="box-status box-status-<?php
                        echo $jwtSettings->getCorsSettings()->isCorsEnabled()
                            ? "on"
                            : 'off'
                        ?>"
                >
                </div>
                <div class="text-center">
					<?php
                    echo $jwtSettings->getCorsSettings()->isCorsEnabled()
                        ? __('On', 'simple-jwt-login')
                        : __('Off', 'simple-jwt-login');
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
                    <?php echo __('Reset Password Status', 'simple-jwt-login'); ?>
                </h5>
                <div
                        class="box-status box-status-<?php
                        echo $jwtSettings->getResetPasswordSettings()->isResetPasswordEnabled()
                            ? "on"
                            : 'off'
                        ?>"
                >
                </div>
                <div class="text-center">
                    <?php
                    echo $jwtSettings->getResetPasswordSettings()->isResetPasswordEnabled()
                        ? __('On', 'simple-jwt-login')
                        : __('Off', 'simple-jwt-login');
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
                    <?php echo __('Number of active hooks', 'simple-jwt-login'); ?>
                </h5>
                <div class="box-status  box-status-<?php
                echo count($jwtSettings->getHooksSettings()->getEnabledHooks()) > 0
                    ? "on"
                    : 'off'
                ?>">
                </div>
                <div class="text-center">
                    <?php
                    echo count($jwtSettings->getHooksSettings()->getEnabledHooks());
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
                    <?php echo __('Number of Auth Codes', 'simple-jwt-login'); ?>
                </h5>
                <div class="box-status  box-status-<?php
                echo count($jwtSettings->getAuthCodesSettings()->getAuthCodes()) > 0
                    ? "on"
                    : 'off'
                ?>">
                </div>
                <div class="text-center">
                    <?php
                    echo count($jwtSettings->getAuthCodesSettings()->getAuthCodes());
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
