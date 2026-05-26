<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Services\RouteService;

if (!defined('ABSPATH')) {
    /**
     * @phpstan-ignore-next-line
     */
    exit;
}
// @Generic.Files.LineLength

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */

$facebook = $jwtSettings->getIntegrationsSettings()->facebook();
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="facebook logo"></div>
            <div>
                <h3 class="sjl-gen-card-title">
                    <?php echo esc_html__('Facebook', 'simple-jwt-login'); ?>
                    <?php
                    echo isset($errorCode)
                    && (
                        $settingsErrors->generateCode(
                            SettingsErrors::PREFIX_APPLICATIONS,
                            SettingsErrors::ERR_FACEBOOK_CLIENT_ID_REQUIRED
                        ) === $errorCode
                        || $settingsErrors->generateCode(
                            SettingsErrors::PREFIX_APPLICATIONS,
                            SettingsErrors::ERR_FACEBOOK_CLIENT_SECRET_REQUIRED
                        ) === $errorCode
                    )
                        ? '<span class="simple-jwt-error">!</span>'
                        : '';
                    ?>
                </h3>
                <p class="sjl-gen-card-desc">
                    <?php echo esc_html__('Integrate Facebook OAuth into your WordPress website.', 'simple-jwt-login'); ?>
                    <a href="https://developers.facebook.com/docs/facebook-login" target="_blank">
                        <?php echo esc_html__('Read more', 'simple-jwt-login'); ?>
                    </a>
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="hidden" name="facebook[enabled]" value="0">
            <label class="sjl-toggle-switch" title="<?php echo esc_attr(__('Enable / Disable Facebook', 'simple-jwt-login')); ?>" style="margin: 0;">
                <input type="checkbox" id="facebook_enabled" name="facebook[enabled]" value="1"
                    <?php echo $facebook->isEnabled() ? 'checked' : ''; ?>>
                <span class="sjl-toggle-slider"></span>
            </label>
            <span style="font-size: 12px; color: #555; white-space: nowrap;">
                <?php echo esc_html(__('Enable', 'simple-jwt-login')); ?>
            </span>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-network"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Credentials', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Enter your Facebook application credentials from the Meta for Developers console.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-two-col">
            <div class="sjl-gen-two-col-left">
                <label class="sjl-gen-field-label" for="facebook_client_id">
                    <?php echo esc_html__('App ID', 'simple-jwt-login'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text" name="facebook[client_id]" id="facebook_client_id"
                       class="form-control"
                       value="<?php echo esc_attr($facebook->getClientId()); ?>"
                       placeholder="<?php echo esc_attr(__('App ID', 'simple-jwt-login')); ?>"
                />
            </div>
            <div class="sjl-gen-two-col-right">
                <label class="sjl-gen-field-label" for="facebook_client_secret">
                    <?php echo esc_html__('App Secret', 'simple-jwt-login'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text" name="facebook[client_secret]" id="facebook_client_secret"
                       class="form-control"
                       value="<?php echo esc_attr($facebook->getClientSecret()); ?>"
                       placeholder="<?php echo esc_attr(__('App Secret', 'simple-jwt-login')); ?>"
                />
            </div>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-users"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('OAuth on Login / Register', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Display a "Continue with Facebook" button on the WordPress login and registration page.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="facebook[enable_oauth]" id="facebook_enable_oauth" value="1"
                    <?php echo $facebook->isOauthEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="facebook_enable_oauth" class="sjl-gen-feature-label">
                    <?php echo esc_html(__('Enable OAuth on WordPress login', 'simple-jwt-login')); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo wp_kses(
                        sprintf(
                            __(
                                'Set the following Redirect URI in your <a href="%s" target="_blank">Meta for Developers</a> app for the OAuth flow to work correctly.',
                                'simple-jwt-login'
                            ),
                            esc_url('https://developers.facebook.com/')
                        ),
                        ['a' => ['href' => [], 'target' => []]]
                    ); ?>
                </p>
                <div class="sjl-gen-url-example">
                    <p class="sjl-gen-url-example-label"><?php echo esc_html__('Redirect URI:', 'simple-jwt-login'); ?></p>
                    <div class="generated-code">
                        <span class="code">
                            <?php echo esc_html($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, ['provider' => 'facebook'])); ?>
                        </span>
                        <span class="copy-button">
                            <button class="btn btn-secondary btn-xs"><?php echo esc_html__('Copy', 'simple-jwt-login'); ?></button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-randomize"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php echo esc_html(__('Exchange Facebook OAuth "code" for Facebook tokens', 'simple-jwt-login')); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Exchange the authorization code obtained in the OAuth flow for Facebook tokens.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="facebook[enable_exchange_code]" id="facebook_enable_exchange_code" value="1"
                    <?php echo $facebook->isExchangeCodeEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="facebook_enable_exchange_code" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Enable exchange of Facebook OAuth code for Facebook tokens', 'simple-jwt-login'); ?>
                </label>
            </div>
        </div>

        <div class="sjl-gen-feature-item">
            <label class="sjl-gen-field-label" for="facebook_redirect_uri_exchange_code">
                <?php echo esc_html__('Redirect URI', 'simple-jwt-login'); ?>
            </label>
            <input type="text" id="facebook_redirect_uri_exchange_code"
                   class="form-control sjl-gen-input-medium"
                   name="facebook[redirect_uri_exchange_code]"
                   value="<?php echo esc_attr($facebook->getExchangeCodeRedirectUri()); ?>"
                   placeholder="<?php echo esc_attr__('Redirect URI', 'simple-jwt-login'); ?>"
            />
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="sjl-gen-code-block" style="margin-bottom: 10px;">
                <div class="sjl-gen-params-table">
                    <div class="sjl-gen-param-def">
                        <code class="sjl-gen-var-chip">provider</code>
                        <span class="sjl-gen-card-desc"><?php echo esc_html__('facebook', 'simple-jwt-login'); ?></span>
                    </div>
                    <div class="sjl-gen-param-def">
                        <code class="sjl-gen-var-chip">code</code>
                        <span class="sjl-gen-card-desc"><?php echo esc_html__('the code received from the OAuth flow', 'simple-jwt-login'); ?></span>
                    </div>
                </div>
            </div>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, [
                        'provider' => 'facebook',
                        'code'     => __('your_code', 'simple-jwt-login'),
                    ]));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo esc_html__('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-update"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php echo esc_html(__('Exchange Facebook "access_token" for a WordPress JWT', 'simple-jwt-login')); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html(__('Exchange a Facebook access_token for a Simple-JWT-Login WordPress JWT.', 'simple-jwt-login')); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="facebook[enable_exchange_token]" id="facebook_enable_exchange_token" value="1"
                    <?php echo $facebook->isExchangeTokenEnabled() ? esc_html('checked="checked"') : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="facebook_enable_exchange_token" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Enable exchange of Facebook access_token for a WordPress JWT', 'simple-jwt-login'); ?>
                </label>
            </div>
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="sjl-gen-code-block" style="margin-bottom: 10px;">
                <div class="sjl-gen-params-table">
                    <div class="sjl-gen-param-def">
                        <code class="sjl-gen-var-chip">provider</code>
                        <span class="sjl-gen-card-desc"><?php echo esc_html('facebook'); ?></span>
                    </div>
                    <div class="sjl-gen-param-def">
                        <code class="sjl-gen-var-chip">access_token</code>
                        <span class="sjl-gen-card-desc"><?php echo esc_html__('the access_token from your Facebook OAuth process', 'simple-jwt-login'); ?></span>
                    </div>
                </div>
            </div>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, [
                        'provider'     => 'facebook',
                        'access_token' => __('your_access_token', 'simple-jwt-login'),
                    ]));
                    ?>
                </span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo esc_html__('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
        </div>

    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Other Options', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Additional settings for the Facebook integration.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="facebook[allow_on_all_endpoints]" id="facebook_all_endpoints" value="1"
                    <?php echo $facebook->isAllowedOnAllEndpoints() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="facebook_all_endpoints" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Allow usage of Facebook access_token on all endpoints', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('The plugin will search for a WordPress user matching the email returned by Facebook\'s Graph API. You must also enable "All WordPress endpoints check for JWT authentication" in General settings.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="facebook[create_user_if_not_exists]" id="facebook_create_user_if_not_exists" value="1"
                    <?php echo $facebook->isCreateUserIfNotExistsEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="facebook_create_user_if_not_exists" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Create user if not exists', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('Automatically create a new WordPress user if no account is found matching the email returned by Facebook.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>

    </div>
</div>
