<?php

use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$hooks = SimpleJWTLoginHooks::getHooksDetails();
?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo __( 'Hooks', 'simple-jwt-login' ); ?></h3>
        <p>
            <?php echo __('Make sure that the hook you are trying to use is enabled. Otherwise, the hook will not be called.','simple-jwt-login');?>
        </p>
        <table class="table table-bordered">
            <thead class="thead-dark">
            <tr>
                <th scope="col">
                    <input type="checkbox" id="toggleHooks" />
                    <?php echo __('Enabled', 'simple-jwt-login'); ?>
                </th>
                <th scope="col"><?php echo __( 'Hook Name', 'simple-jwt-login' ); ?></th>
                <th scope="col"><?php echo __( 'Hook Type', 'simple-jwt-login' ); ?></th>
                <th scope="col"><?php echo __( 'Parameters', 'simple-jwt-login' ); ?></th>
                <th scope="col"><?php echo __( 'Return', 'simple-jwt-login' ); ?></th>
                <th scope="col"><?php echo __( 'Description', 'simple-jwt-login' ); ?></th>
            </tr>
            </thead>
			<?php
			if ( ! empty( $hooks ) ) {
				foreach ( $hooks as $singleHook ) {
					?>
                    <tr>
                        <td>
                            <input
                                    type="checkbox"
                                    name="enabled_hooks[]"
                                    id="hook_<?php echo $singleHook['name'];?>"
                                    value="<?php echo $singleHook['name'];?>"
                                    <?php echo $jwtSettings->isHookEnable($singleHook['name']) ? 'checked' : '' ?>
                            />
                        </td>
                        <td>
                            <label for="hook_<?php echo $singleHook['name'];?>">
                                <?php echo $singleHook['name']; ?>
                            </label>
                        </td>
                        <td><?php echo $singleHook['type']; ?></td>
                        <td><?php
							if ( ! empty( $singleHook['parameters'] ) ) {
								echo implode( ', ', $singleHook['parameters'] );
							}
							?>
                        </td>
                        <td>
                            <?php
                                if(isset($singleHook['return'])){
                                    echo $singleHook['return'];
                                } else {
                                    echo "void";
                                }
                            ?>
                        </td>
                        <td>
                            <p><?php echo str_replace( "\n", "<br />", $singleHook['description'] ); ?></p>
                        </td>
                    </tr>
					<?php
				}
			}
			?>

        </table>
    </div>
</div>

