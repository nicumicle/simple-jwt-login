<?php

use SimpleJWTLogin\Modules\RouteService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<div class="row">
	<div class="col-md-12">
		<h3 class="section-title"><?php echo __( 'Allow CORS', 'simple-jwt-login' ); ?></h3>
		<div class="form-group">
			<input type="radio" id="allow_cors_no" name="cors[enabled]" class="form-control"
			       value="0"
				<?php echo( $jwtSettings->getCors()->isCorsEnabled() === false ? 'checked' : '' ); ?>
			/>
			<label for="allow_cors_no">
				<?php echo __( 'No', 'simple-jwt-login' ); ?>
			</label>

			<input type="radio" id="allow_cors_yes" name="cors[enabled]" class="form-control"
			       value="1" <?php echo( $jwtSettings->getCors()->isCorsEnabled()  === true ? 'checked' : '' ); ?> />
			<label for="allow_cors_yes">
				<?php echo __( 'Yes', 'simple-jwt-login' ); ?>
			</label>
		</div>
	</div>
</div>
<hr/>

<div class="row">
	<div class="col-md-12">
		<h3 class="section-title"><?php echo __('CORS Headers', 'simple-jwt-login');?></h3>
	</div>
</div>

<div class="row mb-4">
	<div class="col-md-4">
		<input type="checkbox" name="cors[allow_origin_enabled]" value="1" <?php echo $jwtSettings->getCors()->isAllowOriginEnabled() ? 'checked' : ''?> />
		<b>Access-Control-Allow-Origin</b>
	</div>
	<div class="col-md-8">
		<input type="text" class="form-control" name="cors[allow_origin]" value="<?php echo $jwtSettings->getCors()->getAllowOrigin();?>" />
        <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin" target="_blank">
            <?php echo __('Read more', 'simple-jwt-login'); ?>
        </a>

	</div>
</div>

<div class="row mb-4">
	<div class="col-md-4">
		<input type="checkbox" name="cors[allow_methods_enabled]" value="1"  <?php echo $jwtSettings->getCors()->isAllowMethodsEnabled() ? 'checked' : ''?> />
		<b>Access-Control-Allow-Methods</b>
	</div>
	<div class="col-md-8">
		<input type="text" class="form-control" name="cors[allow_methods]" value="<?php echo $jwtSettings->getCors()->getAllowMethods();?>" />
        <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods" target="_blank">
            <?php echo __('Read more', 'simple-jwt-login');?>
        </a>
	</div>
</div>

<div class="row mb-4">
	<div class="col-md-4">
		<input type="checkbox" name="cors[allow_headers_enabled]" value="1"  <?php echo $jwtSettings->getCors()->isAllowHeadersEnabled() ? 'checked' : ''?> />
		<b>Access-Control-Allow-Headers</b>
	</div>
	<div class="col-md-8">
		<input type="text" class="form-control" name="cors[allow_headers]" value="<?php echo $jwtSettings->getCors()->getAllowHeaders();?>" />
        <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers" target="_blank">
            <?php echo __('Read more', 'simple-jwt-login');?>
        </a>
	</div>
</div>



