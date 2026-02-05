<?php
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
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
                     <span
                        class="dashicons dashicons-info-outline sjl-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo esc_attr__(
                            'Allows users to authenticate automatically using a valid JWT token.',
                            'simple-jwt-login'
                        ); ?>"
                    ></span>
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
                     <span
                        class="dashicons dashicons-info-outline sjl-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo esc_attr__(
                            'Enables user registration through the JWT register endpoint.',
                            'simple-jwt-login'
                        ); ?>"
                    ></span>
                </h5>
                <div class="box-status box-status-<?php
                echo $jwtSettings->getRegisterSettings()->isRegisterAllowed()
                    ? 'on'
                    : 'off'
                ?>">
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
                    <span
                        class="dashicons dashicons-info-outline sjl-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo esc_attr__(
                            'Allows users to delete their account using a JWT-authenticated request.',
                            'simple-jwt-login'
                        ); ?>"
                    ></span>
                </h5>
                <div class="box-status box-status-<?php
                    echo $jwtSettings->getDeleteUserSettings()->isDeleteAllowed()
                        ? 'on'
                        : 'off'
                ?>">
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
                    <span
                        class="dashicons dashicons-info-outline sjl-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo esc_attr__(
                            'Enables JWT authentication for protected REST API endpoints.',
                            'simple-jwt-login'
                        ); ?>"
                    ></span>
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
                    <span
                        class="dashicons dashicons-info-outline sjl-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo esc_attr__(
                            'Controls whether cross-origin requests are allowed for JWT endpoints.',
                            'simple-jwt-login'
                        ); ?>"
                    ></span>
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
                    <span
                        class="dashicons dashicons-info-outline sjl-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo esc_attr__(
                            'Allows users to reset their password using a secure JWT endpoint.',
                            'simple-jwt-login'
                        ); ?>"
                    ></span>
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
                    <span
                        class="dashicons dashicons-info-outline sjl-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo esc_attr__(
                            'Shows how many Simple-JWT-Login hooks are currently enabled.',
                            'simple-jwt-login'
                        ); ?>"
                    ></span>
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
                    <span
                        class="dashicons dashicons-info-outline sjl-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo esc_attr__(
                            'Displays the total number of active authentication codes.',
                            'simple-jwt-login'
                        ); ?>"
                    ></span>
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
<div class="row">
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __('Protect endpoints', 'simple-jwt-login'); ?>
                    <span
                        class="dashicons dashicons-info-outline sjl-tooltip"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="<?php echo esc_attr__(
                            'Restricts selected REST API endpoints to authenticated JWT requests only.',
                            'simple-jwt-login'
                        ); ?>"
                    ></span>
                </h5>
                <div
                        class="box-status box-status-<?php
                        echo $jwtSettings->getProtectEndpointsSettings()->isEnabled()
                            ? "on"
                            : 'off'
                        ?>"
                >
                </div>
                <div class="text-center">
					<?php
                    echo $jwtSettings->getProtectEndpointsSettings()->isEnabled()
                        ? __('On', 'simple-jwt-login')
                        : __('Off', 'simple-jwt-login');
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
