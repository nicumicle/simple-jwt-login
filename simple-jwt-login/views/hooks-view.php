<?php

use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SimpleJWTLoginSettings $jwtSettings
 */

$hooks = SimpleJWTLoginHooks::getHooksDetails();

$enabledHooksCount = count(
    array_filter(
        $hooks,
        function ($h) use ($jwtSettings) {
            return $jwtSettings->getHooksSettings()->isHookEnable($h['name']);
        }
    )
);

?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-embed-generic"></span>
        <div>
            <h3 class="sjl-gen-card-title"><?php echo __('WordPress Hooks Integration', 'simple-jwt-login'); ?></h3>
            <p class="sjl-gen-card-desc">
                <?php echo __('Enable specific WordPress hooks to extend JWT functionality. Only enabled hooks will be triggered.', 'simple-jwt-login'); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <div class="sjl-hooks-toolbar">
            <span class="sjl-hooks-count-label">
                <span id="sjl-hooks-enabled-count"><?php echo $enabledHooksCount; ?></span>
                <?php
                echo sprintf(
                    ' / %d %s',
                    count($hooks),
                    esc_html(__('enabled', 'simple-jwt-login'))
                );
                ?>
            </span>
            <label class="sjl-toggle-switch" title="<?php echo esc_attr(__('Toggle all hooks', 'simple-jwt-login')); ?>">
                <input type="checkbox" id="toggleHooks" <?php echo $enabledHooksCount === count($hooks) && count($hooks) > 0 ? 'checked' : ''; ?> />
                <span class="sjl-toggle-slider"></span>
            </label>
        </div>

        <div class="sjl-hooks-list">
            <?php if (! empty($hooks)) {
                foreach ($hooks as $singleHook) {
                    $isEnabled = $jwtSettings->getHooksSettings()->isHookEnable($singleHook['name']);
                    ?>
                <div class="sjl-hook-item<?php echo $isEnabled ? ' sjl-hook-item--enabled' : ''; ?>">

                    <div class="sjl-hook-item-toggle">
                        <label class="sjl-toggle-switch">
                            <input
                                type="checkbox"
                                name="enabled_hooks[]"
                                id="hook_<?php echo esc_attr($singleHook['name']); ?>"
                                value="<?php echo esc_attr($singleHook['name']); ?>"
                                <?php echo $isEnabled ? 'checked' : ''; ?>
                            />
                            <span class="sjl-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="sjl-hook-item-body">
                        <div class="sjl-hook-item-title">
                            <label for="hook_<?php echo esc_attr($singleHook['name']); ?>">
                                <code class="sjl-gen-var-chip sjl-hook-name-chip"><?php echo esc_html($singleHook['name']); ?></code>
                            </label>
                            <span class="sjl-badge <?php echo $singleHook['type'] === 'filter' ? 'sjl-badge-count' : 'sjl-badge-on'; ?>">
                                <?php echo esc_html($singleHook['type']); ?>
                            </span>
                        </div>

                        <div class="sjl-hook-item-meta">
                            <?php if (! empty($singleHook['parameters'])) { ?>
                            <div class="sjl-hook-meta-row">
                                <span class="sjl-hook-meta-label"><?php echo __('Parameters', 'simple-jwt-login'); ?></span>
                                <span class="sjl-hook-meta-value">
                                    <?php foreach ($singleHook['parameters'] as $param) { ?>
                                        <code class="sjl-gen-var-chip"><?php echo esc_html($param); ?></code>
                                    <?php } ?>
                                </span>
                            </div>
                            <?php } ?>

                            <div class="sjl-hook-meta-row">
                                <span class="sjl-hook-meta-label"><?php echo __('Return', 'simple-jwt-login'); ?></span>
                                <span class="sjl-hook-meta-value">
                                    <?php if (isset($singleHook['return'])) {
                                        echo '<code class="sjl-gen-var-chip">' . esc_html($singleHook['return']) . '</code>';
                                    } else { ?>
                                        <code class="sjl-gen-var-chip">void</code>
                                    <?php } ?>
                                </span>
                            </div>

                            <div class="sjl-hook-meta-row sjl-hook-meta-desc">
                                <span class="sjl-hook-meta-label"><?php echo __('Description', 'simple-jwt-login'); ?></span>
                                <span class="sjl-hook-meta-value sjl-gen-card-desc">
                                    <?php echo str_replace("\n", "<br />", esc_html($singleHook['description'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
                <?php }
            } ?>
        </div>
    </div>
</div>
