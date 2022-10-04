<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

$jwtSettings   = new SimpleJWTLoginSettings(new WordPressData());
$saved         = false;
$message       = __('Settings successfully saved', 'simple-jwt-login');
$showStatusBar = false;
$errorCode = null;


try {
    $saved         = $jwtSettings->watchForUpdates($_POST);
    $showStatusBar = $saved;
} catch (\Exception $e) {
    $showStatusBar = true;
    $message       = $e->getMessage();
    $errorCode     = $e->getCode();
}
$settingsErrors = new SettingsErrors();
$settingsPages = [
    [
        'id'   => 'simple-jwt-login-tab-dashboard',
        'name' => __('Dashboard', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_DASHBOARD,
        'index' => SettingsErrors::PREFIX_DASHBOARD,
    ],
    [
        'id'   => 'simple-jwt-login-tab-general',
        'name' => __('General', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_GENERAL,
        'index' => SettingsErrors::PREFIX_GENERAL,
    ],
    [
        'id'   => 'simple-jwt-login-tab-login',
        'name' => __('Login', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_LOGIN,
        'index' => SettingsErrors::PREFIX_LOGIN,
    ],
    [
        'id'   => 'simple-jwt-login-tab-register',
        'name' => __('Register User', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_REGISTER,
        'index' => SettingsErrors::PREFIX_REGISTER,
    ],
    [
        'id'   => 'simple-jwt-login-tab-delete',
        'name' => __('Delete User', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_DELETE,
        'index' => SettingsErrors::PREFIX_DELETE,
    ],
    [
        'id'   => 'simple-jwt-login-tab-reset-password',
        'name' => __('Reset Password', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_RESET_PASSWORD,
        'index' => SettingsErrors::PREFIX_RESET_PASSWORD,
    ],
    [
        'id'   => 'auth-tab-login',
        'name' => __('Authentication', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_AUTHENTICATION,
        'index' => SettingsErrors::PREFIX_AUTHENTICATION,
    ],
    [
        'id'   => 'simple-jwt-login-tab-auth-codes',
        'name' => __('Auth Codes', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_AUTH_CODES,
        'index' => SettingsErrors::PREFIX_AUTH_CODES,
    ],
    [
        'id'   => 'simple-jwt-login-tab-hooks',
        'name' => __('Hooks', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_HOOKS,
        'index' => SettingsErrors::PREFIX_HOOKS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-cors',
        'name' => __('CORS', 'simple-jwt-login'),
        'has_error' => $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_CORS,
        'index' => SettingsErrors::PREFIX_CORS,
    ],
    [
        'id'   => 'simple-jwt-login-tab-protect-endpoints',
        'name' => 'Protect endpoints',
        'has_error' =>  (
                $settingsErrors->getSectionFromErrorCode($errorCode) === SettingsErrors::PREFIX_PROTECT_ENDPOINTS
        ),
        'index' => SettingsErrors::PREFIX_PROTECT_ENDPOINTS,
    ],
];

?>
<form method="post">
    <?php
    $activeTab = $settingsPages[0]['index'];
    if (isset($_POST['active_tab'])) {
        foreach ($settingsPages as $item) {
            if ($item['index'] === (int)esc_attr($_POST['active_tab'])) {
                $activeTab = (int)esc_attr($_POST['active_tab']);
            }
        }
    }
    ?>
    <input type="hidden" name="active_tab" id="active_tab" value="<?php echo $activeTab;?>"/>
    <?php
    $jwtSettings
        ->getWordPressData()
        ->insertNonce(WordPressData::NONCE_NAME);
    ?>
    <div id="simple-jwt-login" class="wrapper">
		<?php
        if ($showStatusBar) {
            ?>
            <div class="row">
                <div class="col-md-12 mb-4 mt-3">
                    <div class="<?php echo $saved ? 'updated' : 'error' ?> notice my-acf-notice is-dismissible m-0">
                        <p>
                            <?php
                            if ($saved === false) {
                                ?>
                                <span class="simple-jwt-error">!</span>
                                <?php
                            } ?>
							<?php echo esc_html($message); ?>
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
                    <h1 class="main-title"><?php echo __('Simple JWT Login Settings', 'simple-jwt-login');?></h1>
                </div>
                <div class="col-md-2 text-right">
                    <input type="submit" class="btn btn-dark" value="<?php echo __('Save', 'simple-jwt-login');?>">
                </div>
            </div>
            <hr/>
            <div class="row">
                <div class="col-md-2 mb-3">
                    <ul class="nav nav-pills flex-column nav-tabs" id="simple-jwt-login-tabs" role="tablist">
						<?php
                        foreach ($settingsPages as $page) {
                            $index = $page['index'];
                            $isActive = (empty($errorCode) && ($activeTab === $page['index']))
                                ||  $settingsErrors->getSectionFromErrorCode($errorCode) === $index
                            ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $isActive ? 'active' : ''?>"
                                   id="<?php echo esc_attr($page['id']); ?>-tab"
                                   data-toggle="tab"
                                   data-index="<?php echo esc_html($index);?>"
                                   href="#<?php echo esc_attr($page['id']); ?>"
                                   role="tab"
                                   aria-controls="<?php echo esc_attr($page['id']); ?>"
                                   aria-selected="true"
                                   title="<?php echo esc_attr($page['name']); ?>"
                                >
                                    <?php
                                    if ($page['has_error']) {
                                        ?>
                                        <span class="simple-jwt-error">!</span>
                                        <?php
                                    }

                                    echo esc_html($page['name']); ?>
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
                        foreach ($settingsPages as $page) {
                            $index = $page['index'];
                            $isActive = (empty($errorCode) && ($activeTab === $page['index']))
                                ||  $settingsErrors->getSectionFromErrorCode($errorCode) === $index
                            ?>
                            <div class="tab-pane fade <?php echo $isActive ? 'active' : '' ?> show"
                                 id="<?php echo esc_attr($page['id']); ?>"
                                 role="tabpanel"
                                 aria-labelledby="<?php echo esc_attr($page['id']); ?>-tab"
                            >
								<?php
                                switch ($page['index']) {
                                    case SettingsErrors::PREFIX_DASHBOARD:
                                        include_once plugin_dir_path(__FILE__) . "dashboard-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_GENERAL:
                                        include_once plugin_dir_path(__FILE__) . "general-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_LOGIN:
                                        include_once plugin_dir_path(__FILE__) . "login-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_REGISTER:
                                        include_once plugin_dir_path(__FILE__) . "register-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_DELETE:
                                        include_once plugin_dir_path(__FILE__) . "delete-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_RESET_PASSWORD:
                                        include_once plugin_dir_path(__FILE__) . "reset-password-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_AUTHENTICATION:
                                        include_once plugin_dir_path(__FILE__) . "auth-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_AUTH_CODES:
                                        include_once plugin_dir_path(__FILE__) . "auth-codes-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_HOOKS:
                                        include_once plugin_dir_path(__FILE__) . "hooks-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_CORS:
                                        include_once plugin_dir_path(__FILE__) . "cors-view.php";
                                        break;
                                    case SettingsErrors::PREFIX_PROTECT_ENDPOINTS:
                                        include_once plugin_dir_path(__FILE__) . "protect-endpoints-view.php";
                                        break;
                                    default:
                                        echo __("View file does not exists.", 'simple-jwt-login');
                                }
                                ?>
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
                   placeholder="<?php
                       echo __(
                           'WordPress new user Role ( when new users are created )',
                           'simple-jwt-login'
                       );
                        ?>"
            />
            <input type="text"
                   name="auth_codes[expiration_date][]"
                   class="form-control"
                   placeholder="<?php  echo __(
                       'Expiration date: YYYY-MM-DD HH:MM:SS ( Example: 2020-12-23 23:34:59)',
                       'simple-jwt-login'
                   ) ;?>"
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

