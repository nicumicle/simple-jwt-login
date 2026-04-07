<?php

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-networking"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('Allow CORS Support', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Allow cross-origin requests to JWT API endpoints.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">
        <div class="sjl-gen-radio-group">
            <label class="sjl-gen-radio-option">
                <input type="radio" name="cors[enabled]" id="allow_cors_no" value="0"
                    <?php echo $jwtSettings->getCorsSettings()->isCorsEnabled() === false ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Disabled', 'simple-jwt-login'); ?></span>
            </label>
            <label class="sjl-gen-radio-option">
                <input type="radio" name="cors[enabled]" id="allow_cors_yes" value="1"
                    <?php echo $jwtSettings->getCorsSettings()->isCorsEnabled() === true ? 'checked' : ''; ?>
                />
                <span class="sjl-gen-radio-label"><?php echo __('Enabled', 'simple-jwt-login'); ?></span>
            </label>
        </div>
    </div>
</div>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-editor-code"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('CORS Headers Configuration', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Configure which CORS headers to include in API responses. Enable a header and set its value.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="cors[allow_origin_enabled]" id="cors_allow_origin_enabled"
                       value="1" <?php echo $jwtSettings->getCorsSettings()->isAllowOriginEnabled() ? 'checked' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="cors_allow_origin_enabled" class="sjl-gen-feature-label">
                    <code class="sjl-gen-var-chip">Access-Control-Allow-Origin</code>
                </label>
                <input type="text" class="form-control sjl-gen-input-medium" name="cors[allow_origin]"
                       style="margin: 6px 0 4px;"
                       value="<?php echo esc_attr($jwtSettings->getCorsSettings()->getAllowOrigin()); ?>"
                />
                <p class="sjl-gen-feature-desc">
                    <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin" target="_blank">
                        <?php echo __('Read more', 'simple-jwt-login'); ?>
                    </a>
                </p>
            </div>
        </div>

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="cors[allow_methods_enabled]" id="cors_allow_methods_enabled"
                       value="1" <?php echo $jwtSettings->getCorsSettings()->isAllowMethodsEnabled() ? 'checked' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="cors_allow_methods_enabled" class="sjl-gen-feature-label">
                    <code class="sjl-gen-var-chip">Access-Control-Allow-Methods</code>
                </label>
                <input type="text" class="form-control sjl-gen-input-medium" name="cors[allow_methods]"
                       style="margin: 6px 0 4px;"
                       value="<?php echo esc_attr($jwtSettings->getCorsSettings()->getAllowMethods()); ?>"
                />
                <p class="sjl-gen-feature-desc">
                    <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods" target="_blank">
                        <?php echo __('Read more', 'simple-jwt-login'); ?>
                    </a>
                </p>
            </div>
        </div>

        <div class="sjl-gen-feature-toggle">
            <div class="sjl-gen-feature-toggle-check">
                <input type="checkbox" name="cors[allow_headers_enabled]" id="cors_allow_headers_enabled"
                       value="1" <?php echo $jwtSettings->getCorsSettings()->isAllowHeadersEnabled() ? 'checked' : ''; ?>
                />
            </div>
            <div class="sjl-gen-feature-toggle-text">
                <label for="cors_allow_headers_enabled" class="sjl-gen-feature-label">
                    <code class="sjl-gen-var-chip">Access-Control-Allow-Headers</code>
                </label>
                <input type="text" class="form-control sjl-gen-input-medium" name="cors[allow_headers]"
                       style="margin: 6px 0 4px;"
                       value="<?php echo esc_attr($jwtSettings->getCorsSettings()->getAllowHeaders()); ?>"
                />
                <p class="sjl-gen-feature-desc">
                    <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers" target="_blank">
                        <?php echo __('Read more', 'simple-jwt-login'); ?>
                    </a>
                </p>
            </div>
        </div>

    </div>
</div>
