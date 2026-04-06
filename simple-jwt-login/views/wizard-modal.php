<?php
if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line */
    exit;
} // Exit if accessed directly
?>

<div id="sjl-wizard-modal" role="dialog" aria-modal="true" aria-labelledby="sjl-wizard-step-title">
    <div class="sjl-wizard-backdrop"></div>
    <div class="sjl-wizard-container">

        <!-- Header: title + progress -->
        <div class="sjl-wizard-header">
            <div class="sjl-wizard-header-row">
                <span class="sjl-wizard-title">
                    <?php echo esc_html(__('Simple JWT Login - Setup Wizard', 'simple-jwt-login')); ?>
                </span>
                <button type="button" class="sjl-wizard-close-btn notice-dismiss" aria-label="<?php echo esc_attr(__('Close', 'simple-jwt-login')); ?>"></button>
            </div>
            <div class="sjl-wizard-progress-track">
                <div id="sjl-wizard-progress-fill"></div>
            </div>
            <div id="sjl-wizard-progress-label"><?php echo esc_html(__('Step 1 - Select features', 'simple-jwt-login')); ?></div>
        </div>

        <!-- Step title / subtitle (populated by wizard.js) -->
        <div class="sjl-wizard-step-header">
            <h3 id="sjl-wizard-step-title"></h3>
            <p  id="sjl-wizard-step-subtitle"></p>
        </div>

        <!-- Dynamic content area -->
        <div id="sjl-wizard-content"></div>

        <!-- Footer navigation -->
        <div class="sjl-wizard-footer">
            <button type="button" id="sjl-wizard-btn-prev" class="btn btn-secondary sjl-hidden">
                &larr; <?php echo esc_html(__('Previous', 'simple-jwt-login')); ?>
            </button>
            <div class="sjl-wizard-footer-right">
                <button type="button" id="sjl-wizard-btn-next" class="btn btn-dark">
                    <?php echo esc_html(__('Next', 'simple-jwt-login')); ?> &rarr;
                </button>
                <button type="button" id="sjl-wizard-btn-finish" class="btn btn-success sjl-hidden">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../images/correct.svg'); ?>" class="sjl-wizard-btn-icon" alt="" aria-hidden="true">
                    <?php echo esc_html(__('Save Settings', 'simple-jwt-login')); ?>
                </button>
            </div>
        </div>

    </div>
</div>