<?php

use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Protect endpoints enabled', 'simple-jwt-login'); ?></h3>
        <div class="form-group">
            <input type="radio" id="protect_endpoints_enabled_no" name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[enabled]" class="form-control"
                   value="0"
                <?php echo $jwtSettings->getProtectEndpointsSettings()->isEnabled() === false
                    ? esc_html('checked')
                    : esc_html('');
                ?>
            />
            <label for="protect_endpoints_enabled_no">
                <?php echo __('No', 'simple-jwt-login'); ?>
            </label>

            <input type="radio" id="protect_endpoints_enabled_yes"  name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[enabled]" class="form-control"
                   value="1"
                <?php echo
                $jwtSettings->getProtectEndpointsSettings()->isEnabled()
                    ? esc_html('checked')
                    : esc_html('');
                ?>
            />
            <label for="protect_endpoints_enabled_yes">
                <?php echo __('Yes', 'simple-jwt-login'); ?>
            </label>
            <br/>
            <br />
            <p>
              <?php echo __('The endpoints will require a JWT in order to be accessed. If no JWT is provided, rest endpoints will provide an error instead of the actual content.', 'simple-jwt-login');?>
            </p>
        </div>
    </div>
</div>
<hr/>


<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <h2 class="section-title">Action</h2>
            <select id="protection_type" name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[action]" class="form-control">
                <option
                        value="<?php echo esc_attr(ProtectEndpointSettings::ALL_ENDPOINTS);?>"
                    <?php
                    echo $jwtSettings->getProtectEndpointsSettings()->getAction() === ProtectEndpointSettings::ALL_ENDPOINTS
                        ? esc_html('selected')
                        : esc_html('')
                    ?>
                >
                    <?php echo __('Apply on All REST Endpoints', 'simple-jwt-login');?>
                </option>
                <option
                        value="<?php echo esc_attr(ProtectEndpointSettings::SPECIFIC_ENDPOINTS);?>"
                    <?php echo $jwtSettings->getProtectEndpointsSettings()->getAction() === ProtectEndpointSettings::SPECIFIC_ENDPOINTS
                        ? esc_html('selected')
                        : esc_html('')
                    ?>
                >
                    <?php echo __('Apply only on Specific REST endpoints', 'simple-jwt-login');?>
                </option>
            </select>
        </div>
    </div>
</div>
<hr />

<div class="row" id="protected_endpoints_whitelisted">
    <div class="col-md-12">
        <h2 class="section-title">
            <?php echo __('Whitelisted endpoints', 'simple-jwt-login');?>
        </h2>
        <p class="text-muted">
            <?php echo __('These endpoints will skip the check for the JWT.', 'simple-jwt-login');?>
        </p>
    </div>
    <div class="col-md-12">
        <input type="button" class="btn btn-dark" value="<?php echo __('Add Endpoint', 'simple-jwt-login');?> +" id="add_whitelist_endpoint" >
    </div>
    <div class="col-md-12">
        <div id="whitelisted-domains">
            <?php
            foreach ($jwtSettings->getProtectEndpointsSettings()->getWhitelistedDomains() as $endpoint) {
                ?>
                <div class="form-group endpoint_row">
                    <div class="input-group">
                        <input type="text"
                               name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[whitelist][]"
                               class="form-control"
                               value="<?php echo esc_attr($endpoint); ?>"
                               placeholder="<?php echo __('Endpoint path', 'simple-jwt-login'); ?>"
                        />
                        <div class="input-group-addon auth-code-delete-container">
                            <a href="javascript:void(0)"
                               onclick="jwt_login_remove_endpoint_row(jQuery(this));"
                               title="<?php echo __('delete', 'simple-jwt-login'); ?>"
                            >
                                <i class="delete-auth-code" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>



<div class="row" id="protected_endpoints_protected">
    <div class="col-md-12">
        <h2 class="section-title">
            <?php echo __('Protected endpoints', 'simple-jwt-login');?>
        </h2>
        <p class="text-muted">
            <?php echo __('The JWT will be required on the following endpoints.' , 'simple-jwt-login');?>
        </p>
    </div>
    <div class="col-md-12">
        <input type="button" class="btn btn-dark" value="<?php echo __('Add Endpoint', 'simple-jwt-login');?> +" id="add_protect_endpoint">
    </div>
    <div class="col-md-12">
        <div id="protected-domains">
            <?php
            foreach ($jwtSettings->getProtectEndpointsSettings()->getProtectedEndpoints() as $endpoint) {
                ?>
                <div class="form-group endpoint_row">
                    <div class="input-group">
                        <input type="text"
                               name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[protect][]"
                               class="form-control"
                               value="<?php echo esc_attr($endpoint); ?>"
                               placeholder="<?php echo __('Endpoint path', 'simple-jwt-login'); ?>"
                        />
                        <div class="input-group-addon auth-code-delete-container">
                            <a href="javascript:void(0)"
                               onclick="jwt_login_remove_endpoint_row(jQuery(this));"
                               title="<?php echo __('delete', 'simple-jwt-login'); ?>"
                            >
                                <i class="delete-auth-code" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<hr/>


<div id="endpoint_whitelist_line" style="display: none;">
    <div class="form-group endpoint_row">
        <div class="input-group">
            <input type="text"
                   name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[whitelist][]"
                   class="form-control"
                   value=""
                   placeholder="<?php echo __('Endpoint path', 'simple-jwt-login'); ?>"
            />
            <div class="input-group-addon auth-code-delete-container">
                <a href="javascript:void(0)"
                   onclick="jwt_login_remove_endpoint_row(jQuery(this));"
                   title="<?php echo __('delete', 'simple-jwt-login'); ?>"
                >
                    <i class="delete-auth-code" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div id="endpoint_protect_line" style="display: none;">
    <div class="form-group endpoint_row">
        <div class="input-group">
            <input type="text"
                   name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[protect][]"
                   class="form-control"
                   value=""
                   placeholder="<?php echo __('Endpoint path', 'simple-jwt-login'); ?>"
            />
            <div class="input-group-addon auth-code-delete-container">
                <a href="javascript:void(0)"
                   onclick="jwt_login_remove_endpoint_row(jQuery(this));"
                   title="<?php echo __('delete', 'simple-jwt-login'); ?>"
                >
                    <i class="delete-auth-code" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</div>