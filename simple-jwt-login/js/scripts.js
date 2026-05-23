jQuery(document).ready(
    function ($) {
        $('#simple-jwt-login').css('visibility', 'visible');
        $('#sjl-page-loader').addClass('sjl-loader-hidden');
        setTimeout(function () { $('#sjl-page-loader').remove(); }, 250);

        $('#auth_codes').append($('#code_line').html());

        // Login button layout picker
        $('#simple-jwt-login .sjl-layout-option').on('click', function () {
            var $option = $(this);
            $option.closest('.sjl-layout-picker').find('.sjl-layout-option').removeClass('selected');
            $option.addClass('selected');
            $option.find('input[type="radio"]').prop('checked', true);
        });

        // Applications: icon tile catalog switching (scoped per catalog group)
        $('#simple-jwt-login .sjl-app-tile[data-app]').on('click keypress', function (e) {
            if (e.type === 'keypress' && e.which !== 13) {
                return;
            }
            var $tile    = $(this);
            var appId    = $tile.data('app');
            var isOpen   = $tile.hasClass('active');
            var $catalog = $tile.closest('.sjl-apps-catalog');
            var $body    = $catalog.closest('.sjl-apps-body');
            var $panels  = $body.find('.sjl-apps-panels');

            if (isOpen) {
                return;
            }

            $catalog.find('.sjl-app-tile').removeClass('active').attr('aria-expanded', 'false');
            $panels.find('.sjl-app-panel').hide();

            $tile.addClass('active').attr('aria-expanded', 'true');
            $panels.find('#sjl-app-panel-' + appId).show();
            $body.find('input[type="hidden"][name$="_panel"]').val(appId);
        });

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

        function sjlUpdateHooksCount() {
            var checked = $('#simple-jwt-login-tab-hooks .sjl-hooks-list input[type="checkbox"]:checked').length;
            $('#sjl-hooks-enabled-count').text(checked);
        }

        $('#simple-jwt-login #toggleHooks').on(
            'click',
            function () {
                var isChecked = $(this).is(':checked');
                var $checkboxes = $('#simple-jwt-login-tab-hooks .sjl-hooks-list input[type="checkbox"]');
                $checkboxes.prop('checked', isChecked);
                $checkboxes.each(function () {
                    $(this).closest('.sjl-hook-item').toggleClass('sjl-hook-item--enabled', isChecked);
                });
                sjlUpdateHooksCount();
            }
        );

        $(document).on(
            'change',
            '#simple-jwt-login-tab-hooks .sjl-hook-item-toggle input[type="checkbox"]',
            function () {
                var isChecked = $(this).is(':checked');
                $(this).closest('.sjl-hook-item').toggleClass('sjl-hook-item--enabled', isChecked);
                sjlUpdateHooksCount();
                var $all     = $('#simple-jwt-login-tab-hooks .sjl-hooks-list input[type="checkbox"]');
                var total    = $all.length;
                var numChecked = $all.filter(':checked').length;
                $('#toggleHooks')
                    .prop('checked', numChecked === total)
                    .prop('indeterminate', numChecked > 0 && numChecked < total);
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
            }
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

        function calculate_strength_refreshTokenKey()
        {
            var refreshTokenKey = jQuery('#refresh_token_key').val();

            if (refreshTokenKey.length === 0) {
                document.getElementById("refresh_token_progress_label").innerHTML = "0%";
                document.getElementById("refresh_token_progress").value = "0";
                return;
            }
            // Check progress
            var progress = [/[$@$!%*#?&]/, /[A-Z]/, /[0-9]/, /[a-z]/]
                .reduce((memo, test) => memo + test.test(refreshTokenKey), 0);

            // Length must be at least 8 chars
            if (progress > 2 && refreshTokenKey.length > 7) {
                progress++;
            }

            var progressPercent = "";
            switch (progress) {
                case 0:
                case 1:
                case 2:
                    progressPercent = "25";
                    break;
                case 3:
                    progressPercent = "50";
                    break;
                case 4:
                    progressPercent = "75";
                    break;
                case 5:
                    progressPercent = "100";
                    break;
            }

            document.getElementById("refresh_token_progress_label").innerHTML = progressPercent + '%';
            document.getElementById("refresh_token_progress").value = progressPercent;
        }

        // Bind refresh token key strength calculation
        jQuery('#simple-jwt-login #refresh_token_key').on('keyup', function () {
            calculate_strength_refreshTokenKey();
        });

        simple_jwt_bind_decryption_key();
        simple_jwt_bind_reset_password();
        simple_jwt_bind_protected_endpoints();

        // -----------------------------------------------------------------------
        // JWT Rules — per-row algorithm toggle and dynamic add/remove
        // -----------------------------------------------------------------------

        function sjl_rule_bind_alg($row)
        {
            var alg = $row.find('.sjl-rule-alg').val();
            if (alg && alg.indexOf('RS') !== -1) {
                $row.find('.sjl-rule-hs-fields').hide();
                $row.find('.sjl-rule-rs-fields').show();
            } else {
                $row.find('.sjl-rule-hs-fields').show();
                $row.find('.sjl-rule-rs-fields').hide();
            }
        }

        function sjl_rule_bind_condition($row)
        {
            var type = $row.find('.sjl-rule-condition-type').val() || 'payload';
            var $keyGroup = $row.find('.sjl-rule-condition-key-group');
            var $keyInput = $row.find('.sjl-rule-condition-key');

            if (type === 'iss') {
                $keyGroup.hide();
                $keyInput.val('iss');
            } else {
                $keyGroup.show();
                if ($keyInput.val() === 'iss') {
                    $keyInput.val('');
                }
            }
        }

        // Initialize algorithm and condition visibility for existing PHP-rendered rows
        $('#sjl-jwt-rules .sjl-rule-row').each(function () {
            sjl_rule_bind_alg($(this));
            sjl_rule_bind_condition($(this));
        });

        // Algorithm change on any rule row
        $(document).on('change', '#sjl-jwt-rules .sjl-rule-alg', function () {
            sjl_rule_bind_alg($(this).closest('.sjl-rule-row'));
        });

        $(document).on('change', '#sjl-jwt-rules .sjl-rule-condition-type', function () {
            sjl_rule_bind_condition($(this).closest('.sjl-rule-row'));
        });

        // Add new rule row by cloning the hidden template
        $('#simple-jwt-login #sjl-add-rule').on('click', function () {
            var $clone = $('#sjl-rule-row-template .sjl-rule-row').clone();
            $clone.removeAttr('style');
            $('#sjl-jwt-rules').append($clone);
            sjl_rule_bind_alg($clone);
            sjl_rule_bind_condition($clone);
        });

        // Remove a rule row
        $(document).on('click', '#sjl-jwt-rules .sjl-rule-remove', function () {
            $(this).closest('.sjl-rule-row').remove();
        });

        // Serialize rule rows to JSON before form submission
        $('#simple-jwt-login').closest('form').on('submit', function () {
            var rules = [];
            $('#sjl-jwt-rules .sjl-rule-row').each(function () {
                var $row = $(this);
                var alg  = $row.find('.sjl-rule-alg').val() || 'HS256';
                var conditionType = $row.find('.sjl-rule-condition-type').val() || 'payload';
                var conditionOperator = $row.find('.sjl-rule-condition-operator').val() || 'equals';
                var conditionKey = $row.find('.sjl-rule-condition-key').val() || '';
                var conditionValue = $row.find('.sjl-rule-condition-value').val() || '';
                var rule = {
                    condition_type: conditionType,
                    condition_operator: conditionOperator,
                    condition_key: conditionType === 'iss' ? 'iss' : conditionKey,
                    condition_value: conditionValue,
                    algorithm: alg
                };

                if (conditionType === 'iss') {
                    rule.iss = conditionValue;
                }

                if (alg.indexOf('RS') !== -1) {
                    rule.decryption_key_public  = $row.find('.sjl-rule-pub-key').val()  || '';
                    rule.decryption_key_private = $row.find('.sjl-rule-priv-key').val() || '';
                } else {
                    rule.decryption_key        = $row.find('.sjl-rule-key').val() || '';
                    rule.decryption_key_base64 = $row.find('.sjl-rule-key-b64').is(':checked') ? 1 : 0;
                }
                rule.login_by           = parseInt($row.find('.sjl-rule-login-by').val(), 10) || 0;
                rule.login_by_parameter = $row.find('.sjl-rule-login-by-param').val() || '';
                rules.push(rule);
            });
            $('#jwt_rules_json').val(JSON.stringify(rules));

            // Serialize webhook rows to JSON before form submission
            var webhooks = [];
            $('#sjl-webhooks .sjl-webhook-item').each(function () {
                var $row = $(this);
                var url = $row.find('.sjl-webhook-url').val() || '';
                var method = $row.find('.sjl-webhook-method').val() || 'POST';
                var enabled = $row.find('.sjl-webhook-enabled').is(':checked');
                var events = [];
                $row.find('.sjl-webhook-event:checked').each(function () {
                    events.push($(this).val());
                });
                var headers = [];
                $row.find('.sjl-webhook-header-row').each(function () {
                    var key = $(this).find('.sjl-header-key').val() || '';
                    var value = $(this).find('.sjl-header-value').val() || '';
                    if (key) {
                        headers.push({ key: key, value: value });
                    }
                });
                var payloadTemplate = $row.find('.sjl-webhook-payload-template').val() || '';
                webhooks.push({
                    url: url,
                    method: method,
                    enabled: enabled,
                    events: events,
                    headers: headers,
                    payload_template: payloadTemplate
                });
            });
            $('#webhooks_json').val(JSON.stringify(webhooks));
        });

        // TABS
        $('#simple-jwt-login-tabs a').click(function (e) {
            e.preventDefault();
            var $link = $(this);
            $('#active_tab').val($link.attr('data-index'));
            $link.tab('show');
            var section = $link.attr('data-section');
            if (section) {
                $link.one('shown.bs.tab', function () {
                    var el = document.getElementById(section);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            }
        });

        // Webhooks: add new webhook row by cloning the hidden template
        $('#simple-jwt-login #sjl-add-webhook').on('click', function () {
            var $clone = $('#sjl-webhook-row-template .sjl-webhook-item').clone();
            $('#sjl-webhooks').append($clone);
        });

        // Webhooks: remove a webhook row
        $(document).on('click', '#sjl-webhooks .sjl-webhook-remove', function () {
            $(this).closest('.sjl-webhook-item').remove();
        });

        // Webhooks: toggle accordion open/closed
        $(document).on('click', '#sjl-webhooks .sjl-webhook-toggle', function () {
            var $item = $(this).closest('.sjl-webhook-item');
            var isOpen = $item.attr('data-open') === 'true';
            $item.attr('data-open', isOpen ? 'false' : 'true');
            $(this).attr('aria-expanded', !isOpen);
            $(this).find('.dashicons')
                .toggleClass('dashicons-arrow-down-alt2', !isOpen)
                .toggleClass('dashicons-arrow-right-alt2', isOpen);
        });

        // Webhooks: update URL preview in header on input
        $(document).on('input', '#sjl-webhooks .sjl-webhook-url', function () {
            var val = $(this).val() || 'New Webhook';
            $(this).closest('.sjl-webhook-item').find('.sjl-webhook-url-preview').text(val);
        });

        // Webhooks: update method badge when select changes
        $(document).on('change', '#sjl-webhooks .sjl-webhook-method', function () {
            var method = $(this).val();
            var $item = $(this).closest('.sjl-webhook-item');
            $item.find('.sjl-method-badge')
                .text(method)
                .attr('class', 'sjl-method-badge sjl-method-' + method.toLowerCase());
        });

        // Webhooks: sync event tags in header when event checkboxes change
        $(document).on('change', '#sjl-webhooks .sjl-webhook-event', function () {
            var $item = $(this).closest('.sjl-webhook-item');
            var event = $(this).val();
            var checked = $(this).is(':checked');
            $item.find('.sjl-event-tag[data-event="' + event + '"]').toggleClass('active', checked);
            $(this).closest('.sjl-event-checkbox-label').toggleClass('active', checked);
        });

        // Webhooks: add a header row to a webhook
        $(document).on('click', '#sjl-webhooks .sjl-add-header', function () {
            var $headerRow = $('#sjl-webhook-header-row-template .sjl-webhook-header-row').clone();
            var $subsection = $(this).closest('.sjl-webhook-subsection');
            $subsection.find('.sjl-webhook-headers-rows').append($headerRow);
            var count = $subsection.find('.sjl-webhook-header-row').length;
            $subsection.find('.sjl-header-count').text(count);
        });

        // Webhooks: remove a header row
        $(document).on('click', '#sjl-webhooks .sjl-header-remove', function () {
            var $subsection = $(this).closest('.sjl-webhook-subsection');
            $(this).closest('.sjl-webhook-header-row').remove();
            var count = $subsection.find('.sjl-webhook-header-row').length;
            $subsection.find('.sjl-header-count').text(count);
        });

        // Webhooks: insert variable chip at textarea cursor
        $(document).on('click', '#sjl-webhooks .sjl-var-chip', function () {
            var varText = $(this).data('var');
            var $textarea = $(this).closest('.sjl-webhook-subsection').find('.sjl-webhook-payload-template');
            var el = $textarea[0];
            var start = el.selectionStart;
            var end = el.selectionEnd;
            el.value = el.value.substring(0, start) + varText + el.value.substring(end);
            el.selectionStart = el.selectionEnd = start + varText.length;
            $textarea.trigger('focus');
        });

        // Dashboard cards — clicking navigates to the corresponding settings tab
        $('#simple-jwt-login [data-sjl-tab]').on('click', function () {
            var tabIndex = $(this).data('sjl-tab');
            var $tab = $('#simple-jwt-login-tabs a[data-index="' + tabIndex + '"]');
            if ($tab.length) {
                $tab.trigger('click');
            }
        });

        // -----------------------------------------------------------------------
        // Sidebar group collapse / expand
        // -----------------------------------------------------------------------

        function sjlGetCollapsedGroups() {
            try {
                var raw = localStorage.getItem('sjl_nav_collapsed');
                return raw ? JSON.parse(raw) : {};
            } catch (e) {
                return {};
            }
        }

        function sjlSaveCollapsedGroups(state) {
            try {
                localStorage.setItem('sjl_nav_collapsed', JSON.stringify(state));
            } catch (e) {}
        }

        function sjlApplyGroupCollapse(groupId, collapse) {
            var $label = $('.sjl-nav-group-label[data-sjl-group="' + groupId + '"]');
            var $items = $('.sjl-nav-sub-item[data-sjl-group-item="' + groupId + '"]');

            // Never collapse a group that contains the active tab
            if (collapse && $items.find('.nav-link.active').length > 0) {
                collapse = false;
            }

            $label.toggleClass('sjl-collapsed', collapse);
            $label.find('.sjl-nav-group-toggle').attr('aria-expanded', String(!collapse));
            $items.toggleClass('sjl-group-hidden', collapse);
        }

        // Restore persisted state on load
        var sjlCollapsed = sjlGetCollapsedGroups();
        $('.sjl-nav-group-label[data-sjl-group]').each(function () {
            var groupId = $(this).data('sjl-group');
            sjlApplyGroupCollapse(groupId, sjlCollapsed[groupId] === true);
        });

        // Toggle on label click
        $(document).on('click', '.sjl-nav-group-label[data-sjl-group]', function () {
            var groupId  = $(this).data('sjl-group');
            var collapse = !$(this).hasClass('sjl-collapsed');
            sjlApplyGroupCollapse(groupId, collapse);
            var state = sjlGetCollapsedGroups();
            state[groupId] = collapse;
            sjlSaveCollapsedGroups(state);
        });

        // -----------------------------------------------------------------------
        // Try Now: inject Try buttons into every generated-code block
        // -----------------------------------------------------------------------

        $('#simple-jwt-login .generated-code').each(function () {
            jQuery(this).find('.copy-button').after(
                '<span class="sjl-try-btn-wrap">'
                + '<button class="btn sjl-btn-plugin sjl-try-btn" type="button">Try</button>'
                + '</span>'
            );
        });

        $(document).on('click', '#simple-jwt-login .sjl-try-btn', function (e) {
            e.preventDefault();
            var $block    = jQuery(this).closest('.generated-code');
            var $existing = $block.next('.sjl-try-panel');

            if ($existing.length) {
                $existing.remove();
                return;
            }

            var url    = sjlTryCleanUrl($block);
            var method = sjlTryGetMethod($block);
            var params = sjlTryParseParams(url);
            var base   = sjlTryBaseUrl(url);
            var keys   = Object.keys(params);
            var isBody = (method === 'POST' || method === 'PUT');

            var paramsHtml = '';
            if (keys.length) {
                paramsHtml += '<div class="sjl-try-params"><p class="sjl-try-section-label">Parameters</p>';
                keys.forEach(function (key) {
                    var safeKey  = jQuery('<span>').text(key).html();
                    var safeHint = jQuery('<span>').text(params[key]).html();
                    paramsHtml  += '<div class="sjl-try-param-row">'
                        + '<label class="sjl-try-param-label">' + safeKey + '</label>'
                        + '<input type="text" class="form-control sjl-try-param-input"'
                        + ' data-param="' + safeKey + '" placeholder="' + safeHint + '" />'
                        + '</div>';
                });
                paramsHtml += '</div>';
            }

            var $panel = jQuery(
                '<div class="sjl-try-panel">'
                + '<div class="sjl-try-panel-header">'
                + '<span class="sjl-try-panel-title">Try it out</span>'
                + '<button class="sjl-try-close" type="button" title="Close">&times;</button>'
                + '</div>'
                + paramsHtml
                + '<div class="sjl-try-url-preview">'
                + '<p class="sjl-try-section-label">Request URL</p>'
                + '<code class="sjl-try-url-code"></code>'
                + '</div>'
                + '<div class="sjl-try-actions">'
                + '<button class="btn sjl-btn-plugin sjl-try-send-btn" type="button">Send Request</button>'
                + '</div>'
                + '<div class="sjl-try-response" style="display:none">'
                + '<p class="sjl-try-section-label">Response</p>'
                + '<div class="sjl-try-response-status"></div>'
                + '<pre class="sjl-try-response-body"></pre>'
                + '</div>'
                + '</div>'
            );

            $panel.data({ base: base, method: method, isBody: isBody, defaultParams: params });
            $panel.find('.sjl-try-url-code').text(isBody ? base : sjlTryBuildUrl(base, params));

            $panel.on('input', '.sjl-try-param-input', function () {
                if (!isBody) {
                    var cur = sjlTryCollectParams($panel);
                    $panel.find('.sjl-try-url-code').text(sjlTryBuildUrl(base, cur));
                }
            });

            $panel.on('click', '.sjl-try-close', function () { $panel.remove(); });

            $panel.on('click', '.sjl-try-send-btn', function () {
                var cur      = sjlTryCollectParams($panel);
                var fetchUrl = isBody ? base : sjlTryBuildUrl(base, cur);
                var fetchOpts = isBody
                    ? { method: method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(cur) }
                    : { method: method };

                var $btn     = jQuery(this);
                var $resp    = $panel.find('.sjl-try-response');
                var $status  = $panel.find('.sjl-try-response-status');
                var $body    = $panel.find('.sjl-try-response-body');

                $btn.prop('disabled', true).text('Sending...');
                $resp.show();
                $status.text('').removeClass('sjl-try-status--ok sjl-try-status--err');
                $body.text('Loading...');

                fetch(fetchUrl, fetchOpts)
                    .then(function (res) {
                        var st = res.status, stText = res.statusText;
                        return res.text().then(function (t) { return { status: st, statusText: stText, body: t }; });
                    })
                    .then(function (data) {
                        var ok = data.status >= 200 && data.status < 300;
                        $status.text('Status: ' + data.status + ' ' + data.statusText)
                            .addClass(ok ? 'sjl-try-status--ok' : 'sjl-try-status--err');
                        var formatted;
                        try { formatted = JSON.stringify(JSON.parse(data.body), null, 2); } catch (ex) { formatted = data.body; }
                        $body.text(formatted);
                        $btn.prop('disabled', false).text('Send Request');
                    })
                    .catch(function (err) {
                        $status.text('Network error: ' + err.message).addClass('sjl-try-status--err');
                        $body.text('');
                        $btn.prop('disabled', false).text('Send Request');
                    });
            });

            $block.after($panel);
        });
    }(jQuery)
);

/* ── Try Now helpers ─────────────────────────────────────────────────────── */

function sjlTryParseParams(url)
{
    var params  = {};
    var qIndex  = url.indexOf('?');
    if (qIndex === -1) { return params; }
    var qs = url.slice(qIndex + 1);
    qs.split('&').forEach(function (pair) {
        var eq  = pair.indexOf('=');
        if (eq === -1) { return; }
        var key = decodeURIComponent(pair.slice(0, eq));
        var val = decodeURIComponent(pair.slice(eq + 1));
        params[key] = val;
    });
    return params;
}

function sjlTryBaseUrl(url)
{
    var q = url.indexOf('?');
    return q === -1 ? url : url.slice(0, q);
}

function sjlTryBuildUrl(base, params)
{
    var keys = Object.keys(params);
    if (!keys.length) { return base; }
    var qs = keys.map(function (k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
    }).join('&');
    return base + '?' + qs;
}

function sjlTryCleanUrl($block)
{
    var raw = $block.find('.code').html() || '';
    return raw.trim()
        .replace(/&amp;/g, '&')
        .replace(/<b>|<\/b>/g, '')
        .replace(/\s+/g, '');
}

function sjlTryGetMethod($block)
{
    return $block.find('.method').first().text().trim().toUpperCase();
}

function sjlTryCollectParams($panel)
{
    var out = {};
    $panel.find('.sjl-try-param-input').each(function () {
        var k   = jQuery(this).data('param');
        var val = jQuery(this).val();
        out[k]  = val;
    });
    return out;
}

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

function showRefreshTokenKey()
{
    var elementType = 'text';
    if (jQuery('#refresh_token_key').attr('type') === 'text') {
        jQuery('#refresh_token_key_container .toggle-image').removeClass('toggle_visible');
        elementType = 'password';
    } else {
        jQuery('#refresh_token_key_container .toggle-image').addClass('toggle_visible');
    }

    jQuery('#refresh_token_key').attr('type', elementType);
}

function generateRefreshTokenKey()
{
    var array = new Uint8Array(32);
    window.crypto.getRandomValues(array);
    var hex = Array.from(array, function (byte) {
        return ('0' + byte.toString(16)).slice(-2);
    }).join('');
    jQuery('#refresh_token_key').val(hex).attr('type', 'text').trigger('keyup');
    jQuery('#refresh_token_key_container .toggle-image').addClass('toggle_visible');

    var $msg = jQuery('#refresh_token_generated_msg');
    $msg.text('New key generated!').addClass('sjl-gen-generated-msg--visible');
    clearTimeout(window._sjlGenMsgTimer);
    window._sjlGenMsgTimer = setTimeout(function () {
        $msg.removeClass('sjl-gen-generated-msg--visible');
    }, 2500);
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

/* ── API Key management ─────────────────────────────────────────────────── */

function sjlCreateApiKey()
{
    var cfg  = window._sjlAkConfig;
    var name = jQuery('#sjl-ak-name').val().trim();
    var expires = jQuery('#sjl-ak-expires').val();
    var perms = [];
    jQuery('.sjl-ak-perm-check:checked').each(function () { perms.push(jQuery(this).val()); });
    var msg = jQuery('#sjl-ak-create-msg');
    msg.text('').removeClass('sjl-ak-msg--error sjl-ak-msg--ok');

    if (!name) { msg.text('Name is required.').addClass('sjl-ak-msg--error'); return; }
    if (!perms.length) { msg.text('Select at least one permission.').addClass('sjl-ak-msg--error'); return; }

    jQuery.ajax({
        url: cfg.restBase,
        method: 'POST',
        contentType: 'application/json',
        beforeSend: function (xhr) { xhr.setRequestHeader('X-WP-Nonce', cfg.nonce); },
        data: JSON.stringify({ name: name, permissions: perms, expires_at: expires || null }),
        success: function (res) {
            if (res.data && res.data.key) {
                jQuery('#sjl-ak-raw-key').text(res.data.key);
                jQuery('#sjl-ak-modal').show();
            }
        },
        error: function (xhr) {
            var errMsg = 'Failed to create API key.';
            try { errMsg = JSON.parse(xhr.responseText).message || errMsg; } catch (e) {}
            msg.text(errMsg).addClass('sjl-ak-msg--error');
        }
    });
}

function sjlRevokeApiKey(id)
{
    if (!window.confirm('Revoke this API key? This cannot be undone.')) { return; }
    var cfg = window._sjlAkConfig;
    jQuery.ajax({
        url: cfg.restBase + '/' + id,
        method: 'DELETE',
        beforeSend: function (xhr) { xhr.setRequestHeader('X-WP-Nonce', cfg.nonce); },
        success: function () { window.location.reload(); },
        error: function () { alert('Failed to revoke API key.'); }
    });
}

function sjlDeleteApiKey(id)
{
    if (!window.confirm('Permanently delete this API key? This cannot be undone.')) { return; }
    var cfg = window._sjlAkConfig;
    jQuery.ajax({
        url: cfg.restBaseSingle + '/' + id + '/delete',
        method: 'DELETE',
        beforeSend: function (xhr) { xhr.setRequestHeader('X-WP-Nonce', cfg.nonce); },
        success: function () { window.location.reload(); },
        error: function () { alert('Failed to delete API key.'); }
    });
}

function sjlCopyApiKey()
{
    var key = jQuery('#sjl-ak-raw-key').text();
    if (!key) { return; }
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(key).then(function () {
            jQuery('#sjl-ak-copy-msg').text('Copied!');
        });
    } else {
        var ta = document.createElement('textarea');
        ta.value = key;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        jQuery('#sjl-ak-copy-msg').text('Copied!');
    }
}

function sjlCloseApiKeyModal()
{
    jQuery('#sjl-ak-modal').hide();
    jQuery('#sjl-ak-copy-msg').text('');
    window.location.reload();
}
