<?php

use SimpleJWTLogin\Helpers\ApiKeyPermissions;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Services\RouteService;

$permissionLabels = [
    ApiKeyPermissions::READ   => ['GET',    '/wp/v2/*', 'Read WordPress resources',   'dashicons-visibility'],
    ApiKeyPermissions::CREATE => ['POST',   '/wp/v2/*', 'Create WordPress resources',  'dashicons-plus-alt'],
    ApiKeyPermissions::UPDATE => ['PUT',    '/wp/v2/*', 'Update WordPress resources',  'dashicons-edit'],
    ApiKeyPermissions::DELETE => ['DELETE', '/wp/v2/*', 'Delete WordPress resources',  'dashicons-trash'],
];

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
}

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */

$namespace  = rtrim($jwtSettings->getGeneralSettings()->getRouteNamespace(), '/');
$restBase   = rest_url($namespace);
$restNonce  = wp_create_nonce('wp_rest');

global $wpdb;
$apiKeyRepo  = new ApiKeyRepository($wpdb);
$akIsAdmin   = current_user_can('manage_options');
//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$akPage      = isset($_GET['ak_page']) ? max(1, (int) $_GET['ak_page']) : 1;
$akPerPage   = 20;

if ($akIsAdmin) {
    $akResult = $apiKeyRepo->findAll($akPage, $akPerPage);
} else {
    $akResult = $apiKeyRepo->findByUserId(get_current_user_id(), $akPage, $akPerPage);
}

