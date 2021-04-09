<?php

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\SettingsErrors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$jwtSettings   = new SimpleJWTLoginSettings( new \SimpleJWTLogin\Modules\WordPressData() );
$saved         = false;
$message       = __( 'Settings successfully saved', 'simple-jwt-login' );
$showStatusBar = false;
$errorCode = null;
try {
    $saved         = $jwtSettings->watchForUpdates( $_POST );
    $showStatusBar = $saved;
} catch ( \Exception $e ) {
    $showStatusBar = true;
    $message       = $e->getMessage();
    $errorCode     = $e->getCode();
}

$settingsPages = [
	[
		'id'   => 'simple-jwt-login-tab-dashboard',
		'view' => 'dashboard-view.php',
		'name' => 'Dashboard',
        'has_error' => SettingsErrors::getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_DASHBOARD,
	],
	[
		'id'   => 'simple-jwt-login-tab-general',
		'view' => 'general-view.php',
		'name' => 'General',
        'has_error' => SettingsErrors::getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_GENERAL,
	],
	[
		'id'   => 'simple-jwt-login-tab-login',
		'view' => 'login-view.php',
		'name' => 'Login',
        'has_error' =>  SettingsErrors::getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_LOGIN,
	],
	[
		'id'   => 'simple-jwt-login-tab-register',
		'view' => 'register-view.php',
		'name' => 'Register User',
        'has_error' =>  SettingsErrors::getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_REGISTER,
	],
	[
		'id'   => 'simple-jwt-login-tab-delete',
		'view' => 'delete-view.php',
		'name' => 'Delete User',
        'has_error' =>  SettingsErrors::getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_DELETE,
	],
	[
		'id'   => 'auth-tab-login',
		'view' => 'auth-view.php',
		'name' => 'Authentication',
        'has_error' =>  SettingsErrors::getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_AUTHENTICATION,
	],
	[
		'id'   => 'simple-jwt-login-tab-auth-codes',
		'view' => 'auth-codes-view.php',
		'name' => 'Auth Codes',
        'has_error' =>  SettingsErrors::getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_AUTH_CODES,
	],
	[
		'id'   => 'simple-jwt-login-tab-hooks',
		'view' => 'hooks-view.php',
		'name' => 'Hooks',
        'has_error' =>  SettingsErrors::getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_HOOKS,
	],
	[
		'id'   => 'simple-jwt-login-cors-tab',
		'view' => 'cors-view.php',
		'name' => 'CORS',
        'has_error' =>  SettingsErrors::getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_CORS
	],
];
?>
<form method="post">
    <div id="simple-jwt-login" class="wrapper">
		<?php
		if ( $showStatusBar ) {
			?>
            <div class="row">
                <div class="col-md-12 mb-4 mt-3">
                    <div class="<?php echo $saved ? 'updated' : 'error' ?> notice my-acf-notice is-dismissible m-0">
                        <p>
                            <?php
                            if($saved === false){
                                ?>
                                <span class="simple-jwt-error">!</span>
                                <?php
                            }
                            ?>
							<?php echo $message; ?>
                        </p>
                    </div>
                </div>
            </div>
			<?php
		}
		?>
        <div class="">
            <div class="row main-title-container">
                <div class="col-md-10">
                    <h1 class="main-title">Simple JWT Login Settings</h1>
                </div>
                <div class="col-md-2 text-right">
                    <input type="submit" class="btn btn-dark" value="Save">
                </div>
            </div>
            <hr/>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <ul class="nav nav-pills flex-column" id="myTab" role="tablist">
						<?php
						foreach ( $settingsPages as $index => $page ) {
						    $isActive = empty($errorCode) && $index === 0
                                || SettingsErrors::getSectionFromErrorCode($errorCode) === $index + 1
							?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $isActive ? 'active' : ''?>"
                                   id="<?php echo $page['id']; ?>-tab"
                                   data-toggle="tab"
                                   href="#<?php echo $page['id']; ?>"
                                   role="tab"
                                   aria-controls="<?php echo $page['id']; ?>"
                                   aria-selected="true"
                                   title="<?php echo $page['name'] ?>"
                                >
                                    <?php
                                    if($page['has_error']){
                                        ?>
                                        <span class="simple-jwt-error">!</span>
                                        <?php
                                    }

                                    echo $page['name'];
                                    ?>
                                </a>
                            </li>
							<?php
						}
						?>
                    </ul>
                </div>

                <div class="col-md-10">
                    <div class="tab-content card-shadow" id="simple-jwt-login-tab-content">
						<?php
						foreach ( $settingsPages as $index => $page ) {
                            $isActive = empty($errorCode) && $index === 0
                                || SettingsErrors::getSectionFromErrorCode($errorCode) === $index + 1
						    ?>
                            <div class="tab-pane fade <?php echo $isActive ? 'active' : '' ?> show"
                                 id="<?php echo $page['id']; ?>"
                                 role="tabpanel"
                                 aria-labelledby="<?php echo $page['id']; ?>-tab"
                            >
								<?php include_once $page['view']; ?>
                            </div>
							<?php
						}
						?>
                    </div>
                </div>
                <!-- /.col-md-8 -->
            </div>


        </div>
        <!-- /.container -->
    </div>
</form>


<div id="code_line" style="display:none">
    <div class="form-group auth_row">
        <div class="input-group">
            <input type="text"
                   name="auth_codes[code][]"
                   class="form-control"
                   placeholder="<?php  echo __('Authentication Key', 'simple-jwt-login') ;?>"
            />
            <input type="text"
                   name="auth_codes[role][]"
                   class="form-control"
                   placeholder="<?php  echo __('WordPress new user Role ( when new users are created )', 'simple-jwt-login') ;?>"
            />
            <input type="text"
                   name="auth_codes[expiration_date][]"
                   class="form-control"
                   placeholder="<?php  echo __('Expiration date: YYYY-MM-DD HH:MM:SS ( Example: 2020-12-23 23:34:59)', 'simple-jwt-login') ;?>"
            />
            <div class="input-group-addon auth-code-delete-container">
                <a href="javascript:void(0)"
                   onclick="jwt_login_remove_auth_line(jQuery(this));"
                   title="<?php  echo __('delete', 'simple-jwt-login') ;?>"
                >
                    <i class="delete-auth-code" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</div>

