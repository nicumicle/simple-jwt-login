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

?>
<div class="row">
    <div class="col-md-12">
        <h3 class="section-title"><?php echo esc_html__('WordPress Hooks Integration', 'simple-jwt-login'); ?></h3>
        <p class="text-muted">
            <?php echo esc_html__('Enable specific WordPress hooks to extend JWT functionality. Only enabled hooks will be triggered.', 'simple-jwt-login'); ?>
        </p>
        <p>
            <?php
            echo esc_html__(
                'Make sure that the hook you are trying to use is enabled. Otherwise, the hook will not be called.',
                'simple-jwt-login'
            );
            ?>
        </p>
        <table class="table table-bordered">
            <thead class="thead-dark">
            <tr>
                <th scope="col">
                    <input type="checkbox" id="toggleHooks" />
                    <label for="toggleHooks">
                        <?php echo esc_html__('Enabled', 'simple-jwt-login'); ?>
                    </label>
                </th>
                <th scope="col"><?php echo esc_html__('Hook Name', 'simple-jwt-login'); ?></th>
                <th scope="col"><?php echo esc_html__('Hook Type', 'simple-jwt-login'); ?></th>
                <th scope="col"><?php echo esc_html__('Parameters', 'simple-jwt-login'); ?></th>
                <th scope="col"><?php echo esc_html__('Return', 'simple-jwt-login'); ?></th>
                <th scope="col"><?php echo esc_html__('Description', 'simple-jwt-login'); ?></th>
            </tr>
            </thead>
			<?php
            if (! empty($hooks)) {
                foreach ($hooks as $singleHook) {
                    ?>
                    <tr>
                        <td>
                            <input
                                    type="checkbox"
                                    name="enabled_hooks[]"
                                    id="hook_<?php echo esc_attr($singleHook['name']); ?>"
                                    value="<?php echo esc_attr($singleHook['name']); ?>"
                                    <?php echo $jwtSettings->getHooksSettings()->isHookEnable($singleHook['name'])
                                        ? 'checked'
                                        : ''
                                    ?>
                            />
                        </td>
                        <td>
                            <label for="hook_<?php echo esc_attr($singleHook['name']); ?>">
                                <?php echo esc_attr($singleHook['name']); ?>
                            </label>
                        </td>
                        <td><?php echo esc_html($singleHook['type']); ?></td>
                        <td><?php
                        if (! empty($singleHook['parameters'])) {
                            echo esc_html(implode(', ', $singleHook['parameters']));
                        } ?>
                        </td>
                        <td>
                            <?php
                            if (isset($singleHook['return'])) {
                                echo esc_html($singleHook['return']);
                            } else {
                                echo "void";
                            } ?>
                        </td>
                        <td>
                            <p><?php echo nl2br(esc_html($singleHook['description']), false); ?></p>
                        </td>
                    </tr>
					<?php
                }
            }
            ?>

        </table>
    </div>
</div>

