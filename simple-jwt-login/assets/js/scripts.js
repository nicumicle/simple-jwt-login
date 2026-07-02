jQuery(document).ready(
    function ($) {
        // Apply theme before revealing the page to avoid a flash.
        // Priority: DB value (via _sjlConfig) > localStorage > system preference.
        var sjlConfig     = window._sjlConfig || {};
        var sjlDbTheme    = sjlConfig.theme || '';
        var sjlLocalTheme = localStorage.getItem('sjl-theme') || '';
        var sjlTheme      = sjlDbTheme || sjlLocalTheme;
        var sjlPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var sjlIsDark     = sjlTheme === 'dark' || (!sjlTheme && sjlPrefersDark);
        var $plugin       = $('#simple-jwt-login');

        if (sjlIsDark) {
            $plugin.attr('data-sjl-theme', 'dark');
            $('#wpwrap').addClass('sjl-wp-dark');
            $('#sjl-theme-label').text('Light mode');
        }

        $('#simple-jwt-login').css('visibility', 'visible');
        $('#sjl-page-loader').addClass('sjl-loader-hidden');
        setTimeout(function () {
			$('#sjl-page-loader').remove(); }, 250);

        $('#sjl-theme-toggle').on('click', function () {
            var isDark   = $plugin.attr('data-sjl-theme') === 'dark';
            var newTheme = isDark ? 'light' : 'dark';

            if (isDark) {
                $plugin.removeAttr('data-sjl-theme');
                $('#wpwrap').removeClass('sjl-wp-dark');
                $('#sjl-theme-label').text('Dark mode');
            } else {
                $plugin.attr('data-sjl-theme', 'dark');
                $('#wpwrap').addClass('sjl-wp-dark');
                $('#sjl-theme-label').text('Light mode');
            }

            localStorage.setItem('sjl-theme', newTheme);
            $('#sjl-theme-mode-input').val(newTheme);
        });

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
            var $catalog = $tile.closest('.sjl-apps-catalog');
            var $body    = $catalog.closest('.sjl-apps-body');
            var $panels  = $body.find('.sjl-apps-panels');

            $catalog.find('.sjl-app-tile').removeClass('active').attr('aria-expanded', 'false');
            $panels.find('.sjl-app-panel').hide();

            $tile.addClass('active').attr('aria-expanded', 'true');
            $panels.removeClass('sjl-hidden').find('.sjl-app-panel').hide();
            $panels.find('#sjl-app-panel-' + appId).show();
            $body.find('input[type="hidden"][name$="_panel"]').val(appId);
        });

        // App catalog search filter (shared by OAuth and 3rd-party sections)
        $('#simple-jwt-login .sjl-apps-search').on('input', function () {
            var query    = $(this).val().toLowerCase().trim();
            var $body    = $(this).closest('.sjl-apps-body');
            var $catalog = $body.find('.sjl-apps-catalog');
            var $panels  = $body.find('.sjl-apps-panels');
            var $noRes   = $body.find('.sjl-apps-no-results');
            var visible  = 0;

            $catalog.find('.sjl-app-tile').each(function () {
                var name = $(this).data('name') || '';
                var show = query === '' || name.indexOf(query) !== -1;
                $(this).toggle(show);
                if (show) {
                    visible++;
                }
            });

            var noneFound = visible === 0;
            $noRes.toggle(noneFound);
            $panels.toggleClass('sjl-hidden', noneFound);
        });

        $('#simple-jwt-login #add_code').click(
            function () {
                $('#auth_codes').append($('#code_line').html());
            }
        );

        var $payloadCheckboxes = $('#authentication_payload_data input[type="checkbox"][name="jwt_payload[]"]');
        var $checkAll = $('#sjl-payload-check-all');

        function syncCheckAll() {
            var total   = $payloadCheckboxes.length;
            var checked = $payloadCheckboxes.filter(':checked').length;
            $checkAll.prop('checked', total > 0 && checked === total);
            $checkAll.prop('indeterminate', checked > 0 && checked < total);
        }

        $checkAll.on('change', function () {
            $payloadCheckboxes.prop('checked', $(this).is(':checked'));
        });

        $payloadCheckboxes.on('change', syncCheckAll);

        syncCheckAll();

        $('#simple-jwt-login #sjl-add-payload-claim').click(function () {
            $('#sjl-payload-claims-table').append($('#sjl-payload-claim-line').html());
        });

        $('#simple-jwt-login #sjl-add-header-claim').click(function () {
            $('#sjl-header-claims-table').append($('#sjl-header-claim-line').html());
        });

        $('#simple-jwt-login #add_rule_endpoint').click(
            function () {
                $('#endpoint-rules').append($('#endpoint_rule_line').html());
                sjlUpdateRuleCount();
            }
        );

        $('#simple-jwt-login input[name="jwt_reset_password_flow"]').on(
            'change',
            function () {
                sjlBindResetPassword();
            }
        )

        $(document).on('change', '#simple-jwt-login .sjl-endpoint-type-select', function () {
            sjlToggleRolesInput(this);
        });

        $('#simple-jwt-login input[name="redirect"]').on('change', function () {
            $('#simple-jwt-login #redirect_url').toggle(~~$(this).val() === 9);
        });

        ['require_register_auth', 'require_delete_auth'].forEach(function (inputName) {
            $('#simple-jwt-login input[name="' + inputName + '"]').on('change', function () {
                $('#simple-jwt-login #' + inputName + '_alert').toggle(~~$(this).val() === 0);
            });
        });

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

        function sjlUpdateHooksCount()
		{
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

        $('#simple-jwt-login #sjl-hooks-search').on(
            'input',
            function () {
                var query = $(this).val().toLowerCase().trim();
                var $items = $('#simple-jwt-login-tab-hooks .sjl-hooks-list .sjl-hook-item');
                var visibleCount = 0;

                $items.each(function () {
                    var name   = $(this).data('hook-name') || '';
                    var desc   = $(this).data('hook-desc') || '';
                    var params = $(this).data('hook-params') || '';
                    var match  = !query || name.indexOf(query) !== -1 || desc.indexOf(query) !== -1 || params.indexOf(query) !== -1;
                    $(this).toggle(match);
                    if (match) {
                        visibleCount++;
                    }
                });

                $('#sjl-hooks-no-results').toggle(visibleCount === 0 && query !== '');
            }
        );

        $('#simple-jwt-login #decryption_key').on(
            'keyup',
            function () {
                sjlCalculateStrengthDecryptionKey();
            }
        )

        $('#simple-jwt-login #simple-jwt-login-jwt-algorithm, #simple-jwt-login #decryption_source').on(
            'change',
            function (e) {
                sjlBindDecryptionKey();
            }
        );

        function sjlBindDecryptionKey()
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
                sjlCalculateStrengthDecryptionKey();
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

        function sjlUpdateRuleCount()
        {
            var count = $('#simple-jwt-login #endpoint-rules .endpoint_row').length;
            $('#simple-jwt-login #rules_endpoint_count').text(count);
        }

        function sjlCalculateStrength(value, labelId, progressId)
        {
            if (value.length === 0) {
                document.getElementById(labelId).innerHTML = "0%";
                document.getElementById(progressId).value = "0";
                return;
            }

            var progress = [/[$@$!%*#?&]/, /[A-Z]/, /[0-9]/, /[a-z]/]
                .reduce(function (memo, test) { return memo + test.test(value); }, 0);

            if (progress > 2 && value.length > 7) {
                progress++;
            }

            var percent;
            switch (progress) {
                case 0:
                case 1:
                case 2:
                    percent = "25";
                    break;
                case 3:
                    percent = "50";
                    break;
                case 4:
                    percent = "75";
                    break;
                default:
                    percent = "100";
                    break;
            }

            document.getElementById(labelId).innerHTML = percent + '%';
            document.getElementById(progressId).value = percent;
        }

        function sjlCalculateStrengthDecryptionKey()
        {
            var decryptionKey = jQuery('#decryption_key').val();

            if (jQuery('#decryption_key_base64').is(':checked')) {
                var base64regex = new RegExp('^([0-9a-zA-Z+/]{4})*(([0-9a-zA-Z+/]{2}==)|([0-9a-zA-Z+/]{3}=))?$');
                if (base64regex.test(decryptionKey)) {
                    decryptionKey = atob(decryptionKey);
                }
            }

            sjlCalculateStrength(decryptionKey, "decryption_progress_label", "decryption_progress");
        }

        function sjlCalculateStrengthRefreshTokenKey()
        {
            sjlCalculateStrength(
                jQuery('#refresh_token_key').val(),
                "refresh_token_progress_label",
                "refresh_token_progress"
            );
        }

        // Bind refresh token key strength calculation
        jQuery('#simple-jwt-login #refresh_token_key').on('keyup', function () {
            sjlCalculateStrengthRefreshTokenKey();
        });

        sjlBindDecryptionKey();
        sjlBindResetPassword();

        // -----------------------------------------------------------------------
        // JWT Rules — per-row algorithm toggle and dynamic add/remove
        // -----------------------------------------------------------------------

        function sjlRuleBindAlg($row)
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

        function sjlRuleBindCondition($row)
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
            sjlRuleBindAlg($(this));
            sjlRuleBindCondition($(this));
        });

        // Algorithm change on any rule row
        $(document).on('change', '#sjl-jwt-rules .sjl-rule-alg', function () {
            sjlRuleBindAlg($(this).closest('.sjl-rule-row'));
        });

        $(document).on('change', '#sjl-jwt-rules .sjl-rule-condition-type', function () {
            sjlRuleBindCondition($(this).closest('.sjl-rule-row'));
        });

        // Add new rule row by cloning the hidden template
        $('#simple-jwt-login #sjl-add-rule').on('click', function () {
            var $clone = $('#sjl-rule-row-template .sjl-rule-row').clone();
            $clone.removeAttr('style');
            $('#sjl-jwt-rules').append($clone);
            sjlRuleBindAlg($clone);
            sjlRuleBindCondition($clone);
        });

        // Toggle Secret Key visibility in rule rows
        $(document).on('click', '#sjl-jwt-rules .sjl-rule-toggle-key', function () {
            var $group = $(this).closest('.sjl-rule-key-group');
            var $input = $group.find('.sjl-rule-key');
            var $icon  = $group.find('.toggle-image');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.addClass('toggle_visible');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('toggle_visible');
            }
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
                // Base64-encoded (UTF-8 safe) so the JSON blob never carries raw quotes/
                // backslashes for this field: WordPress's wp_magic_quotes() runs
                // stripslashes_deep() before addslashes_deep() on $_POST, which eats one
                // layer of any real backslash a payload_template contains (e.g. from
                // JSON.stringify escaping an embedded ") before our code ever sees it.
                var payloadRaw = $row.find('.sjl-webhook-payload-template').val() || '';
                var payloadTemplate = btoa(unescape(encodeURIComponent(payloadRaw)));
                var timeout = parseInt($row.find('.sjl-webhook-timeout').val(), 10) || 0;
                webhooks.push({
                    url: url,
                    method: method,
                    enabled: enabled,
                    events: events,
                    headers: headers,
                    payload_template: payloadTemplate,
                    timeout: timeout
                });
            });
            $('#webhooks_json').val(JSON.stringify(webhooks));
        });

        // TABS
        $('#simple-jwt-login-tabs a').click(function (e) {
            e.preventDefault();
            var $link = $(this);
            var tabIndex = $link.attr('data-index');
            $('#active_tab').val(tabIndex);
            $link.tab('show');
            var url = new URL(window.location.href);
            var page = url.searchParams.get('page');
            url.search = '';
            if (page) {
                url.searchParams.set('page', page);
            }
            url.searchParams.set('active_tab', tabIndex);
            history.replaceState(null, '', url.toString());
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

        function sjlInsertAtCursor(el, text) {
            var start = el.selectionStart;
            var end   = el.selectionEnd;
            el.value = el.value.substring(0, start) + text + el.value.substring(end);
            el.selectionStart = el.selectionEnd = start + text.length;
        }

        // Webhooks: insert variable chip at textarea cursor
        $(document).on('click', '#sjl-webhooks .sjl-var-chip', function () {
            var $textarea = $(this).closest('.sjl-webhook-subsection').find('.sjl-webhook-payload-template');
            sjlInsertAtCursor($textarea[0], $(this).data('var'));
            $textarea.trigger('focus');
        });

        // Reset password: insert variable chip at subject/body cursor
        $(document).on('click', '#simple_jwt_reset_password_email_container .sjl-var-chip', function () {
            var el = document.getElementById($(this).data('target'));
            sjlInsertAtCursor(el, $(this).data('var'));
            el.focus();
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

        function sjlGetCollapsedGroups()
		{
            try {
                var raw = localStorage.getItem('sjl_nav_collapsed');
                return raw ? JSON.parse(raw) : {};
            } catch (e) {
                return {};
            }
        }

        function sjlSaveCollapsedGroups(state)
		{
            try {
                localStorage.setItem('sjl_nav_collapsed', JSON.stringify(state));
            } catch (e) {
			}
        }

        function sjlApplyGroupCollapse(groupId, collapse)
		{
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
                    var autoVal  = '';
                    if (key === 'rest_route') {
                        autoVal = safeHint
                    }
                    var safeAutoVal = jQuery('<span>').text(autoVal).html();
                    paramsHtml  += '<div class="sjl-try-param-row">'
                        + '<label class="sjl-try-param-label">' + safeKey + '</label>'
                        + '<input type="text" class="form-control sjl-try-param-input"'
                        + ' data-param="' + safeKey + '" placeholder="' + safeHint + '"'
                        + (autoVal ? ' value="' + safeAutoVal + '"' : '') + ' />'
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
                + '<div class="sjl-try-code-btns">'
                + '<span class="sjl-try-code-label">Code:</span>'
                + '<button class="sjl-try-code-btn" data-lang="curl" type="button">cURL</button>'
                + '<button class="sjl-try-code-btn" data-lang="js" type="button">JS</button>'
                + '<button class="sjl-try-code-btn" data-lang="php" type="button">PHP</button>'
                + '</div>'
                + '</div>'
                + '<div class="sjl-try-code-block" style="display:none">'
                + '<div class="sjl-try-code-block-header">'
                + '<span class="sjl-try-code-block-lang"></span>'
                + '<button class="sjl-try-code-copy-btn" type="button">'
                + '<span class="dashicons dashicons-clipboard"></span> Copy'
                + '</button>'
                + '</div>'
                + '<pre class="sjl-try-code-pre"></pre>'
                + '</div>'
                + '<div class="sjl-try-response" style="display:none">'
                + '<p class="sjl-try-section-label">Response</p>'
                + '<div class="sjl-try-response-status"></div>'
                + '<pre class="sjl-try-response-body"></pre>'
                + '</div>'
                + '</div>'
            );

            $panel.data({ base: base, method: method, isBody: isBody, defaultParams: params });
            var initialParams = isBody ? params : sjlTryCollectParams($panel);
            $panel.find('.sjl-try-url-code').text(isBody ? base : sjlTryBuildUrl(base, initialParams));

            $panel.on('input', '.sjl-try-param-input', function () {
                if (!isBody) {
                    var cur = sjlTryCollectParams($panel);
                    $panel.find('.sjl-try-url-code').text(sjlTryBuildUrl(base, cur));
                }

                var $activeBtn = $panel.find('.sjl-try-code-btn.active');
                if ($activeBtn.length) {
                    var activeLang = $activeBtn.data('lang');
                    var codeCur    = sjlTryCollectParamsForCode($panel);
                    var codeUrl    = isBody ? base : sjlTryBuildUrl(base, codeCur);
                    var rerendered = '';
                    if (activeLang === 'curl') {
                        rerendered = sjlTryGenCurl(codeUrl, method, codeCur, isBody);
                    } else if (activeLang === 'js') {
                        rerendered = sjlTryGenJs(codeUrl, method, codeCur, isBody);
                    } else if (activeLang === 'php') {
                        rerendered = sjlTryGenPhp(codeUrl, method, codeCur, isBody);
                    }
                    $panel.find('.sjl-try-code-pre').text(rerendered);
                }
            });

            $panel.on('click', '.sjl-try-close', function () {
				$panel.remove(); });

            $panel.on('click', '.sjl-try-send-btn', function () {
                var cur      = sjlTryCollectParams($panel);
                var fetchUrl = isBody ? base : sjlTryBuildUrl(base, cur);
                var fetchOpts = isBody
                    ? { method : method, headers : { 'Content-Type' : 'application/json' }, body : JSON.stringify(cur) }
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
                        return res.text().then(function (t) {
							return { status: st, statusText: stText, body: t }; });
                    })
                    .then(function (data) {
                        var ok = data.status >= 200 && data.status < 300;
                        $status.text('Status: ' + data.status + ' ' + data.statusText)
                            .addClass(ok ? 'sjl-try-status--ok' : 'sjl-try-status--err');
                        var formatted;
                        try {
							formatted = JSON.stringify(JSON.parse(data.body), null, 2); } catch (ex) {
							formatted = data.body; }
							$body.text(formatted);
							$btn.prop('disabled', false).text('Send Request');
                    })
                    .catch(function (err) {
                        $status.text('Network error: ' + err.message).addClass('sjl-try-status--err');
                        $body.text('');
                        $btn.prop('disabled', false).text('Send Request');
                    });
            });

            $panel.on('click', '.sjl-try-code-btn', function () {
                var $btn       = jQuery(this);
                var lang       = $btn.data('lang');
                var $codeBlock = $panel.find('.sjl-try-code-block');
                var $codePre   = $panel.find('.sjl-try-code-pre');
                var $codeLang  = $panel.find('.sjl-try-code-block-lang');

                if ($btn.hasClass('active')) {
                    $btn.removeClass('active');
                    $codeBlock.hide();
                    return;
                }

                $panel.find('.sjl-try-code-btn').removeClass('active');
                $btn.addClass('active');

                var cur = sjlTryCollectParamsForCode($panel);
                var url = isBody ? base : sjlTryBuildUrl(base, cur);
                var code = '';
                if (lang === 'curl') {
                    code = sjlTryGenCurl(url, method, cur, isBody);
                    $codeLang.text('cURL');
                } else if (lang === 'js') {
                    code = sjlTryGenJs(url, method, cur, isBody);
                    $codeLang.text('JavaScript');
                } else if (lang === 'php') {
                    code = sjlTryGenPhp(url, method, cur, isBody);
                    $codeLang.text('PHP');
                }

                $codePre.text(code);
                $codeBlock.show();
            });

            $panel.on('click', '.sjl-try-code-copy-btn', function () {
                var $btn = jQuery(this);
                var code = $panel.find('.sjl-try-code-pre').text();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(code).then(function () {
                        $btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
                        setTimeout(function () {
                            $btn.html('<span class="dashicons dashicons-clipboard"></span> Copy');
                        }, 2000);
                    });
                }
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
    if (qIndex === -1) {
		return params; }
    var qs = url.slice(qIndex + 1);
    qs.split('&').forEach(function (pair) {
        var eq  = pair.indexOf('=');
        if (eq === -1) {
			return; }
        var key = decodeURIComponent(pair.slice(0, eq));
        var val = decodeURIComponent(pair.slice(eq + 1));
        params[key] = val;
    });
    return params;
}

function sjlTryBaseUrl(url)
{
    const q = url.indexOf('?');

    if (q === -1) {
        return url;
    }

    const match = url.match(/[?&]rest_route=([^&]+)/);

    return match
        ? url.slice(0, q) + '?rest_route=' + match[1]
        : url.slice(0, q);
}

function sjlTryBuildUrl(base, params)
{
    var keys = Object.keys(params).filter(function (k) { return k !== 'rest_route'; });
    if (!keys.length) {
        return base;
    }
    var qs = keys.map(function (k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
    }).join('&');
    var sep = base.indexOf('?') === -1 ? '?' : '&';
    return base + sep + qs;
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

function sjlTryCollectParamsForCode($panel)
{
    var out = {};
    $panel.find('.sjl-try-param-input').each(function () {
        var $input = jQuery(this);
        var k      = $input.data('param');
        var val    = $input.val();
        out[k]     = val !== '' ? val : ($input.attr('placeholder') || '');
    });
    return out;
}

function sjlRemoveAuthLine(a_element)
{
    jQuery(a_element).closest('.auth_row').remove();
}

/* ── Try Now code generators ─────────────────────────────────────────────── */

function sjlTryGenCurl(url, method, params, isBody)
{
    if (isBody) {
        return 'curl -X ' + method + ' "' + url + '" \\\n'
            + '  -H "Content-Type: application/json" \\\n'
            + '  -d \'' + JSON.stringify(params, null, 2) + '\'';
    }
    return 'curl -X ' + method + ' "' + url + '"';
}

function sjlTryGenJs(url, method, params, isBody)
{
    if (isBody) {
        return 'const response = await fetch(\'' + url + '\', {\n'
            + '  method: \'' + method + '\',\n'
            + '  headers: { \'Content-Type\': \'application/json\' },\n'
            + '  body: JSON.stringify(' + JSON.stringify(params, null, 2) + ')\n'
            + '});\n'
            + 'const data = await response.json();\n'
            + 'console.log(data);';
    }
    return 'const response = await fetch(\'' + url + '\', {\n'
        + '  method: \'' + method + '\'\n'
        + '});\n'
        + 'const data = await response.json();\n'
        + 'console.log(data);';
}

function sjlTryGenPhp(url, method, params, isBody)
{
    if (isBody) {
        return '<?php\n'
            + '$ch = curl_init(\'' + url + '\');\n'
            + 'curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);\n'
            + 'curl_setopt($ch, CURLOPT_CUSTOMREQUEST, \'' + method + '\');\n'
            + 'curl_setopt($ch, CURLOPT_HTTPHEADER, [\'Content-Type: application/json\']);\n'
            + 'curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(' + JSON.stringify(params, null, 2) + '));\n'
            + '$body = curl_exec($ch);\n'
            + 'curl_close($ch);\n'
            + '$data = json_decode($body, true);';
    }
    return '<?php\n'
        + '$ch = curl_init(\'' + url + '\');\n'
        + 'curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);\n'
        + '$body = curl_exec($ch);\n'
        + 'curl_close($ch);\n'
        + '$data = json_decode($body, true);';
}

function sjlRemoveEndpointRow(a_element)
{
    jQuery(a_element).closest('.endpoint_row').remove();
    jQuery('#simple-jwt-login #rules_endpoint_count').text(
        jQuery('#simple-jwt-login #endpoint-rules .endpoint_row').length
    );
}

function sjlToggleRolesInput(selectEl)
{
    var rolesInput = jQuery(selectEl).closest('.endpoint_row').find('.sjl-endpoint-roles-input');
    if (jQuery(selectEl).val() === 'protected_roles') {
        rolesInput.show();
    } else {
        rolesInput.hide();
    }
}

function sjlShowDecryptionKey()
{
    sjlToggleSecret('decryption_key_container', 'decryption_key');
}

function sjlToggleSecret(containerId, inputId)
{
    var elementType = 'text';
    if (jQuery('#' + inputId).attr('type') === 'text') {
        jQuery('#' + containerId + ' .toggle-image').removeClass('toggle_visible');
        elementType = 'password';
    } else {
        jQuery('#' + containerId + ' .toggle-image').addClass('toggle_visible');
    }
    jQuery('#' + inputId).attr('type', elementType);
}

function sjlShowRefreshTokenKey()
{
    sjlToggleSecret('refresh_token_key_container', 'refresh_token_key');
}

function sjlGenerateRefreshTokenKey()
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

function sjlBindResetPassword()
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
    jQuery('.sjl-ak-perm-check:checked').each(function () {
		perms.push(jQuery(this).val()); });
    var msg = jQuery('#sjl-ak-create-msg');
    msg.text('').removeClass('sjl-ak-msg--error sjl-ak-msg--ok');

    if (!name) {
		msg.text('Name is required.').addClass('sjl-ak-msg--error'); return; }
    if (!perms.length) {
		msg.text('Select at least one permission.').addClass('sjl-ak-msg--error'); return; }

    jQuery.ajax({
        url: cfg.restBase,
        method: 'POST',
        contentType: 'application/json',
        beforeSend: function (xhr) {
			xhr.setRequestHeader('X-WP-Nonce', cfg.nonce); },
        data: JSON.stringify({ name: name, permissions: perms, expires_at: expires || null }),
        success: function (res) {
            if (res.data && res.data.key) {
                jQuery('#sjl-ak-raw-key').text(res.data.key);
                jQuery('#sjl-ak-modal').show();
            }
        },
        error: function (xhr) {
            var errMsg = 'Failed to create API key.';
            try {
				errMsg = JSON.parse(xhr.responseText).message || errMsg; } catch (e) {
				}
				msg.text(errMsg).addClass('sjl-ak-msg--error');
        }
    });
}

function sjlApiKeyAction(url, method, confirmMsg, errorMsg)
{
    if (!window.confirm(confirmMsg)) {
        return;
    }
    var cfg = window._sjlAkConfig;
    jQuery.ajax({
        url: url,
        method: method,
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
        },
        success: function () {
            window.location.reload();
        },
        error: function () {
            alert(errorMsg);
        }
    });
}

function sjlRevokeApiKey(id)
{
    sjlApiKeyAction(
        window._sjlAkConfig.restBase + '/' + id + '/revoke',
        'POST',
        'Revoke this API key? This cannot be undone.',
        'Failed to revoke API key.'
    );
}

function sjlDeleteApiKey(id)
{
    sjlApiKeyAction(
        window._sjlAkConfig.restBase + '/' + id,
        'DELETE',
        'Permanently delete this API key? This cannot be undone.',
        'Failed to delete API key.'
    );
}

function sjlCopyApiKey()
{
    var key = jQuery('#sjl-ak-raw-key').text();
    if (!key) {
		return; }
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

function sjlRemoveClaimRow(btn)
{
    jQuery(btn).closest('.sjl-claims-row').remove();
}

/* ============================================
   JWT Decoder
   ============================================ */
(function ($) {
    function sjlBase64UrlDecode(str) {
        var s = str.replace(/-/g, '+').replace(/_/g, '/');
        while (s.length % 4 !== 0) {
            s += '=';
        }
        try {
            return decodeURIComponent(
                atob(s).split('').map(function (c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join('')
            );
        } catch (ex) {
            return null;
        }
    }

    function sjlHighlightJson(obj) {
        var json = JSON.stringify(obj, null, 2);
        return json.replace(
            /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+-]?\d+)?)/g,
            function (match) {
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        return '<span class="sjl-json-key">' + match + '</span>';
                    }
                    return '<span class="sjl-json-string">' + match + '</span>';
                }
                if (/true|false/.test(match)) {
                    return '<span class="sjl-json-bool">' + match + '</span>';
                }
                if (/null/.test(match)) {
                    return '<span class="sjl-json-null">' + match + '</span>';
                }
                return '<span class="sjl-json-number">' + match + '</span>';
            }
        );
    }

    function sjlDecodeToken(token) {
        var $error  = $('#sjl-decoder-error');
        var $errMsg = $('#sjl-decoder-error-msg');

        $error.hide();

        if (!token) {
            $('#sjl-decoder-header-json').empty();
            $('#sjl-decoder-payload-json').empty();
            return;
        }

        var parts = token.split('.');
        if (parts.length !== 3) {
            $errMsg.text('Invalid JWT: expected three dot-separated parts.');
            $error.show();
            return;
        }

        var headerRaw  = sjlBase64UrlDecode(parts[0]);
        var payloadRaw = sjlBase64UrlDecode(parts[1]);

        if (headerRaw === null || payloadRaw === null) {
            $errMsg.text('Invalid JWT: could not base64url-decode token parts.');
            $error.show();
            return;
        }

        var headerObj, payloadObj;
        try {
            headerObj = JSON.parse(headerRaw);
        } catch (ex) {
            $errMsg.text('Invalid JWT: header is not valid JSON.');
            $error.show();
            return;
        }
        try {
            payloadObj = JSON.parse(payloadRaw);
        } catch (ex) {
            $errMsg.text('Invalid JWT: payload is not valid JSON.');
            $error.show();
            return;
        }

        $('#sjl-decoder-header-json').html(sjlHighlightJson(headerObj));
        $('#sjl-decoder-payload-json').html(sjlHighlightJson(payloadObj));
    }

    function sjlCopyText(targetId) {
        var text = $('#' + targetId).text();
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
    }

    $(document).on('input', '#sjl-decoder-input', function () {
        sjlDecodeToken($(this).val().trim());
    });

    $(document).on('click', '#sjl-decoder-clear', function () {
        $('#sjl-decoder-input').val('').trigger('input');
    });

    $(document).on('click', '.sjl-decoder-copy-btn', function () {
        sjlCopyText($(this).data('target'));
        var $btn = $(this);
        var $icon = $btn.find('.dashicons');
        $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes');
        setTimeout(function () {
            $icon.removeClass('dashicons-yes').addClass('dashicons-clipboard');
        }, 1500);
    });
}(jQuery));
