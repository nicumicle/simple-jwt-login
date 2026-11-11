<?php

use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    /**
     * @phpstan-ignore-next-line
     */
    exit;
}

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 * @var bool $sjlPluginInstalled
 * @var bool $sjlPluginActivated Whether the WordPress Abilities API (WP 6.9+) is available.
 */

// Rendered inside a closure so the view defines no global variables.
call_user_func(function () use ($jwtSettings, $sjlPluginActivated) {
    $aiSettings   = $jwtSettings->getIntegrationsSettings()->aiAbilities();
    $mcpInstalled = file_exists(WP_PLUGIN_DIR . '/mcp-adapter/mcp-adapter.php');
    $mcpStatus    = sprintf(
    /* translators: 1: opening anchor tag, 2: closing anchor tag */
        __('MCP Adapter: not installed. Download it from %1$sGitHub%2$s.', 'simple-jwt-login'),
        '<a href="https://github.com/WordPress/mcp-adapter/releases" target="_blank">',
        '</a>'
    );
    if ($mcpInstalled) {
		$mcpStatus = __('MCP Adapter: installed but not activated.', 'simple-jwt-login');
    }
    if (class_exists('\WP\MCP\Plugin')) {
		$mcpStatus = __('MCP Adapter: active.', 'simple-jwt-login');
    }
    $mcpCurrentUser    = wp_get_current_user();
    $mcpUserLogin      = !empty($mcpCurrentUser->user_login) ? $mcpCurrentUser->user_login : 'admin';
    $mcpWpPath         = rtrim(ABSPATH, '/\\');
    $mcpEndpoint       = rest_url('mcp/mcp-adapter-default-server');
    $mcpAppPasswordUrl = admin_url('profile.php#application-passwords-section');
    $mcpIsSsl          = is_ssl();
    $mcpAppPasswords   = function_exists('wp_is_application_passwords_available')
        && wp_is_application_passwords_available();
    $aiAccessOptions = [
    AiAbilitiesSettings::ACCESS_READ       => __('Read only', 'simple-jwt-login'),
    AiAbilitiesSettings::ACCESS_WRITE      => __('Write only', 'simple-jwt-login'),
    AiAbilitiesSettings::ACCESS_READ_WRITE => __('Read & write', 'simple-jwt-login'),
    ];
    $aiPermissionHelp = [
    AiAbilitiesSettings::PERMISSION_VIEW_SECRETS    => __(
        'Show JWT signing keys, OAuth client secrets, the refresh token key, auth code values and webhook header values in clear text. Anything the agent reads may be sent to the AI provider. The audit log always stays masked. Secrets can never be changed by AI agents.',
        'simple-jwt-login'
    ),
    AiAbilitiesSettings::PERMISSION_CHANGE_ROLES    => __(
        'Change the role given to users who register, the user meta they may set and the role of auth codes. Needs Write access. Administrator and other roles with administrator capabilities can never be set by AI agents.',
        'simple-jwt-login'
    ),
    AiAbilitiesSettings::PERMISSION_CHANGE_AUTH_PROVIDERS => __(
        'Change OAuth providers (Google, Auth0, Facebook, GitHub), JWT rules (trusted issuers), how a JWT is matched to a user (login by, login parameter, allowed issuers) and the Two-Factor integration. Needs Write access. Pointing a provider or issuer at an attacker-controlled service lets the attacker log in as your users; turning off two-factor removes the second login check.',
        'simple-jwt-login'
    ),
    AiAbilitiesSettings::PERMISSION_CREATE_API_KEYS => __(
        'Create API keys. Needs Write access. The agent receives the new key, a working credential for your site. Revoking keys does not need this option.',
        'simple-jwt-login'
    ),
    AiAbilitiesSettings::PERMISSION_ADD_WEBHOOKS    => __(
        'Add webhooks. Needs Write access. A webhook sends plugin events (logins, registrations, ...) to any HTTPS URL. Removing webhooks does not need this option.',
        'simple-jwt-login'
    ),
    ];
	?>

	<?php if (!$sjlPluginActivated) : ?>
<div class="notice notice-warning inline sjl-plugin-not-installed-notice">
    <p>
        <?php echo esc_html(sprintf(
            /* translators: %s: the current WordPress version */
            __('AI Abilities require WordPress 6.9 or newer (Abilities API). You are running WordPress %s, so this integration has no effect until you update WordPress.', 'simple-jwt-login'),
            get_bloginfo('version')
        )); ?>
    </p>
</div>
	<?php endif; ?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header" style="justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="ai-abilities logo"></div>
            <div>
                <h3 class="sjl-gen-card-title">
                    <?php echo esc_html__('AI Abilities (MCP)', 'simple-jwt-login'); ?>
                </h3>
                <p class="sjl-gen-card-desc">
                    <?php echo wp_kses(
                        sprintf(
                            /* translators: 1: opening anchor tag, 2: closing anchor tag */
                            __('Let AI agents manage and troubleshoot this plugin through the %1$sWordPress Abilities API%2$s.', 'simple-jwt-login'),
                            '<a href="https://developer.wordpress.org/apis/abilities-api/" target="_blank">',
                            '</a>'
                        ),
                        ['a' => ['href' => [], 'target' => []]]
                    ); ?>
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="hidden" name="ai_abilities[enabled]" value="0">
            <label class="sjl-toggle-switch"
                   title="<?php echo esc_attr(__('Enable / Disable AI Abilities', 'simple-jwt-login')); ?>"
                   style="margin: 0;">
                <input type="checkbox" id="ai_abilities_enabled" name="ai_abilities[enabled]" value="1"
                    <?php echo $aiSettings->isEnabled() ? 'checked' : ''; ?>>
                <span class="sjl-toggle-slider"></span>
            </label>
            <span class="sjl-toggle-enable-label">
                <?php echo esc_html(__('Enable', 'simple-jwt-login')); ?>
            </span>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-settings"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Settings', 'simple-jwt-login'); ?></h3>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <table class="form-table sjl-form-table">
            <tr>
                <th scope="row">
                    <label for="ai_abilities_access">
                        <?php echo esc_html__('AI access', 'simple-jwt-login'); ?>
                    </label>
                </th>
                <td>
                    <select name="ai_abilities[access]" id="ai_abilities_access" class="form-control">
                        <?php foreach ($aiAccessOptions as $aiAccessValue => $aiAccessLabel) : ?>
                            <option value="<?php echo esc_attr($aiAccessValue); ?>"
                                <?php echo $aiSettings->getSelectedAccess() === $aiAccessValue ? 'selected' : ''; ?>>
                                <?php echo esc_html($aiAccessLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php echo esc_html__(
                            'Read: view settings, API keys, webhooks and logs, analyze the audit log for attacks, explain error codes, inspect JWTs and diagnose the setup. Write: change settings, revoke API keys and remove webhooks. Riskier actions need the AI permissions below.',
                            'simple-jwt-login'
                        ); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-lock"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('AI permissions', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'Sensitive actions are off by default. Turn on only what your AI agents need: text from emails, posts or logs that an agent reads can trick it into misusing these permissions. Turning a permission off takes effect immediately; after turning one on, restart running MCP sessions to see newly allowed abilities.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <?php foreach ($aiPermissionHelp as $aiPermission => $aiPermissionDesc) : ?>
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 12px 0;">
                <div>
                    <strong><?php echo esc_html(AiAbilitiesSettings::getPermissionLabel($aiPermission)); ?></strong>
                    <p class="description" style="margin: 4px 0 0;"><?php echo esc_html($aiPermissionDesc); ?></p>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">
                    <input type="hidden" name="ai_abilities[<?php echo esc_attr($aiPermission); ?>]" value="0">
                    <label class="sjl-toggle-switch"
                           title="<?php echo esc_attr(AiAbilitiesSettings::getPermissionLabel($aiPermission)); ?>"
                           style="margin: 0;">
                        <input type="checkbox" id="ai_abilities_<?php echo esc_attr($aiPermission); ?>"
                               name="ai_abilities[<?php echo esc_attr($aiPermission); ?>]" value="1"
                            <?php echo $aiSettings->isPermissionGranted($aiPermission) ? 'checked' : ''; ?>>
                        <span class="sjl-toggle-slider"></span>
                    </label>
                    <span class="sjl-toggle-enable-label">
                        <?php echo esc_html(__('Allow', 'simple-jwt-login')); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-rest-api"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Connect an AI client', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'MCP clients (Claude, Cursor, ChatGPT, ...) need the MCP Adapter plugin. Without it, the abilities are still available through the WordPress REST API (/wp-json/wp-abilities/v1/) and to other plugins.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <p class="sjl-gen-card-desc">
            <?php echo wp_kses($mcpStatus, ['a' => ['href' => [], 'target' => []]]); ?>
        </p>

        <h4 style="margin: 16px 0 4px;">
            <?php echo esc_html__('Option 1: WP-CLI (local or over SSH) - recommended', 'simple-jwt-login'); ?>
        </h4>
        <p class="sjl-gen-card-desc">
            <?php echo esc_html__(
                'The AI client starts WP-CLI on the server and talks to it directly. No web endpoint is used: only someone who can already run commands on the server can connect. Requires WP-CLI on the server. Over SSH, use key-based login (the client cannot answer a password prompt).',
                'simple-jwt-login'
            ); ?>
        </p>
        <?php
        $mcpClientTransport = 'stdio';
        include plugin_dir_path(__FILE__) . 'ai-abilities-clients.php';
        ?>

        <h4 style="margin: 24px 0 4px;">
            <?php echo esc_html__('Option 2: HTTP (no SSH access, e.g. shared hosting)', 'simple-jwt-login'); ?>
        </h4>
        <p class="sjl-gen-card-desc">
            <?php echo wp_kses(
                sprintf(
                    /* translators: 1: opening anchor tag, 2: closing anchor tag */
                    __('The client connects to the MCP endpoint below and logs in with an Application Password. Create one in %1$sUsers > Profile > Application Passwords%2$s for the administrator the AI will act as, then use it in the command. Anyone with this password has full administrator access to the REST API: store it safely and revoke it when you no longer need it.', 'simple-jwt-login'),
                    '<a href="' . esc_url($mcpAppPasswordUrl) . '" target="_blank">',
                    '</a>'
                ),
                ['a' => ['href' => [], 'target' => []]]
            ); ?>
        </p>
        <?php if (!$mcpAppPasswords) : ?>
            <div class="notice notice-error inline">
                <p>
                    <?php echo esc_html__(
                        'Application Passwords are not available on this site, so Option 2 returns "401 Sorry, you are not allowed to do that". WordPress only enables them over HTTPS, or on a local development site (define WP_ENVIRONMENT_TYPE as "local" in wp-config.php). A plugin or the "wp_is_application_passwords_available" filter can also turn them off.',
                        'simple-jwt-login'
                    ); ?>
                </p>
            </div>
        <?php endif; ?>
        <?php if (!$mcpIsSsl) : ?>
            <div class="notice notice-warning inline">
                <p>
                    <?php echo esc_html__(
                        'This site is not served over HTTPS. Do not use Option 2 without HTTPS: the Application Password would be sent in clear text.',
                        'simple-jwt-login'
                    ); ?>
                </p>
            </div>
        <?php endif; ?>
        <div class="sjl-gen-url-example">
            <p class="sjl-gen-url-example-label"><?php echo esc_html__('MCP endpoint:', 'simple-jwt-login'); ?></p>
            <div class="generated-code">
                <span class="code"><?php echo esc_html($mcpEndpoint); ?></span>
                <span class="copy-button">
                    <button class="btn btn-secondary btn-xs"><?php echo esc_html__('Copy', 'simple-jwt-login'); ?></button>
                </span>
            </div>
        </div>
        <?php
        $mcpClientTransport = 'http';
        include plugin_dir_path(__FILE__) . 'ai-abilities-clients.php';
        ?>
        <p class="description">
            <?php echo esc_html__(
                'Other MCP clients: use the endpoint above with the header "Authorization: Basic base64(username:application-password)". These settings hold a working administrator credential: keep config files out of version control. If you get 401 errors with correct credentials, your host may strip the Authorization header before it reaches WordPress.',
                'simple-jwt-login'
            ); ?>
        </p>

        <h4 style="margin: 24px 0 4px;">
            <?php echo esc_html__('Before connecting a live site', 'simple-jwt-login'); ?>
        </h4>
        <ul class="sjl-gen-card-desc" style="padding-left: 20px; list-style: disc;">
            <li><?php echo esc_html__('Start with "Read only" AI access and keep the AI permissions off. Switch to write access only while you need changes.', 'simple-jwt-login'); ?></li>
            <li><?php echo esc_html__('Use a dedicated administrator account (and Application Password) for AI agents, so you can revoke it on its own and spot its actions in the audit log.', 'simple-jwt-login'); ?></li>
            <li><?php echo esc_html__('Review and confirm write actions in your AI client instead of auto-approving them.', 'simple-jwt-login'); ?></li>
            <li><?php echo esc_html__('Restart the MCP session after changing the AI access or permissions, so the client sees the updated list of abilities.', 'simple-jwt-login'); ?></li>
        </ul>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Security', 'simple-jwt-login'); ?></h3>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <ul class="sjl-gen-card-desc" style="padding-left: 20px; list-style: disc;">
            <li><?php echo esc_html__('Only administrators (manage_options) can run abilities, except "explain-error", which any logged-in user can run without seeing setting values. The access level and the user\'s capability are checked on every call.', 'simple-jwt-login'); ?></li>
            <li><?php echo esc_html__('Secrets (JWT keys, OAuth client secrets, the refresh token key, auth code values, webhook header values) can never be changed by an AI agent, and are masked unless you allow viewing secrets. New auth codes and JWT rules can only be added here.', 'simple-jwt-login'); ?></li>
            <li><?php echo esc_html__('Only you can change these AI Abilities settings and permissions; AI agents cannot.', 'simple-jwt-login'); ?></li>
            <li><?php echo esc_html__('Changes made by AI agents are written to the audit log when the "AI Ability Executed" event is enabled.', 'simple-jwt-login'); ?></li>
        </ul>
    </div>
</div>
	<?php
});
