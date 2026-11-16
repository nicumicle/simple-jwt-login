<?php

if (! defined('ABSPATH')) {
    /**
    @phpstan-ignore-next-line
     */
    exit;
}

/**
 * Hidden fields of an OAuth login button form (see AbstractOauth::getLoginFormTarget()).
 *
 * @var array{action: string, fields: array} $loginForm
 */
?>
<?php foreach ($loginForm['fields'] as $fieldName => $fieldValue) : ?>
    <input type="hidden" name="<?php echo esc_attr($fieldName); ?>" value="<?php echo esc_attr($fieldValue); ?>" />
<?php endforeach; ?>
