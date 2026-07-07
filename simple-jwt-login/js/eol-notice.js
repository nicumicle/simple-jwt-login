jQuery(document).ready(
    function ($) {
        $('.simple-jwt-login-eol-notice').on(
            'click',
            '.notice-dismiss',
            function () {
                $.post(
                    simpleJwtLoginEol.ajaxUrl,
                    {
                        action: simpleJwtLoginEol.action,
                        nonce: $('.simple-jwt-login-eol-notice').data('nonce')
                    }
                );
            }
        );
    }
);
