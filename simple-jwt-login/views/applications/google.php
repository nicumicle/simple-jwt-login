<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
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
 */
?>
<div class="row">
    <div class="col-md-6">
        <h3 class="sub-section-title">
            Google <span class="beta">beta</span>
            <?php
            echo isset($errorCode)
            && (
                $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_APPLICATIONS,
                    SettingsErrors::ERR_GOOGLE_CLIENT_ID_REQUIRED
                ) === $errorCode
                || $settingsErrors->generateCode(
                    SettingsErrors::PREFIX_APPLICATIONS,
                    SettingsErrors::ERR_GOOGLE_CLIENT_SECRET_REQUIRED
                ) === $errorCode
            )
                ? '<span class="simple-jwt-error">!</span>'
                : '';
            ?>
        </h3>
        <p class="text-muted">
            <?php
            echo __(
                'Integrate Google OAuth into your WordPress website.',
                'simple-jwt-login'
            );
            ?>
            <a href="#">Read more</a>
        </p>
    </div>
    <div class="col-md-6 text-right">
        <div class="google logo">
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <h3 class="sub-section-title">
            <?php echo __('Allow Google', 'simple-jwt-login'); ?>
        </h3>
        <div class="form-group">
            <input type="radio" id="social_google_enabled_no" name="google[enabled]" class="form-control"
                   value="0"
                <?php echo $jwtSettings->getApplicationsSettings()->isGoogleEnabled() === false ? 'checked' : ''; ?>
            />
            <label for="social_google_enabled_no">
                <?php echo __('No', 'simple-jwt-login'); ?>
            </label>

            <input type="radio" id="social_google_enabled_yes" name="google[enabled]" class="form-control"
                   value="1"
                <?php
                echo($jwtSettings->getApplicationsSettings()->isGoogleEnabled()
                    ? 'checked'
                    : ''
                );
                ?>
            />
            <label for="social_google_enabled_yes">
                <?php echo __('Yes', 'simple-jwt-login'); ?>
            </label>
        </div>
    </div>
