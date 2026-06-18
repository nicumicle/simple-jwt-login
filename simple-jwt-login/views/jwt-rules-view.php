<?php

use SimpleJWTLogin\Helpers\Jwt\JwtKeyWpConfig;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */

$elseAlgorithm = $jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm();
$elseIsRS      = strpos($elseAlgorithm, 'RS') !== false;
$elseSource    = $jwtSettings->getGeneralSettings()->getDecryptionSource();
?>

<div class="sjl-gen-card">
    <div class="sjl-gen-card-header">
        <span class="dashicons dashicons-shield"></span>
        <div>
            <h3 class="sjl-gen-card-title">
                <?php
                $keyErrors = [
                    SettingsErrors::ERR_GENERAL_PRIVATE_KEY_MISSING_FROM_CODE_RS,
                    SettingsErrors::ERR_GENERAL_PRIVATE_KEY_NOT_PRESENT_IN_CODE_HS,
                    SettingsErrors::ERR_GENERAL_MISSING_PRIVATE_AND_PUBLIC_KEY,
                    SettingsErrors::ERR_GENERAL_DECRYPTION_KEY_REQUIRED,
                ];
                $hasKeyError = false;
                foreach ($keyErrors as $errConst) {
                    if (isset($errorCode)
                        && $settingsErrors->generateCode(SettingsErrors::PREFIX_GENERAL, $errConst) === $errorCode
                    ) {
                        $hasKeyError = true;
                        break;
                    }
                }
                if ($hasKeyError) {
                    echo '<span class="simple-jwt-error">!</span>';
                }
                ?>
                <?php echo esc_html__('JWT Verification Rules', 'simple-jwt-login'); ?>
            </h3>
            <p class="sjl-gen-card-desc">
                <?php echo esc_html__(
                    'Define how incoming JWTs are verified. Each rule matches a specific claim in the token and applies its own algorithm and key. The ELSE row is the required fallback used when no rule matches.',
                    'simple-jwt-login'
                ); ?>
            </p>
        </div>
    </div>
    <div class="sjl-gen-card-body">

        <!-- IF / ELSE IF rules -->
        <div id="sjl-jwt-rules">
            <?php
            foreach ($jwtSettings->getJwtRulesSettings()->getRules() as $rule) {
                $ruleAlgorithm    = isset($rule['algorithm']) ? $rule['algorithm'] : 'HS256';
                $isRS               = strpos($ruleAlgorithm, 'RS') !== false;
                $conditionType      = isset($rule['condition_type']) ? $rule['condition_type'] : 'payload';
                $conditionKey       = isset($rule['condition_key']) ? $rule['condition_key'] : 'iss';
                $conditionOperator  = isset($rule['condition_operator']) ? $rule['condition_operator'] : 'equals';
                $conditionValue     = isset($rule['condition_value']) ? $rule['condition_value'] : (isset($rule['iss']) ? $rule['iss'] : '');
                $ruleKey            = isset($rule['decryption_key']) ? $rule['decryption_key'] : '';
                $ruleKeyB64         = !empty($rule['decryption_key_base64']);
                $rulePubKey         = ($isRS && isset($rule['decryption_key_public']))
                    ? (string)base64_decode($rule['decryption_key_public'])
                    : '';
                $rulePrivKey        = ($isRS && isset($rule['decryption_key_private']))
                    ? (string)base64_decode($rule['decryption_key_private'])
                    : '';
                ?>
                <div class="sjl-rule-row">

                    <!-- IF: Condition ─────────────────────────────────── -->
                    <div class="sjl-rule-if-row">
                        <span class="sjl-rule-badge sjl-rule-condition-badge">
                            <?php echo esc_html__('IF', 'simple-jwt-login'); ?>
                        </span>
                        <div class="sjl-rule-fields-group">
                            <div class="sjl-rule-field">
                                <label class="sjl-rule-field-label">
                                    <?php echo esc_html__('JWT Part', 'simple-jwt-login'); ?>
                                </label>
                                <select class="form-control sjl-rule-condition-type">
                                    <option value="payload" <?php echo $conditionType === 'payload' ? 'selected' : ''; ?>>
                                        <?php echo esc_html__('Payload claim', 'simple-jwt-login'); ?>
                                    </option>
                                    <option value="header" <?php echo $conditionType === 'header' ? 'selected' : ''; ?>>
                                        <?php echo esc_html__('Header claim', 'simple-jwt-login'); ?>
                                    </option>
                                </select>
                            </div>
                            <div class="sjl-rule-field sjl-rule-condition-key-group">
                                <label class="sjl-rule-field-label">
                                    <?php echo esc_html__('Claim Key', 'simple-jwt-login'); ?>
                                </label>
                                <input
                                    type="text"
                                    class="form-control sjl-rule-condition-key"
                                    value="<?php echo esc_attr($conditionKey); ?>"
                                    placeholder="<?php echo esc_attr(__('e.g. iss', 'simple-jwt-login')); ?>"
                                />
                            </div>
                            <div class="sjl-rule-field">
                                <label class="sjl-rule-field-label">
                                    <?php echo esc_html__('Operator', 'simple-jwt-login'); ?>
                                </label>
                                <select class="form-control sjl-rule-condition-operator">
                                    <option value="equals" <?php echo $conditionOperator === 'equals' ? 'selected' : ''; ?>>
                                        <?php echo esc_html__('equals', 'simple-jwt-login'); ?>
                                    </option>
                                    <option value="contains" <?php echo $conditionOperator === 'contains' ? 'selected' : ''; ?>>
                                        <?php echo esc_html__('contains', 'simple-jwt-login'); ?>
                                    </option>
                                </select>
                            </div>
                            <div class="sjl-rule-field sjl-rule-field--grow">
                                <label class="sjl-rule-field-label">
                                    <?php echo esc_html__('Expected Value', 'simple-jwt-login'); ?>
                                </label>
                                <input
                                    type="text"
                                    class="form-control sjl-rule-condition-value"
                                    value="<?php echo esc_attr($conditionValue); ?>"
                                    placeholder="<?php echo esc_attr(__('e.g. https://auth0.example.com/', 'simple-jwt-login')); ?>"
                                />
                            </div>
                        </div>
                        <button type="button" class="sjl-rule-remove"
                                aria-label="<?php echo esc_attr(__('Remove rule', 'simple-jwt-login')); ?>"
                                title="<?php echo esc_attr(__('Remove rule', 'simple-jwt-login')); ?>">&times;</button>
                    </div>

                    <!-- THEN USE: Algorithm + Key ───────────────────────── -->
                    <div class="sjl-rule-then-row">
                        <span class="sjl-rule-badge sjl-rule-badge-then">
                            <?php echo esc_html__('THEN USE', 'simple-jwt-login'); ?>
                        </span>
                        <div class="sjl-rule-fields-group">
                            <div class="sjl-rule-field">
                                <label class="sjl-rule-field-label">
                                    <?php echo esc_html__('Algorithm', 'simple-jwt-login'); ?>
                                </label>
                                <select class="form-control sjl-gen-select sjl-rule-alg">
                                    <?php
                                    foreach (JWT::$supportedAlgs as $alg => $arr) {
                                        $sel = $ruleAlgorithm === $alg ? 'selected' : '';
                                        echo "<option value=\"" . esc_attr($alg) . "\" " . esc_attr($sel) . ">"
                                            . esc_html($alg) . "</option>\n";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- HS* key field (shown for HS algorithms) -->
                            <div class="sjl-rule-hs-fields sjl-rule-field sjl-rule-field--grow"
                                 <?php echo $isRS ? 'style="display:none"' : ''; ?>>
                                <label class="sjl-rule-field-label">
                                    <?php echo esc_html__('Secret Key', 'simple-jwt-login'); ?>
                                </label>
                                <div class="input-group sjl-rule-key-group">
                                    <input
                                        type="password"
                                        class="form-control sjl-rule-key"
                                        value="<?php echo esc_attr($ruleKey); ?>"
                                        placeholder="<?php echo esc_attr(__('Enter the shared secret used to sign this token', 'simple-jwt-login')); ?>"
                                        autocomplete="off"
                                    />
                                    <div class="input-group-addon">
                                        <a href="javascript:void(0)"
                                           class="toggle_key_button sjl-rule-toggle-key"
                                           title="<?php echo esc_attr(__('Toggle key visibility', 'simple-jwt-login')); ?>">
                                            <i class="toggle-image" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="sjl-gen-checkbox-row">
                                    <input type="checkbox" class="sjl-rule-key-b64"
                                           <?php echo $ruleKeyB64 ? 'checked' : ''; ?> />
                                    <label><?php echo esc_html__('Key is Base64 encoded', 'simple-jwt-login'); ?></label>
                                </div>
                            </div>

                            <!-- RS* key fields (shown for RS algorithms) -->
                            <div class="sjl-rule-rs-fields"
                                 <?php echo !$isRS ? 'style="display:none"' : ''; ?>>
                                <div class="sjl-rule-rs-fields-inner">
                                    <div class="sjl-rule-field sjl-rule-field--grow">
                                        <label class="sjl-rule-field-label">
                                            <?php echo esc_html__('Public Key', 'simple-jwt-login'); ?>
                                            <span class="required">*</span>
                                        </label>
                                        <textarea class="form-control sjl-rule-pub-key" rows="4"
                                        ><?php echo esc_html($rulePubKey); ?></textarea>
                                    </div>
                                    <div class="sjl-rule-field sjl-rule-field--grow">
                                        <label class="sjl-rule-field-label">
                                            <?php echo esc_html__('Private Key', 'simple-jwt-login'); ?>
                                            <span class="required">*</span>
                                        </label>
                                        <textarea class="form-control sjl-rule-priv-key" rows="4"
                                        ><?php echo esc_html($rulePrivKey); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- IDENTIFY: User lookup ───────────────────────── -->
                    <?php
                    $ruleLoginBy = isset($rule['login_by']) ? (int)$rule['login_by'] : 0;
                    $ruleLoginByParam = isset($rule['login_by_parameter']) ? $rule['login_by_parameter'] : '';
                    ?>
                    <div class="sjl-rule-identify-row">
                        <span class="sjl-rule-badge sjl-rule-badge-identify">
                            <?php echo esc_html__('IDENTIFY', 'simple-jwt-login'); ?>
                        </span>
                        <div class="sjl-rule-fields-group">
                            <div class="sjl-rule-field">
                                <label class="sjl-rule-field-label">
                                    <?php echo esc_html__('Identify user by', 'simple-jwt-login'); ?>
                                </label>
                                <select class="form-control sjl-rule-login-by">
                                    <option value="0" <?php echo $ruleLoginBy === 0 ? 'selected' : ''; ?>>
                                        <?php echo esc_html__('Email address', 'simple-jwt-login'); ?>
                                    </option>
                                    <option value="1" <?php echo $ruleLoginBy === 1 ? 'selected' : ''; ?>>
                                        <?php echo esc_html__('WordPress User ID', 'simple-jwt-login'); ?>
                                    </option>
                                    <option value="2" <?php echo $ruleLoginBy === 2 ? 'selected' : ''; ?>>
                                        <?php echo esc_html__('WordPress Username', 'simple-jwt-login'); ?>
                                    </option>
                                </select>
                            </div>
                            <div class="sjl-rule-field sjl-rule-field--grow">
                                <label class="sjl-rule-field-label">
                                    <?php echo esc_html__('JWT payload key', 'simple-jwt-login'); ?>
                                    <span class="required">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-control sjl-rule-login-by-param"
                                    value="<?php echo esc_attr($ruleLoginByParam); ?>"
                                    placeholder="<?php echo esc_attr(__('e.g. email', 'simple-jwt-login')); ?>"
                                />
                            </div>
                        </div>
                    </div>

                </div><!-- /.sjl-rule-row -->
                <?php
            }
            ?>
        </div><!-- /#sjl-jwt-rules -->

        <div style="margin: 10px 0 20px;">
            <input
                type="button"
                class="btn btn-outline-secondary"
                id="sjl-add-rule"
                value="<?php echo esc_attr__('+ Add Rule', 'simple-jwt-login'); ?>"
            />
        </div>

        <!-- Hidden JSON carrier for the IF / ELSE IF rules -->
        <input type="hidden" name="jwt_rules" id="jwt_rules_json" value="[]" />

        <!-- ELSE row - required default, backed by GeneralSettings fields -->
        <div class="sjl-rule-else-wrapper">
            <div class="sjl-rule-else-header">
                <span class="sjl-rule-badge sjl-rule-badge-else">
                    <?php echo esc_html__('ELSE (default - used when no rule matches)', 'simple-jwt-login'); ?>
                </span>
                <span class="dashicons dashicons-lock sjl-rule-lock-icon"
                      title="<?php echo esc_attr(__('Required - cannot be removed', 'simple-jwt-login')); ?>"></span>
            </div>

            <!-- Key source -->
            <div class="sjl-gen-step">
                <div class="sjl-gen-step-number">1</div>
                <div class="sjl-gen-step-content">
                    <label class="sjl-gen-step-label" for="decryption_source">
                        <?php echo esc_html__('Where is your JWT secret stored?', 'simple-jwt-login'); ?>
                    </label>
                    <select id="decryption_source" name="decryption_source" class="form-control sjl-gen-select">
                        <option
                            value="<?php echo esc_attr(GeneralSettings::DECRYPTION_SOURCE_SETTINGS); ?>"
                            <?php echo ($elseSource === GeneralSettings::DECRYPTION_SOURCE_SETTINGS ? 'selected' : ''); ?>
                        ><?php echo esc_html__('Plugin Settings (recommended)', 'simple-jwt-login'); ?></option>
                        <option
                            value="<?php echo esc_attr(GeneralSettings::DECRYPTION_SOURCE_CODE); ?>"
                            <?php echo ($elseSource === GeneralSettings::DECRYPTION_SOURCE_CODE ? 'selected' : ''); ?>
                        ><?php echo esc_html__('Code (wp-config.php or custom plugin)', 'simple-jwt-login'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Algorithm -->
            <div class="sjl-gen-step">
                <div class="sjl-gen-step-number">2</div>
                <div class="sjl-gen-step-content">
                    <label class="sjl-gen-step-label" for="simple-jwt-login-jwt-algorithm">
                        <?php echo esc_html__('JWT Algorithm', 'simple-jwt-login'); ?>
                    </label>
                    <select name="jwt_algorithm" class="form-control sjl-gen-select"
                            id="simple-jwt-login-jwt-algorithm">
                        <?php
                        foreach (JWT::$supportedAlgs as $alg => $arr) {
                            $selected = $elseAlgorithm === $alg ? 'selected' : '';
                            echo "<option value=\"" . esc_attr($alg) . "\" " . esc_attr($selected) . ">"
                                . esc_html($alg) . "</option>\n";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Verification key -->
            <div class="sjl-gen-step">
                <div class="sjl-gen-step-number">
                    <?php
                    echo $hasKeyError
                        ? '<span class="simple-jwt-error">!</span>'
                        : '3';
                    ?>
                </div>
                <div class="sjl-gen-step-content">
                    <label class="sjl-gen-step-label">
                        <?php echo esc_html__('JWT Verification Key', 'simple-jwt-login'); ?>
                        <span class="required">*</span>
                    </label>

                    <!-- Symmetric key (HS*) -->
                    <div class="decryption-input-group">
                        <div class="input-group" id="decryption_key_container">
                            <input type="password" name="decryption_key" class="form-control"
                                   id="decryption_key" autocomplete="off"
                                   value="<?php echo esc_attr($jwtSettings->getGeneralSettings()->getDecryptionKey()); ?>"
                                   placeholder="<?php echo esc_attr(__('Enter JWT secret key', 'simple-jwt-login')); ?>"
                            />
                            <div class="input-group-addon">
                                <a href="javascript:void(0)"
                                   onclick="sjlShowDecryptionKey()"
                                   class="toggle_key_button"
                                   title="<?php echo esc_attr(__('Toggle key visibility', 'simple-jwt-login')); ?>">
                                    <i class="toggle-image" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                        <div class="sjl-gen-strength-row">
                            <span><?php echo esc_html__('Strength', 'simple-jwt-login'); ?>:</span>
                            <progress id="decryption_progress" value="0" max="100"></progress>
                            <span id="decryption_progress_label" class="sjl-gen-strength-label"></span>
                        </div>
                        <div class="sjl-gen-checkbox-row">
                            <input type="checkbox" name="decryption_key_base64"
                                   id="decryption_key_base64" value="1"
                                   <?php echo $jwtSettings->getGeneralSettings()->isDecryptionKeyBase64Encoded()
                                       ? esc_html('checked="checked"') : ''; ?>
                            />
                            <label for="decryption_key_base64">
                                <?php echo esc_html__('JWT key is Base64 encoded', 'simple-jwt-login'); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Asymmetric keys (RS*) -->
                    <div class="decryption-textarea-group">
                        <div class="form-group">
                            <label for="simple-jwt-login-public-key">
                                <?php echo esc_html__('Public Key', 'simple-jwt-login'); ?>
                                <span class="required">*</span>
                            </label>
                            <textarea class="form-control" id="simple-jwt-login-public-key"
                                      rows="6" name="decryption_key_public"
                            ><?php echo esc_html($jwtSettings->getGeneralSettings()->getDecryptionKeyPublic()); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="simple-jwt-login-private-key">
                                <?php echo esc_html__('Private Key', 'simple-jwt-login'); ?>
                                <span class="required">*</span>
                            </label>
                            <textarea class="form-control" id="simple-jwt-login-private-key"
                                      rows="6" name="decryption_key_private"
                            ><?php echo esc_html($jwtSettings->getGeneralSettings()->getDecryptionKeyPrivate()); ?></textarea>
                        </div>
                    </div>

                    <!-- Code-based key info -->
                    <div class="decryption-code-info sjl-gen-code-block">
                        <p class="sjl-gen-code-block-intro">
                            <?php echo esc_html__('Define the following constants in your code (e.g. in', 'simple-jwt-login'); ?>
                            <code>wp-config.php</code>):
                        </p>
                        <code class="define_private_key sjl-gen-code-line">
                            define('<strong><?php echo esc_html(JwtKeyWpConfig::SIMPLE_JWT_PRIVATE_KEY); ?></strong>',
                            'MY_SECRET_KEY');
                        </code>
                        <code class="define_public_key sjl-gen-code-line">
                            define('<strong><?php echo esc_html(JwtKeyWpConfig::SIMPLE_JWT_PUBLIC_KEY); ?></strong>',
                            'MY_PUBLIC_KEY');
                        </code>
                    </div>
                </div>
            </div>
            <!-- User identification -->
            <div class="sjl-gen-step">
                <div class="sjl-gen-step-number">4</div>
                <div class="sjl-gen-step-content">
                    <label class="sjl-gen-step-label">
                        <?php echo esc_html__('User Identification', 'simple-jwt-login'); ?>
                    </label>
                    <p class="sjl-gen-step-desc">
                        <?php echo esc_html__('Which JWT payload field identifies the WordPress user?', 'simple-jwt-login'); ?>
                    </p>
                    <div class="sjl-gen-two-col">
                        <div class="sjl-gen-two-col-left">
                            <label class="sjl-gen-field-label" for="jwt_login_by">
                                <?php echo esc_html__('Identify user by', 'simple-jwt-login'); ?>
                            </label>
                            <select name="jwt_login_by" class="form-control" id="jwt_login_by">
                                <option value="0"
                                    <?php echo $jwtSettings->getLoginSettings()->getJWTLoginBy() === LoginSettings::JWT_LOGIN_BY_EMAIL ? 'selected' : ''; ?>
                                ><?php echo esc_html__('Email address', 'simple-jwt-login'); ?></option>
                                <option value="1"
                                    <?php echo $jwtSettings->getLoginSettings()->getJWTLoginBy() === LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID ? 'selected' : ''; ?>
                                ><?php echo esc_html__('WordPress User ID', 'simple-jwt-login'); ?></option>
                                <option value="2"
                                    <?php echo $jwtSettings->getLoginSettings()->getJWTLoginBy() === LoginSettings::JWT_LOGIN_BY_USER_LOGIN ? 'selected' : ''; ?>
                                ><?php echo esc_html__('WordPress Username', 'simple-jwt-login'); ?></option>
                            </select>
                        </div>
                        <div class="sjl-gen-two-col-right">
                            <label class="sjl-gen-field-label" for="jwt_login_by_parameter">
                                <?php echo esc_html__('JWT payload key', 'simple-jwt-login'); ?>
                                <span class="required">*</span>
                            </label>
                            <input type="text" name="jwt_login_by_parameter" class="form-control"
                                   id="jwt_login_by_parameter"
                                   value="<?php echo esc_attr($jwtSettings->getLoginSettings()->getJwtLoginByParameter()); ?>"
                                   placeholder="<?php echo esc_attr__('e.g. email', 'simple-jwt-login'); ?>"
                            />
                            <p class="sjl-gen-card-desc" style="margin-top:6px;">
                                <?php echo esc_html__('Use dot notation for nested values, e.g. <code>user.id</code>.', 'simple-jwt-login'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.sjl-rule-else-wrapper -->

    </div><!-- /.sjl-gen-card-body -->
</div><!-- /.sjl-gen-card -->

<!-- Hidden row template used by JavaScript to clone new rule rows -->
<div id="sjl-rule-row-template" style="display:none">
    <div class="sjl-rule-row">

        <!-- IF: Condition ─────────────────────────────────── -->
        <div class="sjl-rule-if-row">
            <span class="sjl-rule-badge sjl-rule-condition-badge">
                <?php echo esc_html__('IF', 'simple-jwt-login'); ?>
            </span>
            <div class="sjl-rule-fields-group">
                <div class="sjl-rule-field">
                    <label class="sjl-rule-field-label">
                        <?php echo esc_html__('JWT Part', 'simple-jwt-login'); ?>
                    </label>
                    <select class="form-control sjl-rule-condition-type">
                        <option value="payload"><?php echo esc_html__('Payload claim', 'simple-jwt-login'); ?></option>
                        <option value="header"><?php echo esc_html__('Header claim', 'simple-jwt-login'); ?></option>
                    </select>
                </div>
                <div class="sjl-rule-field sjl-rule-condition-key-group">
                    <label class="sjl-rule-field-label">
                        <?php echo esc_html__('Claim Key', 'simple-jwt-login'); ?>
                    </label>
                    <input
                        type="text"
                        class="form-control sjl-rule-condition-key"
                        value=""
                        placeholder="<?php echo esc_attr(__('e.g. iss', 'simple-jwt-login')); ?>"
                    />
                </div>
                <div class="sjl-rule-field">
                    <label class="sjl-rule-field-label">
                        <?php echo esc_html__('Operator', 'simple-jwt-login'); ?>
                    </label>
                    <select class="form-control sjl-rule-condition-operator">
                        <option value="equals"><?php echo esc_html__('equals', 'simple-jwt-login'); ?></option>
                        <option value="contains"><?php echo esc_html__('contains', 'simple-jwt-login'); ?></option>
                    </select>
                </div>
                <div class="sjl-rule-field sjl-rule-field--grow">
                    <label class="sjl-rule-field-label">
                        <?php echo esc_html__('Expected Value', 'simple-jwt-login'); ?>
                    </label>
                    <input
                        type="text"
                        class="form-control sjl-rule-condition-value"
                        value=""
                        placeholder="<?php echo esc_attr(__('e.g. https://auth0.example.com/', 'simple-jwt-login')); ?>"
                    />
                </div>
            </div>
            <button type="button" class="sjl-rule-remove"
                    aria-label="<?php echo esc_attr(__('Remove rule', 'simple-jwt-login')); ?>"
                    title="<?php echo esc_attr(__('Remove rule', 'simple-jwt-login')); ?>">&times;</button>
        </div>

        <!-- THEN USE: Algorithm + Key ───────────────────────── -->
        <div class="sjl-rule-then-row">
            <span class="sjl-rule-badge sjl-rule-badge-then">
                <?php echo esc_html__('THEN USE', 'simple-jwt-login'); ?>
            </span>
            <div class="sjl-rule-fields-group">
                <div class="sjl-rule-field">
                    <label class="sjl-rule-field-label">
                        <?php echo esc_html__('Algorithm', 'simple-jwt-login'); ?>
                    </label>
                    <select class="form-control sjl-gen-select sjl-rule-alg">
                        <?php
                        foreach (JWT::$supportedAlgs as $alg => $arr) {
                            echo "<option value=\"" . esc_attr($alg) . "\">" . esc_html($alg) . "</option>\n";
                        }
                        ?>
                    </select>
                </div>

                <!-- HS* key field (shown for HS algorithms) -->
                <div class="sjl-rule-hs-fields sjl-rule-field sjl-rule-field--grow">
                    <label class="sjl-rule-field-label">
                        <?php echo esc_html__('Secret Key', 'simple-jwt-login'); ?>
                    </label>
                    <div class="input-group sjl-rule-key-group">
                        <input
                            type="password"
                            class="form-control sjl-rule-key"
                            value=""
                            placeholder="<?php echo esc_attr(__('Enter the shared secret used to sign this token', 'simple-jwt-login')); ?>"
                            autocomplete="off"
                        />
                        <div class="input-group-addon">
                            <a href="javascript:void(0)"
                               class="toggle_key_button sjl-rule-toggle-key"
                               title="<?php echo esc_attr(__('Toggle key visibility', 'simple-jwt-login')); ?>">
                                <i class="toggle-image" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                    <div class="sjl-gen-checkbox-row">
                        <input type="checkbox" class="sjl-rule-key-b64" />
                        <label><?php echo esc_html__('Key is Base64 encoded', 'simple-jwt-login'); ?></label>
                    </div>
                </div>

                <!-- RS* key fields (shown for RS algorithms) -->
                <div class="sjl-rule-rs-fields" style="display:none">
                    <div class="sjl-rule-rs-fields-inner">
                        <div class="sjl-rule-field sjl-rule-field--grow">
                            <label class="sjl-rule-field-label">
                                <?php echo esc_html__('Public Key', 'simple-jwt-login'); ?>
                                <span class="required">*</span>
                            </label>
                            <textarea class="form-control sjl-rule-pub-key" rows="4"></textarea>
                        </div>
                        <div class="sjl-rule-field sjl-rule-field--grow">
                            <label class="sjl-rule-field-label">
                                <?php echo esc_html__('Private Key', 'simple-jwt-login'); ?>
                                <span class="required">*</span>
                            </label>
                            <textarea class="form-control sjl-rule-priv-key" rows="4"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- IDENTIFY: User lookup ───────────────────────── -->
        <div class="sjl-rule-identify-row">
            <span class="sjl-rule-badge sjl-rule-badge-identify">
                <?php echo esc_html__('IDENTIFY', 'simple-jwt-login'); ?>
            </span>
            <div class="sjl-rule-fields-group">
                <div class="sjl-rule-field">
                    <label class="sjl-rule-field-label">
                        <?php echo esc_html__('Identify user by', 'simple-jwt-login'); ?>
                    </label>
                    <select class="form-control sjl-rule-login-by">
                        <option value="0"><?php echo esc_html__('Email address', 'simple-jwt-login'); ?></option>
                        <option value="1"><?php echo esc_html__('WordPress User ID', 'simple-jwt-login'); ?></option>
                        <option value="2"><?php echo esc_html__('WordPress Username', 'simple-jwt-login'); ?></option>
                    </select>
                </div>
                <div class="sjl-rule-field sjl-rule-field--grow">
                    <label class="sjl-rule-field-label">
                        <?php echo esc_html__('JWT payload key', 'simple-jwt-login'); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        class="form-control sjl-rule-login-by-param"
                        value=""
                        placeholder="<?php echo esc_attr(__('e.g. email', 'simple-jwt-login')); ?>"
                    />
                </div>
            </div>
        </div>

    </div>
</div><!-- /#sjl-rule-row-template -->
