<?php

use SimpleJWTLogin\Helpers\ApiKeyPermissions;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJWTLogin\Services\RouteService;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
}

/**
 * @var SimpleJWTLoginSettings $jwtSettings
 * @var ApiKeyRepository $apiKeyRepo
 */

$namespace   = rtrim($jwtSettings->getGeneralSettings()->getRouteNamespace(), '/');
$restBase    = rest_url($namespace);
$restNonce   = wp_create_nonce('wp_rest');
$currentUser = get_current_user_id();

//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$akPage    = isset($_GET['ak_page']) ? max(1, (int) $_GET['ak_page']) : 1;
$akPerPage = 20;
$akResult  = $apiKeyRepo->findByUserId($currentUser, $akPage, $akPerPage);
$akItems   = $akResult['items'];
$akTotal   = $akResult['total'];
$akPages   = $akTotal > 0 ? (int) ceil($akTotal / $akPerPage) : 1;

$permissionLabels = [
    ApiKeyPermissions::READ   => 'dashicons-visibility',
    ApiKeyPermissions::CREATE => 'dashicons-plus-alt',
    ApiKeyPermissions::UPDATE => 'dashicons-edit',
    ApiKeyPermissions::DELETE => 'dashicons-trash',
];
?>

<div class="wrap">
<div id="simple-jwt-login">

<div class="sjl-gen-card">
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

        <button type="button" class="btn btn-outline-secondary" onclick="sjlCreateApiKey()">
            <?php echo esc_html__('Create API Key', 'simple-jwt-login'); ?>
        </button>
        <span id="sjl-ak-create-msg" class="sjl-ak-msg"></span>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-list-view"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('My API Keys', 'simple-jwt-login'); ?></h3>
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
                        <td><code><?php echo esc_html($ak->key_prefix); ?></code></td>
                        <td class="sjl-ak-perm-badges">
                            <?php foreach ($perms as $perm) : ?>
                                <span class="sjl-ak-method sjl-method-<?php echo esc_attr(strtolower($perm)); ?>"><?php echo esc_html($perm); ?></span>
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
            <?php if ($akPages > 1) :
                $akBaseUrl = admin_url('admin.php?page=sjl-user-api-keys');
                ?>
                <div class="row mt-3">
                    <div class="col-md-12 text-center">
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center">
                                <li class="page-item <?php echo $akPage <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('ak_page', '1', $akBaseUrl)); ?>">
                                        &laquo; <?php echo esc_html__('First', 'simple-jwt-login'); ?>
                                    </a>
                                </li>
                                <li class="page-item <?php echo $akPage <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('ak_page', (string) max(1, $akPage - 1), $akBaseUrl)); ?>">
                                        &lsaquo; <?php echo esc_html__('Prev', 'simple-jwt-login'); ?>
                                    </a>
                                </li>
                                <?php
                                $akWindow = 4;
                                $akStart  = max(1, min($akPage - (int) ($akWindow / 2), $akPages - $akWindow + 1));
                                $akEnd    = min($akPages, $akStart + $akWindow - 1);
                                for ($akP = $akStart; $akP <= $akEnd; $akP++) :
                                    ?>
                                    <li class="page-item <?php echo $akP === $akPage ? 'active' : ''; ?>">
                                        <?php if ($akP === $akPage) : ?>
                                            <span class="page-link"><?php echo (int) $akP; ?></span>
                                        <?php else : ?>
                                            <a class="page-link" href="<?php echo esc_url(add_query_arg('ak_page', (string) $akP, $akBaseUrl)); ?>">
                                                <?php echo (int) $akP; ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $akPage >= $akPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('ak_page', (string) min($akPages, $akPage + 1), $akBaseUrl)); ?>">
                                        <?php echo esc_html__('Next', 'simple-jwt-login'); ?> &rsaquo;
                                    </a>
                                </li>
                                <li class="page-item <?php echo $akPage >= $akPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('ak_page', (string) $akPages, $akBaseUrl)); ?>">
                                        <?php echo esc_html__('Last', 'simple-jwt-login'); ?> &raquo;
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <p class="text-muted">
                            <small>
                                <?php echo esc_html(sprintf(
                                    /* translators: %1$d current page, %2$d total pages, %3$d total entries */
                                    __('Page %1$d of %2$d (%3$d entries total)', 'simple-jwt-login'),
                                    $akPage,
                                    $akPages,
                                    $akTotal
                                )); ?>
                            </small>
                        </p>
                    </div>
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

</div><!-- #simple-jwt-login -->
</div><!-- .wrap -->

<script>
(function () {
    window._sjlAkConfig = {
        restBase: <?php echo wp_json_encode(trailingslashit($restBase) . RouteService::API_KEYS_ROUTE); ?>,
        nonce:    <?php echo wp_json_encode($restNonce); ?>
    };
}());
</script>