$akItems  = $akResult['items'];
$akTotal  = $akResult['total'];
$akPages  = $akTotal > 0 ? (int) ceil($akTotal / $akPerPage) : 1;
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-admin-network"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('API Keys', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'Allow external clients to authenticate using scoped API keys instead of JWTs.'
                    . ' Send the key via the configured header below.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="api_keys[enabled]" value="0"
                    <?php echo $jwtSettings->getApiKeysSettings()->isEnabled() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="api_keys[enabled]" value="1"
                    <?php echo $jwtSettings->getApiKeysSettings()->isEnabled() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo esc_html__('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>

        <div class="form-group row mt-3">
            <label class="col-sm-2 col-form-label">
                <?php echo esc_html__('Header name', 'simple-jwt-login'); ?>
            </label>
            <div class="col-sm-4">
                <input type="text" class="form-control"
                    name="api_keys[header_name]"
                    value="<?php echo esc_attr($jwtSettings->getApiKeysSettings()->getHeaderName()); ?>"
                    placeholder="X-API-Key"
                />
                <small class="form-text text-muted">
                    <?php echo esc_html__('HTTP header clients must send the API key in. Default: X-API-Key', 'simple-jwt-login'); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<div class="sjl-gen-card" id="sjl-api-keys-section">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-plus-alt"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Create API Key', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__('The raw key is shown only once at creation - store it securely.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="form-group row mb-3">
            <label class="col-sm-2 col-form-label"><?php echo esc_html__('Name', 'simple-jwt-login'); ?></label>
            <div class="col-sm-6">
                <input type="text" id="sjl-ak-name" class="form-control"
                    placeholder="<?php echo esc_attr__('e.g. Mobile App', 'simple-jwt-login'); ?>" />
            </div>
        </div>

        <div class="form-group row mb-3">
            <label class="col-sm-2 col-form-label"><?php echo esc_html__('Expires at', 'simple-jwt-login'); ?></label>
            <div class="col-sm-4">
                <input type="datetime-local" id="sjl-ak-expires" class="form-control" />
                <small class="form-text text-muted"><?php echo esc_html__('Leave blank for no expiration.', 'simple-jwt-login'); ?></small>
            </div>
        </div>

        <div class="form-group row mb-3">
            <label class="col-sm-2 col-form-label"><?php echo esc_html__('Permissions', 'simple-jwt-login'); ?></label>
            <div class="col-sm-10">
                <div>
                    <?php foreach (ApiKeyPermissions::$all as $perm) : ?>
                    <div>
                        <label>
                            <input type="checkbox" class="sjl-ak-perm-check" value="<?php echo esc_attr($perm); ?>" />
                            <?php echo esc_html(ucfirst($perm)); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-outline-secondary" onclick="sjlCreateApiKey()">
            <?php echo esc_html__('Create API Key', 'simple-jwt-login'); ?>
        </button>
        <span id="sjl-ak-create-msg" class="sjl-ak-msg"></span>
    </div>
</div>

<div class="sjl-gen-card" id="sjl-api-keys-list-section">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-list-view"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Existing API Keys', 'simple-jwt-login'); ?></h3>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <?php if (empty($akItems)) : ?>
            <p><?php echo esc_html__('No API keys found.', 'simple-jwt-login'); ?></p>
        <?php else : ?>
            <table class="widefat sjl-ak-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Name', 'simple-jwt-login'); ?></th>
                        <?php if ($akIsAdmin) : ?>
                        <th><?php echo esc_html__('User ID', 'simple-jwt-login'); ?></th>
                        <?php endif; ?>
                        <th><?php echo esc_html__('Prefix', 'simple-jwt-login'); ?></th>
                        <th><?php echo esc_html__('Permissions', 'simple-jwt-login'); ?></th>
                        <th><?php echo esc_html__('Expires', 'simple-jwt-login'); ?></th>
                        <th><?php echo esc_html__('Last Used', 'simple-jwt-login'); ?></th>
                        <th><?php echo esc_html__('Action', 'simple-jwt-login'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($akItems as $ak) :
                        $perms = is_array($ak->permissions)
                            ? $ak->permissions
                            : (array) json_decode((string) $ak->permissions, true);
                        ?>
                    <tr>
                        <td><?php echo esc_html($ak->name); ?></td>
                        <?php if ($akIsAdmin) : ?>
                        <td><?php echo (int) $ak->user_id; ?></td>
                        <?php endif; ?>
                        <td><code><?php echo esc_html($ak->key_prefix); ?></code></td>
                        <td class="sjl-ak-perm-badges">
                            <?php foreach ($perms as $p) :
                                $method = isset($permissionLabels[$p]) ? $permissionLabels[$p][0] : strtoupper($p);
                                ?>
                                <span class="sjl-ak-method sjl-method-<?php echo esc_attr(strtolower($method)); ?>"><?php echo esc_html($p); ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?php echo esc_html($ak->expires_at ?: '-'); ?></td>
                        <td><?php echo esc_html($ak->last_used_at ?: '-'); ?></td>
                        <td>
                            <div class="sjl-ak-actions">
                                <?php if ($ak->revoked_at) : ?>
                                    <span class="sjl-ak-revoked"><?php echo esc_html__('Revoked', 'simple-jwt-login'); ?></span>
                                <?php else : ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick="sjlRevokeApiKey(<?php echo (int) $ak->id; ?>)">
                                        <span class="dashicons dashicons-no-alt" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:3px;"></span>
                                        <?php echo esc_html__('Revoke', 'simple-jwt-login'); ?>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="sjlDeleteApiKey(<?php echo (int) $ak->id; ?>)">
                                    <span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:3px;"></span>
                                    <?php echo esc_html__('Delete', 'simple-jwt-login'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($akPages > 1) : ?>
                <div class="sjl-ak-pagination">
                    <?php for ($i = 1; $i <= $akPages; $i++) :
                        $pageUrl = add_query_arg('ak_page', (string) $i);
                        ?>
                        <?php if ($i === $akPage) : ?>
                            <span class="sjl-ak-page-current"><?php echo (int) $i; ?></span>
                        <?php else : ?>
                            <a href="<?php echo esc_url($pageUrl); ?>"><?php echo (int) $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div id="sjl-ak-modal" class="sjl-ak-modal" style="display:none" role="dialog" aria-modal="true">
    <div class="sjl-ak-modal-content">
        <div class="sjl-ak-modal-header">
            <span class="dashicons dashicons-admin-network"></span>
            <div>
                <h3 class="sjl-ak-modal-title"><?php echo esc_html__('API Key Created', 'simple-jwt-login'); ?></h3>
                <p class="sjl-ak-modal-desc"><?php echo esc_html__('Copy the key below - it will not be shown again.', 'simple-jwt-login'); ?></p>
            </div>
        </div>
        <div class="sjl-ak-modal-body">
            <div class="sjl-gen-warning-banner">
                <span class="dashicons dashicons-warning"></span>
                <span><?php echo esc_html__('Store this key securely. It cannot be recovered after closing this dialog.', 'simple-jwt-login'); ?></span>
            </div>
            <div class="sjl-ak-modal-key-wrap">
                <code id="sjl-ak-raw-key"></code>
                <button type="button" class="sjl-ak-copy-btn" onclick="sjlCopyApiKey()">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php echo esc_html__('Copy', 'simple-jwt-login'); ?>
                </button>
            </div>
            <p id="sjl-ak-copy-msg" class="sjl-ak-copy-msg"></p>
            <div class="sjl-ak-modal-footer">
                <button type="button" class="sjl-gen-btn-generate" onclick="sjlCloseApiKeyModal()">
                    <?php echo esc_html__('Done', 'simple-jwt-login'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    window._sjlAkConfig = {
        restBase:       <?php echo wp_json_encode(trailingslashit($restBase) . RouteService::API_KEYS_ROUTE); ?>,
        restBaseSingle: <?php echo wp_json_encode(trailingslashit($restBase) . 'api-keys'); ?>,
        nonce:          <?php echo wp_json_encode($restNonce); ?>
    };
}());
</script>
