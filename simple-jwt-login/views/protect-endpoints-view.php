<?php

use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\Settings\SettingsErrors;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if (!defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/**
 * Helper function for drawing protect endpoint line
 * @param string $type
 * @param ?array<string,mixed> $endpoint
 * @return void
 * @throws Exception
 */
function simple_jwt_login_draw_endpoin_row($type, $endpoint)
{
    ?>
    <div class="form-group endpoint_row">
        <div class="input-group">
            <select
                name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP . "[" . $type . "_method][]");?>"
                >
                <option
                    value="<?php echo esc_attr(ProtectEndpointSettings::REQUEST_METHOD_ALL);?>"
                    <?php echo (!empty($endpoint) && $endpoint['method'] == ProtectEndpointSettings::REQUEST_METHOD_ALL ? 'selected' : '');?>
                >
                    <?php echo __("ALL", "simple-jwt-login");?>
                </option>
                <optgroup label="<?php echo __('HTTP Methods', 'simple-jwt-login');?>">
                    <option
                        value="<?php echo esc_attr(ProtectEndpointSettings::REQUEST_METHOD_GET);?>"
                        <?php echo (!empty($endpoint) && $endpoint['method'] == ProtectEndpointSettings::REQUEST_METHOD_GET ? 'selected' : '');?>
                    >
                        <?php echo __("GET", "simple-jwt-login");?>
                    </option>
                    <option
                        value="<?php echo esc_attr(ProtectEndpointSettings::REQUEST_METHOD_POST);?>"
                        <?php echo (!empty($endpoint) && $endpoint['method'] == ProtectEndpointSettings::REQUEST_METHOD_POST ? 'selected' : '');?>
                    >
                        <?php echo __("POST", "simple-jwt-login");?>
                    </option>
                    <option
                        value="<?php echo esc_attr(ProtectEndpointSettings::REQUEST_METHOD_PUT);?>""
                        <?php echo (!empty($endpoint) && $endpoint['method'] == ProtectEndpointSettings::REQUEST_METHOD_PUT ? 'selected' : '');?>
                    >
                        <?php echo __("PUT", "simple-jwt-login");?>
                    </option>
                    <option
                        value="<?php echo esc_attr(ProtectEndpointSettings::REQUEST_METHOD_PATCH);?>"
                        <?php echo (!empty($endpoint) && $endpoint['method'] == ProtectEndpointSettings::REQUEST_METHOD_PATCH ? 'selected' : '');?>
                    >
                        <?php echo __("PATCH", "simple-jwt-login");?>
                    </option>
                    <option
                        value="<?php echo esc_attr(ProtectEndpointSettings::REQUEST_METHOD_DELETE);?>"
                        <?php echo (!empty($endpoint) && $endpoint['method'] == ProtectEndpointSettings::REQUEST_METHOD_DELETE ? 'selected' : '');?>
                    >
                        <?php echo __("DELETE", "simple-jwt-login");?>
                    </option>
                </optgroup>
            </select>
            <input type="text"
                   name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP . "[" . $type . "][]");?>"
                   class="form-control"
                   value="<?php echo !empty($endpoint) ? esc_attr($endpoint['url']) : ""; ?>"
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

/**
 * @var SettingsErrors $settingsErrors
 * @var SimpleJWTLoginSettings $jwtSettings
 */
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __('Protect endpoints enabled', 'simple-jwt-login'); ?></h3>
        <div class="form-group">
            <input type="radio"
                   id="protect_endpoints_enabled_no"
                   name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[enabled]"
                   class="form-control"
                   value="0"
                <?php echo $jwtSettings->getProtectEndpointsSettings()->isEnabled() === false
                    ? esc_html('checked')
                    : esc_html('');
                ?>
            />
            <label for="protect_endpoints_enabled_no">
                <?php echo __('No', 'simple-jwt-login'); ?>
            </label>

            <input
                    type="radio"
                    id="protect_endpoints_enabled_yes"
                    name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[enabled]"
                    class="form-control"
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
                <?php
                echo __(
                    'The endpoints will require a JWT in order to be accessed.'
                    . ' If no JWT is provided, rest endpoints will provide an error instead of the actual content.',
                    'simple-jwt-login'
                );
                ?>
            </p>
        </div>
    </div>
</div>
<hr/>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <h2 class="section-title">Action</h2>
            <select
                    id="protection_type"
                    name="<?php echo esc_attr(ProtectEndpointSettings::PROPERTY_GROUP);?>[action]"
                    class="form-control"
            >
                <option
                        value="<?php echo esc_attr(ProtectEndpointSettings::ALL_ENDPOINTS);?>"
                    <?php
                    echo $jwtSettings->getProtectEndpointsSettings()->getAction()
                    === ProtectEndpointSettings::ALL_ENDPOINTS
                        ? esc_html('selected')
                        : esc_html('')
                    ?>
                >
                    <?php echo __('Apply on All REST Endpoints', 'simple-jwt-login');?>
                </option>
                <option
                        value="<?php echo esc_attr(ProtectEndpointSettings::SPECIFIC_ENDPOINTS);?>"
                    <?php echo $jwtSettings->getProtectEndpointsSettings()->getAction()
                    === ProtectEndpointSettings::SPECIFIC_ENDPOINTS
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
        <input
                type="button"
                class="btn btn-dark"
                value="<?php echo __('Add Endpoint', 'simple-jwt-login');?> +"
                id="add_whitelist_endpoint"
        />
    </div>
    <div class="col-md-12">
        <div id="whitelisted-domains">
            <?php
            foreach ($jwtSettings->getProtectEndpointsSettings()->getWhitelistedDomains() as $endpoint) {
                simple_jwt_login_draw_endpoin_row("whitelist", $endpoint);
            }?>
        </div>
    </div>
</div>

<div class="row" id="protected_endpoints_protected">
    <div class="col-md-12">
        <h2 class="section-title">
            <?php echo __('Protected endpoints', 'simple-jwt-login');?>
        </h2>
        <p class="text-muted">
            <?php echo __('The JWT will be required on the following endpoints.', 'simple-jwt-login'); ?>
        </p>
    </div>
    <div class="col-md-12">
        <input
                type="button"
                class="btn btn-dark"
                value="<?php echo __('Add Endpoint', 'simple-jwt-login');?> +"
                id="add_protect_endpoint"
        />
    </div>
    <div class="col-md-12">
        <div id="protected-domains">
            <?php
            foreach ($jwtSettings->getProtectEndpointsSettings()->getProtectedEndpoints() as $endpoint) {
                simple_jwt_login_draw_endpoin_row('protect', $endpoint);
            }
            ?>
        </div>
    </div>
</div>
<hr/>

<?php
    // Empty endpoint lines used by JS for inserting new rows
?>
<div id="endpoint_whitelist_line" style="display: none;">
    <?php simple_jwt_login_draw_endpoin_row('whitelist', null);?>
</div>

<div id="endpoint_protect_line" style="display: none;">
   <?php simple_jwt_login_draw_endpoin_row('protect', null);?>
</div>