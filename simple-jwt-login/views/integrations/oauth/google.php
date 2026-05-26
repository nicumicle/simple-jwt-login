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

$google = $jwtSettings->getIntegrationsSettings()->google();
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="google logo"></div>
            <div>
                <h3 class="sjl-gen-card-title">
                    <?php echo esc_html__('Google', 'simple-jwt-login'); ?>
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
                <p class="sjl-gen-card-desc">
                    <?php echo esc_html__('Integrate Google OAuth into your WordPress website.', 'simple-jwt-login'); ?>
                    <a href="https://simplejwtlogin.com/docs/applications/google/setup" target="_blank">
                        <?php echo esc_html__('Read more', 'simple-jwt-login'); ?>
                    </a>
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="hidden" name="google[enabled]" value="0">
            <label class="sjl-toggle-switch" title="<?php echo esc_attr(__('Enable / Disable Google', 'simple-jwt-login')); ?>" style="margin: 0;">
                <input type="checkbox" id="google_enabled" name="google[enabled]" value="1"
                    <?php echo $google->isEnabled() ? 'checked' : ''; ?>>
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
                <?php echo esc_html__('Enter your Google OAuth application credentials from the Google Cloud Console.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-two-col">
            <div class="sjl-gen-two-col-left">
                <label class="sjl-gen-field-label" for="google_client_id">
                    <?php echo esc_html__('Client ID', 'simple-jwt-login'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text" name="google[client_id]" id="google_client_id"
                       class="form-control"
                       value="<?php echo esc_attr($google->getClientId()); ?>"
                       placeholder="<?php echo esc_attr(__('Client ID', 'simple-jwt-login')); ?>"
                />
            </div>
            <div class="sjl-gen-two-col-right">
                <label class="sjl-gen-field-label" for="google_client_secret">
                    <?php echo esc_html__('Client Secret', 'simple-jwt-login'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text" name="google[client_secret]" id="google_client_secret"
                       class="form-control"
                       value="<?php echo esc_attr($google->getClientSecret()); ?>"
                       placeholder="<?php echo esc_attr(__('Client Secret', 'simple-jwt-login')); ?>"
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
                <?php echo esc_html__('Display a "Continue with Google" button on the WordPress login and registration page.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="google[enable_oauth]" id="google_enable_oauth" value="1"
                    <?php echo $google->isOauthEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="google_enable_oauth" class="sjl-gen-feature-label">
                    <?php echo esc_html(__('Enable OAuth on WordPress login', 'simple-jwt-login')); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo wp_kses(
                        sprintf(
                            __('Set the following Redirect URI in your <a href="%s" target="_blank">Google Cloud Console</a> for the OAuth flow to work correctly.', 'simple-jwt-login'),
                            esc_url('https://console.cloud.google.com/')
                        ),
                        ['a' => ['href' => [], 'target' => []]]
                    ); ?>
                </p>
                <div class="sjl-gen-url-example">
                    <p class="sjl-gen-url-example-label"><?php echo esc_html__('Redirect URI:', 'simple-jwt-login'); ?></p>
                    <div class="generated-code">
                        <span class="code">
                            <?php echo esc_html($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, ['provider' => 'google'])); ?>
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
                <?php echo esc_html(__('Exchange Google OAuth "code" for Google "id_token"', 'simple-jwt-login')); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('Exchange the code obtained in the OAuth flow for a Google id_token.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="google[enable_exchange_code]" id="google_enable_exchange_code" value="1"
                    <?php echo $google->isExchangeCodeEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="google_enable_exchange_code" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Enable exchange of Google OAuth code for Google id_token', 'simple-jwt-login'); ?>
                </label>
            </div>
        </div>

        <div class="sjl-gen-feature-item">
            <label class="sjl-gen-field-label" for="google_redirect_uri_exchange_code">
                <?php echo esc_html__('Redirect URI', 'simple-jwt-login'); ?>
            </label>
            <input type="text" id="google_redirect_uri_exchange_code"
                   class="form-control sjl-gen-input-medium"
                   name="google[redirect_uri_exchange_code]"
                   value="<?php echo esc_attr($google->getExchangeCodeRedirectUri()); ?>"
                   placeholder="<?php echo esc_attr__('Redirect URI', 'simple-jwt-login'); ?>"
            />
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="sjl-gen-code-block" style="margin-bottom: 10px;">
                <div class="sjl-gen-params-table">
                    <div class="sjl-gen-param-def">
                        <code class="sjl-gen-var-chip">provider</code>
                        <span class="sjl-gen-card-desc"><?php echo esc_html__('google', 'simple-jwt-login'); ?></span>
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
                        'provider' => 'google',
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
                <?php echo esc_html(__('Exchange Google "id_token" for a WordPress JWT', 'simple-jwt-login')); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html(__('Exchange a Google id_token for a Simple-JWT-Login JWT.', 'simple-jwt-login')); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="google[enable_exchange_id_token]" id="google_enable_exchange_id_token" value="1"
                    <?php echo $google->isExchangeIdTokenEnabled() ? esc_html('checked="checked"') : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="google_enable_exchange_id_token" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Enable exchange of Google id_token for a WordPress JWT', 'simple-jwt-login'); ?>
                </label>
            </div>
        </div>

        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('Endpoint example:', 'simple-jwt-login'); ?></p>
            <div class="sjl-gen-code-block" style="margin-bottom: 10px;">
                <div class="sjl-gen-params-table">
                    <div class="sjl-gen-param-def">
                        <code class="sjl-gen-var-chip">provider</code>
                        <span class="sjl-gen-card-desc"><?php echo esc_html('google'); ?></span>
                    </div>
                    <div class="sjl-gen-param-def">
                        <code class="sjl-gen-var-chip">id_token</code>
                        <span class="sjl-gen-card-desc"><?php echo esc_html__('the id_token from your OAuth process', 'simple-jwt-login'); ?></span>
                    </div>
                </div>
            </div>
            <div class="generated-code">
                <span class="method">POST</span>
                <span class="code">
                    <?php
                    echo esc_html($jwtSettings->generateExampleLink(RouteService::OAUTH_TOKEN, [
                        'provider' => esc_html('google'),
                        'id_token' => __('google_id_token', 'simple-jwt-login'),
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
                <?php echo esc_html__('Additional settings for the Google integration.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="google[allow_on_all_endpoints]" id="google_all_endpoints" value="1"
                    <?php echo $google->isAllowedOnAllEndpoints() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="google_all_endpoints" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Allow usage of Google id_token on all endpoints', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('The plugin will search for a WordPress user matching the email in the Google JWT payload. You must also enable "All WordPress endpoints check for JWT authentication" in General settings.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="google[create_user_if_not_exists]" id="google_create_user_if_not_exists" value="1"
                    <?php echo $google->isCreateUserIfNotExistsEnabled() ? 'checked="checked"' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="google_create_user_if_not_exists" class="sjl-gen-feature-label">
                    <?php echo esc_html__('Create user if not exists', 'simple-jwt-login'); ?>
                </label>
                <p class="sjl-gen-feature-desc">
                    <?php echo esc_html__('Automatically create a new WordPress user if no account is found matching the email in the Google JWT payload.', 'simple-jwt-login'); ?>
                </p>
            </div>
        </div>

    </div>
</div>
