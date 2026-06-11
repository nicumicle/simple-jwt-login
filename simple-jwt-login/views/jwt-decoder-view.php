<?php

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly
?>

<div class="sjl-jwt-decoder">

    <div class="sjl-section-header">
        <h2 class="sjl-section-title">
            <?php echo esc_html__('JWT Decoder', 'simple-jwt-login'); ?>
        </h2>
        <p class="sjl-section-desc">
            <?php echo esc_html__(
                'Paste a JSON Web Token to inspect its header and payload. Decoding happens entirely in your browser - the token is never sent to the server.',
                'simple-jwt-login'
            ); ?>
        </p>
    </div>

    <div class="row sjl-decoder-layout">

        <div class="col-md-5 sjl-decoder-left">
            <div class="card card-shadow sjl-decoder-input-card">
                <div class="sjl-decoder-input-label">
                    <?php echo esc_html__('Encoded Token', 'simple-jwt-login'); ?>
                    <button type="button" id="sjl-decoder-clear" class="sjl-decoder-clear-btn"
                        title="<?php echo esc_attr__('Clear', 'simple-jwt-login'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php echo esc_html__('Clear', 'simple-jwt-login'); ?>
                    </button>
                </div>
                <textarea
                    id="sjl-decoder-input"
                    class="sjl-decoder-textarea"
                    placeholder="<?php echo esc_attr__('Paste your JWT here…', 'simple-jwt-login'); ?>"
                    spellcheck="false"
                    autocomplete="off"
                ></textarea>
                <div id="sjl-decoder-error" class="sjl-decoder-error" style="display:none">
                    <span class="dashicons dashicons-warning"></span>
                    <span id="sjl-decoder-error-msg"></span>
                </div>
            </div>
        </div>

        <div class="col-md-7 sjl-decoder-right">

            <div class="card card-shadow sjl-decoder-part-card sjl-decoder-header-card">
                <div class="sjl-decoder-part-label">
                    <span class="sjl-decoder-dot sjl-dot-header"></span>
                    <?php echo esc_html__('Header', 'simple-jwt-login'); ?>
                    <button type="button" class="sjl-decoder-copy-btn" data-target="sjl-decoder-header-json"
                        title="<?php echo esc_attr__('Copy', 'simple-jwt-login'); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
                <pre id="sjl-decoder-header-json" class="sjl-decoder-json sjl-json-header"></pre>
            </div>

            <div class="card card-shadow sjl-decoder-part-card sjl-decoder-payload-card">
                <div class="sjl-decoder-part-label">
                    <span class="sjl-decoder-dot sjl-dot-payload"></span>
                    <?php echo esc_html__('Payload', 'simple-jwt-login'); ?>
                    <button type="button" class="sjl-decoder-copy-btn" data-target="sjl-decoder-payload-json"
                        title="<?php echo esc_attr__('Copy', 'simple-jwt-login'); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
                <pre id="sjl-decoder-payload-json" class="sjl-decoder-json sjl-json-payload"></pre>
            </div>

        </div>

    </div>

</div>
