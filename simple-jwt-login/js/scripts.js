jQuery(document).ready(
    function ($) {
        $('#auth_codes').append($('#code_line').html());

        $('#simple-jwt-login #add_code').click(
            function () {
                $('#auth_codes').append($('#code_line').html());
            }
        );

        $('#simple-jwt-login #add_whitelist_endpoint').click(
            function () {
                $('#whitelisted-domains').append($('#endpoint_whitelist_line').html());
            }
        );

        $('#simple-jwt-login #add_protect_endpoint').click(
            function () {
                $('#protected-domains').append($('#endpoint_protect_line').html());
            }
        );

        $('#simple-jwt-login input[name="jwt_reset_password_flow"]').on(
            'change',
            function () {
                simple_jwt_bind_reset_password();
            }
        )

        $('#simple-jwt-login #protection_type').on(
            'change',
            function () {
                simple_jwt_bind_protected_endpoints();
            }
        )

        $('#simple-jwt-login input[name="redirect"]').on(
            'change',
            function () {
                if (~~$(this).val() === 9) {
                    $('#simple-jwt-login #redirect_url').show();
                } else {
                    $('#simple-jwt-login #redirect_url').hide();
                }
            }
        );

        $('#simple-jwt-login input[name="require_register_auth"]').on(
            'change',
            function () {
                if (~~$(this).val() === 0) {
                    $('#simple-jwt-login #require_register_auth_alert').show();
                } else {
                    $('#simple-jwt-login #require_register_auth_alert').hide();
                }
            }
        );

        $('#simple-jwt-login input[name="require_delete_auth"]').on(
            'change',
            function () {
                if (~~$(this).val() === 0) {
                    $('#simple-jwt-login #require_delete_auth_alert').show();
                } else {
                    $('#simple-jwt-login #require_delete_auth_alert').hide();
                }
            }
        );

        $('#simple-jwt-login .generated-code .btn').on(
            'click',
            function (e) {
                e.preventDefault();
                var simpleJWTCopyText     = $(this).closest('.generated-code').find('.code').html();
                simpleJWTCopyText         = simpleJWTCopyText.trim().replace(/&amp;/gmi, '&');
                simpleJWTCopyText         = simpleJWTCopyText.replace(/<b>|<\/b>| /gmi, '');
                var tempJWTLoginCopyInput = $("<input >");
                $("body").append(tempJWTLoginCopyInput);
                tempJWTLoginCopyInput.val(simpleJWTCopyText, '&').select();
                document.execCommand("copy");
                tempJWTLoginCopyInput.remove();
                tempJWTLoginCopyInput = null;
            }
        );

        $('#simple-jwt-login #toggleHooks').on(
            'click',
            function (e) {
                var isChecked = $(this).is(':checked');
                $('#simple-jwt-login-tab-hooks tbody input[type="checkbox"]').attr('checked', isChecked);
            }
        );

        $('#simple-jwt-login #decryption_key').on(
            'keyup',
            function () {
                calculate_strength_decryptionKey();
            }
        )

        $('#simple-jwt-login #simple-jwt-login-jwt-algorithm, #simple-jwt-login #decryption_source').on(
            'change',
            function (e) {
                simple_jwt_bind_decryption_key();
            }
        );

        function simple_jwt_bind_decryption_key()
        {
            var jwt_alg_val    = jQuery('#simple-jwt-login #simple-jwt-login-jwt-algorithm').val();
            var jwt_alg_source = jQuery('#simple-jwt-login #decryption_source').val();
            if (jwt_alg_source === '0') {
                $('#simple-jwt-login .decryption-code-info').hide();
                if (jwt_alg_val.indexOf('RS') !== -1
                ) {
                    $('#simple-jwt-login .decryption-input-group').hide();
                    $('#simple-jwt-login .decryption-textarea-group').show();
                } else {
                    $('#simple-jwt-login .decryption-input-group').show();
                    $('#simple-jwt-login .decryption-textarea-group').hide();
                }
                calculate_strength_decryptionKey();
            } else {
                $('#simple-jwt-login .decryption-input-group').hide();
                $('#simple-jwt-login .decryption-textarea-group').hide();
                $('#simple-jwt-login .decryption-code-info').show();
                if (jwt_alg_val.indexOf('RS') !== -1) {
                    $('#simple-jwt-login .define_public_key').show();
                } else {
                    $('#simple-jwt-login .define_public_key').hide();
                }
            }//end if
        }

        function simple_jwt_bind_protected_endpoints()
        {
            var protection_mode = jQuery('#simple-jwt-login #protection_type').val();
            if (protection_mode === '2') {
                $('#simple-jwt-login #protected_endpoints_protected').show();
                $('#simple-jwt-login #protected_endpoints_whitelisted').hide();
            } else {
                $('#simple-jwt-login #protected_endpoints_protected').hide();
                $('#simple-jwt-login #protected_endpoints_whitelisted').show();
            }
        }

        function calculate_strength_decryptionKey()
        {
            var simplejwt_decryptionKey = jQuery('#decryption_key').val();
            var simplejwt_is_decryption_base64 = jQuery('#decryption_key_base64').is(':checked');

            if (simplejwt_is_decryption_base64) {
                var simpleJWTLoginBase64regex = /^([0-9a-zA-Z+/]{4})*(([0-9a-zA-Z+/]{2}==)|([0-9a-zA-Z+/]{3}=))?$/;
                if (simpleJWTLoginBase64regex.test((simplejwt_decryptionKey))) {
                    simplejwt_decryptionKey = atob(simplejwt_decryptionKey);
                }
            }

            if (simplejwt_decryptionKey.length === 0) {
                document.getElementById("decryption_progress_label").innerHTML = "0%";
                document.getElementById("decryption_progress").value = "0";
                return;
            }
            // Check progress
            var simplejwt_progress = [/[$@$!%*#?&]/, /[A-Z]/, /[0-9]/, /[a-z]/]
                .reduce((memo, test) => memo + test.test(simplejwt_decryptionKey), 0);

            // Length must be at least 8 chars
            if (simplejwt_progress > 2 && simplejwt_decryptionKey.length > 7) {
                simplejwt_progress++;
            }

            var simple_jwt_login_progress_percent = "";
            switch (simplejwt_progress) {
                case 0:
                case 1:
                case 2:
                    simple_jwt_login_progress_percent = "25";
                    break;
                case 3:
                    simple_jwt_login_progress_percent = "50";
                    break;
                case 4:
                    simple_jwt_login_progress_percent = "75";
                    break;
                case 5:
                    simple_jwt_login_progress_percent = "100";
                    break;
            }

            document.getElementById("decryption_progress_label").innerHTML = simple_jwt_login_progress_percent + '%';
            document.getElementById("decryption_progress").value = simple_jwt_login_progress_percent;
        }

        simple_jwt_bind_decryption_key();
        simple_jwt_bind_reset_password();
        simple_jwt_bind_protected_endpoints();

        // TABS
        $('#simple-jwt-login-tabs a').click(function (e) {
            e.preventDefault();
            $('#active_tab').val($(this).attr('data-index'));
            $(this).tab('show');
        });
    }(jQuery)
);

function jwt_login_remove_auth_line(a_element)
{
    jQuery(a_element).closest('.auth_row').remove();

}

function jwt_login_remove_endpoint_row(a_element)
{
    jQuery(a_element).closest('.endpoint_row').remove();
}

function showDecryptionKey()
{
    var elementType = 'text';
    if (jQuery('#decryption_key').attr('type') === 'text') {
        jQuery('#decryption_key_container .toggle-image').removeClass('toggle_visible');
        elementType = 'password';
    } else {
        jQuery('#decryption_key_container .toggle-image').addClass('toggle_visible');
    }

    jQuery('#decryption_key').attr('type', elementType);

}

function simple_jwt_bind_reset_password()
{
    var jwt_reset_email_value = jQuery('#simple-jwt-login #jwt_reset_password_flow_custom').is(':checked');
    if (~~jwt_reset_email_value) {
        jQuery('#simple-jwt-login #simple_jwt_reset_password_email_container').show();
    } else {
        jQuery('#simple-jwt-login #simple_jwt_reset_password_email_container').hide();
    }
}