</div>
<hr />
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="google_client_id">Client ID <span class="required">*</span></label>
            <input
                type="text"
                name="google[client_id]"
                id="google_client_id"
                class="form-control"
                value="<?php echo esc_attr($jwtSettings->getApplicationsSettings()->getGoogleClientID());?>"
                placeholder="<?php echo __('Client ID', 'simple-jwt-login'); ?>"
            />
            <br />

            <label for="google_client_secret">Client Secret <span class="required">*</span></label>
            <input
                type="text"
                class="form-control"
                name="google[client_secret]"
                id="google_client_secret"
                value="<?php echo esc_attr($jwtSettings->getApplicationsSettings()->getGoogleClientSecret());?>"
                placeholder="<?php echo __('Client Secret', 'simple-jwt-login'); ?>"
            />
            <br />

            <label for="google_client_redirect_uri">Redirect URI</label>
            <input
                type="text"
                id="google_client_redirect_uri"
                class="form-control"
                name="google[redirect_uri]"
                value="<?php echo empty($jwtSettings->getApplicationsSettings()->getGoogleRedirectURI())
                    ? esc_attr($jwtSettings->generateExampleLink("applications/login/google", []))
                    : esc_attr($jwtSettings->getApplicationsSettings()->getGoogleRedirectURI()); ?>"
                placeholder="<?php echo __('Redirect URI', 'simple-jwt-login'); ?>"
            />
            <br />
            <hr />
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <h3 class="section-title">
                <?php echo __('Exchange OAuth code with Google id_token', 'simple-jwt-login'); ?>
            </h3>
            <p>
                <?php
                echo __(
                    'This route allows you to exchange the code obtained in the Oauth flow, with a Google id_token.',
                    'simple-jwt-login'
                );
                ?>
            </p>
            <p class="text-muted">
                Parameters:<br />
                <b>provider</b> -> <?php echo __('google', 'simple-jwt-login');?><br />
                <b>code</b> -> <?php echo __('the code you received from OAuth flow', 'simple-jwt-login');?><br />
            </p>
            <div class="generated-code">
                <span class="method">POST:</span>
                <span class="code">
                <?php
                $sampleUrlParams = [
                    'provider'    => __('google', 'simple-jwt-login'),
                    'code' => __('your_code ', 'simple-jwt-login')
                ];

                echo esc_html($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, $sampleUrlParams));
                ?>
            </span>
                <span class="copy-button">
                <button class="btn btn-secondary btn-xs">
                    <?php echo __('Copy', 'simple-jwt-login'); ?>
                </button>
            </span>
            </div>
        </div>
    </div>
    <hr/>
    <div class="row">
        <div class="col-md-12">
            <h3 class="section-title"><?php echo __('Exchange Google JWT with a WordPress JWT', 'simple-jwt-login'); ?></h3>
            <p>
                <?php
                echo __(
                    'This route allows you to exchange the Google `id_token` with a Simple-JWT-Login JWT',
                    'simple-jwt-login'
                );
                ?>
            </p>
            <p class="text-muted">
                Parameters:<br />
                <b>provider</b> -> <?php echo __('google', 'simple-jwt-login');?><br />
                <b>jwt</b> -> <?php echo __('the `id_token` from your OAuth process', 'simple-jwt-login');?><br />
            </p>
            <div class="generated-code">
                <span class="method">POST:</span>
                <span class="code">
                <?php
                $sampleUrlParams = [
                    'provider'    => __('google', 'simple-jwt-login'),
                    'id_token' => __('google_id_token ', 'simple-jwt-login')
                ];

                echo esc_html($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, $sampleUrlParams));
                ?>
            </span>
                <span class="copy-button">
                <button class="btn btn-secondary btn-xs">
                    <?php echo __('Copy', 'simple-jwt-login'); ?>
                </button>
            </span>
            </div>
        </div>
    </div>
    <hr/>
    <div class="row">
        <div class="col-md-12">
            <input type="checkbox" name="google[enable_oauth]" id="google_enable_oauth"
                   value="1"
                <?php
                echo $jwtSettings->getApplicationsSettings()->isOauthEnabled()
                    ? 'checked="checked"'
                    : ""
                ?>
            />
            <label for="google_enable_oauth">
                <?php echo __('Enable OAuth on WordPress login', 'simple-jwt-login');?>
            </label><br/>
            <p class="text-muted">
                * <?php
                echo __(
                    'This option will display the login with google button on WordPress login.',
                    'simple-jwt-login'
                );
                $redirectURL = esc_html($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, ['provider' => 'google']));
                echo "<br />";
                echo __(
                    sprintf('For this option to work, you need to set the redirect URI to: %s', $redirectURL),
                    'simple-jwt-login'
                );
                ?>
            </p>

        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <input type="checkbox" name="google[allow_on_all_endpoints]" id="google_all_endpoints"
                   value="1"
                <?php
                echo $jwtSettings->getApplicationsSettings()->isGoogleJwtAllowedOnAllEndpoints()
                    ? 'checked="checked"'
                    : ""
                ?>
            />
            <label for="google_all_endpoints">
                <?php echo __('Allow usage of Google id_token on all endpoints', 'simple-jwt-login');?>
            </label><br/>
            <p class="text-muted">
                * <?php
                echo __(
                    'This option will allow the usage of Google `id_token` on all endpoints.',
                    'simple-jwt-login'
                );
                echo "&nbsp;";
                echo __(
                    'The plugin will search for the user that has the email with the one specified in the JWT payload.',
                    'simple-jwt-login'
                );
                echo "<br />";
                echo __(
                    'In order for this option to work, you also need to enable the `All WordPress endpoints checks for JWT authentication` from General.',
                    'simple-jwt-login'
                );
                ?>
            </p>
        </div>
        <div class="col-md-12">
            <input type="checkbox" name="google[create_user_if_not_exists]" id="google_create_user_if_not_exists"
                   value="1"
                <?php
                echo $jwtSettings->getApplicationsSettings()->isGoogleCreateUserIfNotExistsEnabled()
                    ? 'checked="checked"'
                    : ""
                ?>
            />
            <label for="google_create_user_if_not_exists">
                <?php echo __('TODO: Create user if not exists', 'simple-jwt-login');?>
            </label><br/>
            <p class="text-muted">
                * <?php
                echo __(
                    'This option will allow to create a new user if the email from JWT is not assigned to a WordPress user.',
                    'simple-jwt-login'
                );
                ?>
            </p>
        </div>
    </div>
</div>
