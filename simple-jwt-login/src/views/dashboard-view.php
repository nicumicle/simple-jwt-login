<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title">
			<?php echo __( 'Routes Status', 'simple-jwt-login' ); ?>
        </h3>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __( 'Login status', 'simple-jwt-login' ); ?>
                </h5>
                <div class="box-status box-status-<?php echo $jwtSettings->getAllowAutologin() ? "on" : 'off' ?>">
                </div>
                <div class="text-center">
					<?php
					echo $jwtSettings->getAllowAutologin()
						? __( 'On', 'simple-jwt-login' )
						: __( 'Off', 'simple-jwt-login' );
					?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __( 'Register Status', 'simple-jwt-login' ); ?>
                </h5>
                <div class="box-status box-status-<?php echo $jwtSettings->getAllowRegister() ? "on" : 'off' ?>">
                </div>
                <div class="text-center">
					<?php
					echo $jwtSettings->getAllowRegister()
						? __( 'On', 'simple-jwt-login' )
						: __( 'Off', 'simple-jwt-login' );
					?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __( 'Delete Status', 'simple-jwt-login' ); ?>
                </h5>
                <div class="box-status box-status-<?php echo $jwtSettings->getAllowDelete() ? "on" : 'off' ?>">
                </div>
                <div class="text-center">
					<?php
					echo $jwtSettings->getAllowDelete()
						? __( 'On', 'simple-jwt-login' )
						: __( 'Off', 'simple-jwt-login' );
					?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
					<?php echo __( 'Authentication Status', 'simple-jwt-login' ); ?>
                </h5>
                <div class="box-status box-status-<?php echo $jwtSettings->getAllowAuthentication() ? "on" : 'off' ?>">
                </div>
                <div class="text-center">
					<?php
					echo $jwtSettings->getAllowAuthentication()
						? __( 'On', 'simple-jwt-login' )
						: __( 'Off', 'simple-jwt-login' );
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
					<?php echo __( 'CORS Status', 'simple-jwt-login' ); ?>
                </h5>
                <div class="box-status box-status-<?php echo $jwtSettings->getCors()->isCorsEnabled() ? "on" : 'off' ?>">
                </div>
                <div class="text-center">
					<?php
					echo $jwtSettings->getCors()->isCorsEnabled()
						? __( 'On', 'simple-jwt-login' )
						: __( 'Off', 'simple-jwt-login' );
					?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-xs-12">
        <div class="card card-shadow">
            <div class="card-body text-center">
                <h5 class="card-title">
                    <?php echo __( 'Number of active hooks', 'simple-jwt-login' ); ?>
                </h5>
                <div class="box-status  box-status-<?php echo count($jwtSettings->getEnabledHooks())> 0 ? "on" : 'off' ?>">
                </div>
                <div class="text-center">
                    <?php
                    echo count($jwtSettings->getEnabledHooks());
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
