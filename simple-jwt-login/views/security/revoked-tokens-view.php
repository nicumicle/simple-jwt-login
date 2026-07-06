<?php

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use SimpleJWTLogin\Services\RouteService;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
}

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 * @var RevokedTokenRepository $revokedTokenRepo
 */

$namespace = rtrim($jwtSettings->getGeneralSettings()->getRouteNamespace(), '/');
$restBase  = rest_url($namespace);
$restNonce = wp_create_nonce('wp_rest');

//phpcs:ignore WordPress.Security.NonceVerification.Recommended
$rtPage    = isset($_GET['rt_page']) ? max(1, (int) $_GET['rt_page']) : 1;
$rtPerPage = 20;

$rtResult = $revokedTokenRepo->findAll($rtPage, $rtPerPage);
$rtItems  = $rtResult['items'];
$rtTotal  = $rtResult['total'];
$rtPages  = $rtTotal > 0 ? (int) ceil($rtTotal / $rtPerPage) : 1;
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-dismiss"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo esc_html__('Revoked Tokens', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'Tokens revoked through the auth/revoke endpoint are listed here. Deleting an entry removes its revocation - the original JWT becomes valid again if it has not expired.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <?php if (empty($rtItems)) : ?>
            <p><?php echo esc_html__('No revoked tokens found.', 'simple-jwt-login'); ?></p>
        <?php else : ?>
            <table class="widefat sjl-ak-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('User ID', 'simple-jwt-login'); ?></th>
                        <th><?php echo esc_html__('Token', 'simple-jwt-login'); ?></th>
                        <th><?php echo esc_html__('Revoked At', 'simple-jwt-login'); ?></th>
                        <th><?php echo esc_html__('Expires At', 'simple-jwt-login'); ?></th>
                        <th><?php echo esc_html__('Action', 'simple-jwt-login'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rtItems as $rt) :
                        $maskedHash = substr($rt->token_hash, 0, 8) . '…' . substr($rt->token_hash, -4);
                        ?>
                    <tr>
                        <td><?php echo (int) $rt->user_id; ?></td>
                        <td><code><?php echo esc_html($maskedHash); ?></code></td>
                        <td><?php echo esc_html($rt->revoked_at); ?></td>
                        <td><?php echo esc_html($rt->expires_at ?: __('Never', 'simple-jwt-login')); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="sjlDeleteRevokedToken(<?php echo (int) $rt->id; ?>)">
                                <span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:3px;"></span>
                                <?php echo esc_html__('Delete', 'simple-jwt-login'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($rtPages > 1) : ?>
                <?php
                $rtBaseUrl = add_query_arg(array('active_tab' => SettingsErrors::PREFIX_REVOKED_TOKENS));
                ?>
                <div class="row mt-3">
                    <div class="col-md-12 text-center">
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center">
                                <li class="page-item <?php echo $rtPage <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('rt_page', '1', $rtBaseUrl)); ?>">
                                        &laquo; <?php echo esc_html__('First', 'simple-jwt-login'); ?>
                                    </a>
                                </li>
                                <li class="page-item <?php echo $rtPage <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('rt_page', (string) max(1, $rtPage - 1), $rtBaseUrl)); ?>">
                                        &lsaquo; <?php echo esc_html__('Prev', 'simple-jwt-login'); ?>
                                    </a>
                                </li>
                                <?php
                                $rtWindow = 4;
                                $rtStart  = max(1, min($rtPage - (int) ($rtWindow / 2), $rtPages - $rtWindow + 1));
                                $rtEnd    = min($rtPages, $rtStart + $rtWindow - 1);
                                for ($rtP = $rtStart; $rtP <= $rtEnd; $rtP++) :
                                    ?>
                                    <li class="page-item <?php echo $rtP === $rtPage ? 'active' : ''; ?>">
                                        <?php if ($rtP === $rtPage) : ?>
                                            <span class="page-link"><?php echo (int) $rtP; ?></span>
                                        <?php else : ?>
                                            <a class="page-link" href="<?php echo esc_url(add_query_arg('rt_page', (string) $rtP, $rtBaseUrl)); ?>">
                                                <?php echo (int) $rtP; ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $rtPage >= $rtPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('rt_page', (string) min($rtPages, $rtPage + 1), $rtBaseUrl)); ?>">
                                        <?php echo esc_html__('Next', 'simple-jwt-login'); ?> &rsaquo;
                                    </a>
                                </li>
                                <li class="page-item <?php echo $rtPage >= $rtPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo esc_url(add_query_arg('rt_page', (string) $rtPages, $rtBaseUrl)); ?>">
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
                                    $rtPage,
                                    $rtPages,
                                    $rtTotal
                                )); ?>
                            </small>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    window._sjlRtConfig = {
        restBase: <?php echo wp_json_encode(trailingslashit($restBase) . RouteService::REVOKED_TOKENS_ROUTE); ?>,
        nonce:    <?php echo wp_json_encode($restNonce); ?>
    };
}());
</script>
