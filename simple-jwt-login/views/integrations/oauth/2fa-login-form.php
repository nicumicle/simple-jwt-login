<?php

if (! defined('ABSPATH')) {
    /**
     * @phpstan-ignore-next-line
     */
    exit;
}

/**
 * Variables provided by OAuthTwoFactorLoginHandler::renderForm():
 *
 * @var string $jwt        Signed interim JWT carrying the 2FA state.
 * @var string $redirectTo URL to redirect to after successful verification.
 * @var string $error      Validation error message (empty when none).
 */

$formAction = add_query_arg(
    'action',
    esc_attr(SimpleJWTLogin\Services\Oauth\AbstractOauth::BROWSER_2FA_ACTION),
    wp_login_url()
);
?>

<?php if (!empty($error)) : ?>
<div id="login_error"><?php echo esc_html($error); ?></div>
<?php endif; ?>

<form name="loginform" id="loginform"
      action="<?php echo esc_url($formAction); ?>"
      method="POST">

    <input type="hidden" name="sjl_jwt"     value="<?php echo esc_attr($jwt); ?>" />
    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirectTo); ?>" />

    <p>
        <label for="sjl-2fa-code">
            <?php echo esc_html__('Two-Factor Code', 'simple-jwt-login'); ?>
        </label>
        <input type="text"
               name="sjl_code"
               id="sjl-2fa-code"
               class="input"
               size="20"
               autocomplete="one-time-code"
               inputmode="numeric"
               autofocus
               required />
    </p>

    <p class="submit">
        <input type="submit"
               name="wp-submit"
               id="wp-submit"
               class="button button-primary button-large"
               value="<?php echo esc_attr__('Verify', 'simple-jwt-login'); ?>" />
    </p>
</form>
