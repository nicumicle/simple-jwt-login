/**
 * Simple JWT Login — Setup Wizard
 *
 * HOW TO ADD A NEW FEATURE TO THE WIZARD:
 *   1. Add a new object to SJL_WIZARD_FEATURES below.
 *   2. Fill in: id, label, description, icon, tabId, tabIndex, and fields.
 *   3. Each field supports types: 'radio', 'text', 'password', 'textarea', 'select-clone'.
 *      Use `showWhen` on any field to make it conditional on another field's value.
 *   No other changes needed anywhere else.
 */

(function ($) {
    'use strict';

    var __ = wp.i18n.__;
    var sprintf = wp.i18n.sprintf;

    // =========================================================================
    // WIZARD CONFIGURATION
    // =========================================================================

    /**
     * General step — always shown right after feature selection.
     *
     * Key fields are conditional via `showWhen`:
     *   - HS* algorithms → decryption_key (password)
     *   - RS* algorithms → decryption_key_public + decryption_key_private (textareas)
     *
     * showWhen shape: { field: '<fieldName>', matches: /regex/ }
     *                 { field: '<fieldName>', notMatches: /regex/ }
     */
    var SJL_WIZARD_GENERAL = {
        id:          'general',
        label:       __('General Settings', 'simple-jwt-login'),
        description: __('These settings are required for all plugin features to work correctly.', 'simple-jwt-login'),
        fields: [
            {
                type:        'text',
                label:       __('Route Namespace', 'simple-jwt-login'),
                name:        'route_namespace',
                placeholder: 'simple-jwt-login',
                help:        __('Base URL path for all Simple JWT Login REST endpoints. Change only if you need to avoid conflicts.', 'simple-jwt-login')
            },
            {
                type:     'select-clone',
                label:    __('JWT Algorithm', 'simple-jwt-login'),
                name:     'jwt_algorithm',
                sourceId: 'simple-jwt-login-jwt-algorithm',
                help:     __('Algorithm used to sign JWT tokens. Must match what your client applications use.', 'simple-jwt-login')
            },
            // HS* / EdDSA / ES* — single shared secret ----------------------
            {
                type:        'password',
                label:       __('JWT Secret Key', 'simple-jwt-login'),
                name:        'decryption_key',
                placeholder: __('Enter a strong secret key', 'simple-jwt-login'),
                help:        __('Secret used to sign and verify JWT tokens. Keep this private!', 'simple-jwt-login'),
                showWhen:    { field: 'jwt_algorithm', notMatches: /^RS/ }
            },
            // RS* — asymmetric key pair --------------------------------------
            {
                type:      'textarea',
                label:     __('Public Key', 'simple-jwt-login'),
                name:      'decryption_key_public',
                rows:      5,
                help:      __('PEM-formatted RSA public key used to verify incoming JWT tokens.', 'simple-jwt-login'),
                showWhen:  { field: 'jwt_algorithm', matches: /^RS/ }
            },
            {
                type:      'textarea',
                label:     __('Private Key', 'simple-jwt-login'),
                name:      'decryption_key_private',
                rows:      5,
                help:      __('PEM-formatted RSA private key used to sign JWT tokens.', 'simple-jwt-login'),
                sensitive: true,   // value masked in the summary
                showWhen:  { field: 'jwt_algorithm', matches: /^RS/ }
            },  
            // API middleware ---------------------------------------------
            {
                type:    'radio',
                label:   __('Check JWT on all WordPress endpoints?', 'simple-jwt-login'),
                name:    'api_middleware[enabled]',
                help:  __('If a JWT is provided on any endpoint, the plugin will try to authenticate the user from that JWT in order to perform the API call.', 'simple-jwt-login'),
                options: [
                    { value: '1', label: __('Yes — check for JWT on all REST endpoints', 'simple-jwt-login') },
                    { value: '0', label: __('No — keep disabled', 'simple-jwt-login')                  }
                ]
            },
            // User identification ----------------------------------------
            {
                type:     'select-clone',
                label:    __('How to Identify User', 'simple-jwt-login'),
                name:     'jwt_login_by',
                sourceId: 'jwt_login_by',
                help:     __('Choose which WordPress user attribute the JWT payload value is matched against.', 'simple-jwt-login')
            },
            {
                type:        'text',
                label:       __('JWT Parameter Key', 'simple-jwt-login'),
                name:        'jwt_login_by_parameter',
                placeholder: __('Example: email', 'simple-jwt-login'),
                help:        __('The key name in the JWT payload that holds the user identifier. Use dot notation for nested values (e.g. user.id).', 'simple-jwt-login')
            }
        ]
    };

    /**
     * Feature list — each entry creates one wizard step.
     *
     * To add a new feature, append an object with these properties:
     *   @property {string}   id          Unique slug (no spaces)
     *   @property {string}   label       Human-readable name shown on the card
     *   @property {string}   description Short description shown on the selection screen
     *   @property {string}   icon        Emoji shown on the card
     *   @property {string}   tabId       HTML id of the corresponding settings tab panel
     *   @property {string}   tabIndex    Value of data-index on the tab <a> element
     *   @property {Array}    fields      Form fields to show in this feature's wizard step
     */
    var SJL_WIZARD_FEATURES = [
        {
            id:          'autologin',
            label:       __('Auto Login', 'simple-jwt-login'),
            description: __('Let users log in automatically via a JWT in the URL or Authorization header.', 'simple-jwt-login'),
            icon:        '🔑',
            tabId:       'simple-jwt-login-tab-login',
            tabIndex:    '2',
            fields: [
                {
                    type:  'radio',
                    label: __('Enable Auto Login?', 'simple-jwt-login'),
                    name:  'allow_autologin',
                    options: [
                        { value: '1', label: __('Yes — enable auto login', 'simple-jwt-login') },
                        { value: '0', label: __('No — keep disabled', 'simple-jwt-login')      }
                    ]
                },
            ]
        },
        {
            id:          'register',
            label:       __('User Registration', 'simple-jwt-login'),
            description: __('Allow new WordPress users to register through the JWT REST API.', 'simple-jwt-login'),
            icon:        '👤',
            tabId:       'simple-jwt-login-tab-register',
            tabIndex:    '3',
            fields: [
                {
                    type:  'radio',
                    label: __('Allow User Registration?', 'simple-jwt-login'),
                    name:  'allow_register',
                    options: [
                        { value: '1', label: __('Yes — allow registrations', 'simple-jwt-login') },
                        { value: '0', label: __('No — keep disabled', 'simple-jwt-login')        }
                    ]
                }
            ]
        },
        {
            id:          'delete',
            label:       __('Delete User', 'simple-jwt-login'),
            description: __('Let users delete their own WordPress accounts via the JWT API.', 'simple-jwt-login'),
            icon:        '🗑',
            tabId:       'simple-jwt-login-tab-delete',
            tabIndex:    '4',
            fields: [
                {
                    type:  'radio',
                    label: __('Enable User Deletion?', 'simple-jwt-login'),
                    name:  'allow_delete',
                    options: [
                        { value: '1', label: __('Yes — allow account deletion', 'simple-jwt-login') },
                        { value: '0', label: __('No — keep disabled', 'simple-jwt-login')           }
                    ]
                },
                {
                    type:        'text',
                    label:       __('JWT Parameter Key', 'simple-jwt-login'),
                    name:        'jwt_delete_by_parameter',
                    placeholder: __('Example: email', 'simple-jwt-login'),
                    help:        __('The key name in the JWT payload that holds the user identifier for deletion. Use dot notation for nested values (e.g. user.id).', 'simple-jwt-login'),
                    showWhen:    { field: 'allow_delete', matches: /^1$/ }
                },
                {
                    type:     'checkbox',
                    label:    __('Require Authentication Code for Deletion?', 'simple-jwt-login'),
                    name:     'require_auth_code_for_delete',
                    help:     __('Require an additional authentication code in the request to delete the user account.', 'simple-jwt-login'),
                    showWhen: { field: 'allow_delete', matches: /^1$/ }
                }
            ]
        },
        {
            id:          'reset_password',
            label:       __('Reset Password', 'simple-jwt-login'),
            description: __('Allow users to trigger a WordPress password reset via the JWT API.', 'simple-jwt-login'),
            icon:        '🔒',
            tabId:       'simple-jwt-login-tab-reset-password',
            tabIndex:    '5',
            fields: [
                {
                    type:  'radio',
                    label: __('Enable Password Reset?', 'simple-jwt-login'),
                    name:  'allow_reset_password',
                    options: [
                        { value: '1', label: __('Yes — allow password resets', 'simple-jwt-login') },
                        { value: '0', label: __('No — keep disabled', 'simple-jwt-login')          }
                    ]
                }
            ]
        },
        {
            id:          'authentication',
            label:       __('JWT Authentication', 'simple-jwt-login'),
            description: __('Generate JWT tokens by authenticating with WordPress credentials.', 'simple-jwt-login'),
            icon:        '🛡',
            tabId:       'auth-tab-login',
            tabIndex:    '6',
            fields: [
                {
                    type:  'radio',
                    label: __('Enable JWT Authentication?', 'simple-jwt-login'),
                    name:  'allow_authentication',
                    options: [
                        { value: '1', label: __('Yes — enable token generation', 'simple-jwt-login') },
                        { value: '0', label: __('No — keep disabled', 'simple-jwt-login')            }
                    ]
                },
                {
                    type:     'checkbox-group',
                    label:    __('JWT Payload Fields', 'simple-jwt-login'),
                    name:     'jwt_payload',
                    help:     __('Select which user data to include in the JWT payload. "iat" (issued at) is always included automatically.', 'simple-jwt-login'),
                    showWhen: { field: 'allow_authentication', matches: /^1$/ },
                    options: [
                        { value: 'exp',      label: __('exp — Expiration time', 'simple-jwt-login')  },
                        { value: 'email',    label: __('email — User email', 'simple-jwt-login')     },
                        { value: 'id',       label: __('id — User ID', 'simple-jwt-login')           },
                        { value: 'site',     label: __('site — Site URL', 'simple-jwt-login')        },
                        { value: 'username', label: __('username — Username', 'simple-jwt-login')    },
                        { value: 'iss',      label: __('iss — Issuer', 'simple-jwt-login')           }
                    ]
                }
            ]
        },
        {
            id:          'cors',
            label:       __('CORS Support', 'simple-jwt-login'),
            description: __('Allow browser requests from other domains to the JWT API endpoints.', 'simple-jwt-login'),
            icon:        '🌐',
            tabId:       'simple-jwt-login-tab-cors',
            tabIndex:    '9',
            fields: [
                {
                    type:  'radio',
                    label: __('Enable CORS Support?', 'simple-jwt-login'),
                    name:  'cors[enabled]',
                    options: [
                        { value: '1', label: __('Yes — allow cross-origin requests', 'simple-jwt-login') },
                        { value: '0', label: __('No — keep disabled', 'simple-jwt-login')               }
                    ]
                },
                {
                    type:     'checkbox',
                    label:    __('Access-Control-Allow-Origin', 'simple-jwt-login'),
                    name:     'cors[allow_origin_enabled]',
                    help:     __('Include the Access-Control-Allow-Origin header in responses.', 'simple-jwt-login'),
                    showWhen: { field: 'cors[enabled]', matches: /^1$/ }
                },
                {
                    type:        'text',
                    label:       __('Allowed Origin(s)', 'simple-jwt-login'),
                    name:        'cors[allow_origin]',
                    placeholder: '*',
                    help:        __('Comma-separated list of origins to allow, or "*" for all.', 'simple-jwt-login'),
                    showWhen:    { field: 'cors[allow_origin_enabled]', matches: /^1$/ }
                },
                {
                    type:     'checkbox',
                    label:    __('Access-Control-Allow-Methods', 'simple-jwt-login'),
                    name:     'cors[allow_methods_enabled]',
                    help:     __('Include the Access-Control-Allow-Methods header in responses.', 'simple-jwt-login'),
                    showWhen: { field: 'cors[enabled]', matches: /^1$/ }
                },
                {
                    type:        'text',
                    label:       __('Allowed Methods', 'simple-jwt-login'),
                    name:        'cors[allow_methods]',
                    placeholder: 'GET, POST, OPTIONS',
                    help:        __('Methods to allow when using CORS. Comma-separated.', 'simple-jwt-login'),
                    showWhen:    { field: 'cors[allow_methods_enabled]', matches: /^1$/ }
                },
                {
                    type:     'checkbox',
                    label:    __('Access-Control-Allow-Headers', 'simple-jwt-login'),
                    name:     'cors[allow_headers_enabled]',
                    help:     __('Include the Access-Control-Allow-Headers header in responses.', 'simple-jwt-login'),
                    showWhen: { field: 'cors[enabled]', matches: /^1$/ }
                },
                {
                    type:        'text',
                    label:       __('Allowed Headers', 'simple-jwt-login'),
                    name:        'cors[allow_headers]',
                    placeholder: '*',
                    help:        __('Headers browsers are allowed to send in cross-origin requests. Comma-separated.', 'simple-jwt-login'),
                    showWhen:    { field: 'cors[allow_headers_enabled]', matches: /^1$/ }
                }
            ]
        }
    ];

    // =========================================================================
    // WIZARD STATE
    // =========================================================================

    var state = {
        selectedFeatures:  [],   // ids chosen by the user on the selection screen
        steps:             [],   // ordered step ids built after feature selection
        currentStep:       0,    // current index in state.steps
        currentStepConfig: null, // config object for the active step (for conditional fields)
        values:            {}    // fieldName → value collected across all steps
    };

    // =========================================================================
    // BOOTSTRAP
    // =========================================================================

    $(document).ready(function () {
        $('#sjl-wizard-btn').on('click', function (e) {
            e.preventDefault();
            openWizard();
        });

        // Close only via × button
        $('#sjl-wizard-modal').on('click', '.sjl-wizard-close-btn', closeWizard);

        // Navigation
        $('#sjl-wizard-modal').on('click', '#sjl-wizard-btn-next',   goNext);
        $('#sjl-wizard-modal').on('click', '#sjl-wizard-btn-prev',   goPrev);
        $('#sjl-wizard-modal').on('click', '#sjl-wizard-btn-finish', finishWizard);

        // Re-evaluate conditional fields when a select-clone changes
        $('#sjl-wizard-modal').on('change', '.sjl-wizard-select', function () {
            state.values[$(this).data('field')] = $(this).val();
            updateConditionalFields();
        });

        // Re-evaluate conditional fields when a radio changes
        $('#sjl-wizard-modal').on('change', 'input[type="radio"]', function () {
            state.values[$(this).data('field')] = $(this).val();
            updateConditionalFields();
        });

        // Re-evaluate conditional fields when a checkbox changes
        $('#sjl-wizard-modal').on('change', 'input[type="checkbox"][data-field]', function () {
            state.values[$(this).data('field')] = $(this).is(':checked') ? '1' : '0';
            updateConditionalFields();
        });

        // Summary: click a section title to jump to that settings tab
        $('#sjl-wizard-modal').on('click', '.sjl-summary-clickable', function () {
            var tabIndex = $(this).data('tabIndex');
            closeWizard();
            $('[data-toggle="tab"][data-index="' + tabIndex + '"]').trigger('click');
        });
    });

    // =========================================================================
    // OPEN / CLOSE
    // =========================================================================

    function openWizard() {
        state.selectedFeatures  = [];
        state.steps             = ['feature-selection'];
        state.currentStep       = 0;
        state.currentStepConfig = null;
        state.values            = readCurrentFormValues();

        renderStep();
        updateProgress();
        updateFooter();

        $('#sjl-wizard-modal').addClass('sjl-active');
        $('body').addClass('sjl-wizard-open');
    }

    function closeWizard() {
        $('#sjl-wizard-modal').removeClass('sjl-active');
        $('body').removeClass('sjl-wizard-open');
    }

    // =========================================================================
    // FORM VALUE SYNC
    // =========================================================================

    /** Snapshot current values from the real settings form. */
    function readCurrentFormValues() {
        var values = {};

        // General — text / password inputs
        values['route_namespace']       = $('input[name="route_namespace"]').val()         || '';
        values['decryption_key']        = $('input[name="decryption_key"]').val()          || '';
        values['jwt_algorithm']         = $('select[name="jwt_algorithm"]').val()          || 'HS256';

        // General — RS* textareas
        values['decryption_key_public']  = $('textarea[name="decryption_key_public"]').val()  || '';
        values['decryption_key_private'] = $('textarea[name="decryption_key_private"]').val() || '';

        // General — api middleware
        values['api_middleware[enabled]'] = $('input[name="api_middleware[enabled]"]').is(':checked') ? '1' : '0';

        // General — user identification
        values['jwt_login_by']           = $('select[name="jwt_login_by"]').val()              || '';
        values['jwt_login_by_parameter'] = $('input[name="jwt_login_by_parameter"]').val()     || '';

        // Feature fields — all types
        $.each(SJL_WIZARD_FEATURES, function (i, feature) {
            $.each(feature.fields, function (j, field) {
                switch (field.type) {
                    case 'radio':
                        var checked = $('input[name="' + field.name + '"]:checked').val();
                        // Default to disabled if nothing is checked yet
                        values[field.name] = (checked !== undefined) ? checked : field.options[1].value;
                        break;
                    case 'checkbox':
                        values[field.name] = $('input[name="' + field.name + '"]').is(':checked') ? '1' : '0';
                        break;
                    case 'checkbox-group':
                        var selected = [];
                        $('input[name="' + field.name + '[]"]:checked').each(function () {
                            selected.push($(this).val());
                        });
                        values[field.name] = selected;
                        break;
                    case 'select-clone':
                        values[field.name] = $('select[name="' + field.name + '"]').val() || '';
                        break;
                    case 'text':
                    case 'password':
                        values[field.name] = $('input[name="' + field.name + '"]').val() || '';
                        break;
                    case 'textarea':
                        values[field.name] = $('textarea[name="' + field.name + '"]').val() || '';
                        break;
                }
            });
        });

        // If the user enables CORS, default to including at least an Allow-Origin header
        // so the wizard doesn't error on save and gives a sensible starting point.
        if (values['cors[enabled]'] === '1') {
            if (values['cors[allow_origin_enabled]'] === undefined) {
                values['cors[allow_origin_enabled]'] = '1';
            }
            if (values['cors[allow_origin_enabled]'] === '1' && !values['cors[allow_origin]']) {
                values['cors[allow_origin]'] = '*';
            }
        }

        return values;
    }

    /** Write wizard-collected values back to the real settings form. */
    function applyValuesToForm() {
        $.each(state.values, function (name, value) {
            // Textareas (RS* public / private keys)
            var $ta = $('textarea[name="' + name + '"]');
            if ($ta.length) {
                $ta.val(value);
                return; // continue $.each
            }

            var $checkboxes = $('input[name="' + name + '[]"][type="checkbox"]');
            var $radios     = $('input[name="' + name + '"][type="radio"]');
            var $checkbox   = $('input[name="' + name + '"][type="checkbox"]');
            var $select     = $('select[name="' + name + '"]');
            var $text       = $('input[name="' + name + '"]').not('[type="radio"]');

            if ($checkboxes.length && Array.isArray(value)) {
                $checkboxes.prop('checked', false);
                $.each(value, function (i, v) {
                    $checkboxes.filter('[value="' + v + '"]').prop('checked', true);
                });
            } else if ($checkbox.length) {
                $checkbox.prop('checked', value === '1' || value === 1 || value === true);
            } else if ($radios.length) {
                $radios.prop('checked', false);
                $radios.filter('[value="' + value + '"]').prop('checked', true).trigger('change');
            } else if ($select.length) {
                $select.val(value).trigger('change');
            } else if ($text.length) {
                $text.val(value);
            }
        });
    }

    // =========================================================================
    // NAVIGATION
    // =========================================================================

    function goNext() {
        collectStepValues();

        if (state.currentStep === 0) {
            buildSteps(); // build ordered step list from selected features
        }

        state.currentStep++;
        renderStep();
        updateProgress();
        updateFooter();
    }

    function goPrev() {
        collectStepValues();
        state.currentStep--;
        renderStep();
        updateProgress();
        updateFooter();
    }

    /**
     * Apply values to the main form and submit it to save settings.
     * The page will reload with a success/error notice (standard WP settings flow).
     */
    function finishWizard() {
        applyValuesToForm();
        closeWizard();
        $('#simple-jwt-login').closest('form').submit();
    }

    /** Build state.steps after the user has chosen features. */
    function buildSteps() {
        state.steps = ['feature-selection', 'general'];

        // Preserve the canonical order defined in SJL_WIZARD_FEATURES
        $.each(SJL_WIZARD_FEATURES, function (i, feature) {
            if (state.selectedFeatures.indexOf(feature.id) !== -1) {
                state.steps.push(feature.id);
            }
        });

        state.steps.push('summary');
    }

    // =========================================================================
    // STEP RENDERING
    // =========================================================================

    function renderStep() {
        var stepId = state.steps[state.currentStep];

        switch (stepId) {
            case 'feature-selection':
                state.currentStepConfig = null;
                renderFeatureSelection();
                break;
            case 'general':
                renderGenericStep(SJL_WIZARD_GENERAL);
                break;
            case 'summary':
                state.currentStepConfig = null;
                renderSummary();
                break;
            default:
                var feature = featureById(stepId);
                if (feature) { renderGenericStep(feature); }
        }
    }

    function renderFeatureSelection() {
        setStepHeader(
            __('What would you like to enable?', 'simple-jwt-login'),
            __('Select the features you want to configure. You can always adjust individual settings later.', 'simple-jwt-login')
        );

        var html = '<div class="sjl-wizard-grid">';
        $.each(SJL_WIZARD_FEATURES, function (i, feature) {
            var isSel = state.selectedFeatures.indexOf(feature.id) !== -1;
            html += '<div class="sjl-wizard-card' + (isSel ? ' sjl-selected' : '') + '" data-feature-id="' + escAttr(feature.id) + '" role="checkbox" aria-checked="' + (isSel ? 'true' : 'false') + '" tabindex="0">';
            html += '  <div class="sjl-wizard-card-icon">'  + feature.icon                  + '</div>';
            html += '  <div class="sjl-wizard-card-name">'  + escHtml(feature.label)        + '</div>';
            html += '  <div class="sjl-wizard-card-desc">'  + escHtml(feature.description)  + '</div>';
            html += '  <div class="sjl-wizard-card-check">&#10003;</div>';
            html += '</div>';
        });
        html += '</div>';
        html += '<p class="sjl-wizard-hint">' + escHtml(__('General settings (JWT key & algorithm) will always be configured in the next step.', 'simple-jwt-login')) + '</p>';

        setContent(html);

        // Card toggle — rebind each render (click + keyboard)
        $('#sjl-wizard-content')
            .off('click keydown', '.sjl-wizard-card')
            .on('click keydown', '.sjl-wizard-card', function (e) {
                if (e.type === 'keydown' && e.which !== 13 && e.which !== 32) { return; }
                if (e.type === 'keydown') { e.preventDefault(); }

                var $card = $(this);
                var id    = $card.data('feature-id');
                var idx   = state.selectedFeatures.indexOf(id);
                if (idx !== -1) {
                    state.selectedFeatures.splice(idx, 1);
                    $card.removeClass('sjl-selected').attr('aria-checked', 'false');
                } else {
                    state.selectedFeatures.push(id);
                    $card.addClass('sjl-selected').attr('aria-checked', 'true');
                }
            });
    }

    function renderGenericStep(stepConfig) {
        state.currentStepConfig = stepConfig;
        var icon = stepConfig.icon ? stepConfig.icon + ' ' : '';
        setStepHeader(icon + stepConfig.label, stepConfig.description);
        setContent(buildFieldsHtml(stepConfig.fields));
        populateWizardFields(stepConfig.fields);
        // Apply initial conditional visibility based on already-stored values
        updateConditionalFields();
    }

    function renderSummary() {
        setStepHeader(
            __('Review your setup ✅', 'simple-jwt-login'),
            __('Click "Save Settings" to apply and save everything.', 'simple-jwt-login')
        );

        var html = '<div class="sjl-wizard-summary">';

        html += summarySection(SJL_WIZARD_GENERAL.label, null, SJL_WIZARD_GENERAL.fields);

        var hasFeatures = false;
        $.each(SJL_WIZARD_FEATURES, function (i, feature) {
            if (state.selectedFeatures.indexOf(feature.id) !== -1) {
                hasFeatures = true;
                html += summarySection(feature.icon + ' ' + feature.label, feature.tabIndex, feature.fields);
            }
        });

        if (!hasFeatures) {
            html += '<p class="sjl-wizard-summary-empty">' + escHtml(__('No features selected — only General settings will be applied.', 'simple-jwt-login')) + '</p>';
        }

        html += '</div>';
        setContent(html);
    }

    function summarySection(title, tabIndex, fields) {
        var tabAttr  = tabIndex ? ' data-tab-index="' + tabIndex + '" title="' + escAttr(__('Click to jump to this tab', 'simple-jwt-login')) + '"' : '';
        var clickCls = tabIndex ? ' sjl-summary-clickable' : '';
        var html = '<div class="sjl-wizard-summary-section">';
        html += '<div class="sjl-wizard-summary-title' + clickCls + '"' + tabAttr + '>' + escHtml(title);
        if (tabIndex) { html += ' <span class="sjl-summary-go">→</span>'; }
        html += '</div>';
        html += '<table class="sjl-wizard-summary-table">';

        $.each(fields, function (i, field) {
            // Skip fields that were hidden (conditional) during input
            if (field.showWhen && !evalShowWhen(field.showWhen)) { return; }

            var raw = state.values[field.name];
            var display;
            if (raw === undefined || raw === '') {
                display = '<em>' + escHtml(__('not set', 'simple-jwt-login')) + '</em>';
            } else if (field.sensitive || field.type === 'password') {
                display = '••••••••';
            } else if (field.type === 'radio') {
                display = escHtml(optionLabel(field, raw) || raw);
            } else if (field.type === 'checkbox') {
                display = (raw === '1' || raw === 1 || raw === true)
                    ? escHtml(__('Yes', 'simple-jwt-login'))
                    : escHtml(__('No', 'simple-jwt-login'));
            } else if (field.type === 'checkbox-group') {
                if (Array.isArray(raw) && raw.length) {
                    display = raw.map(function (v) { return escHtml(v); }).join(', ');
                } else {
                    display = '<em>' + escHtml(__('none selected', 'simple-jwt-login')) + '</em>';
                }
            } else {
                display = escHtml(raw);
            }

            html += '<tr>';
            html += '<td class="sjl-summary-key">' + escHtml(field.label) + '</td>';
            html += '<td class="sjl-summary-val">'  + display             + '</td>';
            html += '</tr>';
        });

        html += '</table></div>';
        return html;
    }

    // =========================================================================
    // CONDITIONAL FIELDS
    // =========================================================================

    /**
     * Evaluate a showWhen descriptor against the current state.values.
     * @param  {{ field: string, matches?: RegExp, notMatches?: RegExp }} cond
     * @returns {boolean}
     */
    function evalShowWhen(cond) {
        var val = String(state.values[cond.field] || '');
        if (cond.matches)    { return cond.matches.test(val);  }
        if (cond.notMatches) { return !cond.notMatches.test(val); }
        return true;
    }

    /**
     * Show/hide conditional field wrappers based on current state.values.
     * Called after a select-clone changes and after initial field render.
     */
    function updateConditionalFields() {
        if (!state.currentStepConfig) { return; }

        $.each(state.currentStepConfig.fields, function (i, field) {
            if (!field.showWhen) { return; }
            var $wrapper = $('#sjl-wizard-content .sjl-wizard-field[data-field-name="' + field.name + '"]');
            $wrapper.toggle(evalShowWhen(field.showWhen));
        });
    }

    // =========================================================================
    // FIELD RENDERING
    // =========================================================================

    function buildFieldsHtml(fields) {
        var html = '';
        $.each(fields, function (i, field) {
            // Use data-field-name on the wrapper so updateConditionalFields can find it
            var hidden = (field.showWhen && !evalShowWhen(field.showWhen)) ? ' style="display:none"' : '';
            html += '<div class="sjl-wizard-field" data-field-name="' + escAttr(field.name) + '"' + hidden + '>';
            if (field.type !== 'checkbox') {
                html += '<label class="sjl-wizard-label">' + escHtml(field.label) + '</label>';
            }
            if (field.help) {
                html += '<p class="sjl-wizard-help">' + escHtml(field.help) + '</p>';
            }
            html += buildInputHtml(field);
            html += '</div>';
        });
        return html;
    }

    function buildInputHtml(field) {
        var dfAttr = ' data-field="' + escAttr(field.name) + '" data-field-type="' + escAttr(field.type) + '"';

        switch (field.type) {
            case 'text':
            case 'password':
                return '<input type="' + field.type + '" class="sjl-wizard-input form-control"' + dfAttr +
                       ' placeholder="' + escAttr(field.placeholder || '') + '" />';

            case 'textarea':
                var rows = field.rows || 5;
                return '<textarea class="sjl-wizard-textarea form-control"' + dfAttr +
                       ' rows="' + rows + '"></textarea>';

            case 'radio':
                var html = '<div class="sjl-wizard-radios">';
                $.each(field.options, function (i, opt) {
                    var rid = 'sjl_r_' + field.name.replace(/\W/g, '_') + '_' + opt.value;
                    html += '<label class="sjl-wizard-radio" for="' + escAttr(rid) + '">';
                    html += '<input type="radio" id="' + escAttr(rid) + '"' + dfAttr +
                            ' name="' + escAttr(field.name) + '" value="' + escAttr(opt.value) + '" />';
                    html += '<span class="sjl-wizard-radio-text">' + escHtml(opt.label) + '</span>';
                    html += '</label>';
                });
                html += '</div>';
                return html;

            case 'checkbox':
                var cid = 'sjl_c_' + field.name.replace(/\W/g, '_');
                return '<label class="sjl-wizard-checkbox" for="' + escAttr(cid) + '">' +
                       '<input type="checkbox" id="' + escAttr(cid) + '"' + dfAttr + ' value="1" />' +
                       '<span class="sjl-wizard-checkbox-text">' + escHtml(field.label) + '</span>' +
                       '</label>';

            case 'checkbox-group':
                var cbHtml = '<div class="sjl-wizard-checkboxes">';
                $.each(field.options, function (i, opt) {
                    var cid = 'sjl_c_' + field.name.replace(/\W/g, '_') + '_' + opt.value;
                    cbHtml += '<label class="sjl-wizard-checkbox" for="' + escAttr(cid) + '">';
                    cbHtml += '<input type="checkbox" id="' + escAttr(cid) + '"' + dfAttr +
                              ' value="' + escAttr(opt.value) + '" />';
                    cbHtml += '<span class="sjl-wizard-checkbox-text">' + escHtml(opt.label) + '</span>';
                    cbHtml += '</label>';
                });
                cbHtml += '</div>';
                return cbHtml;

            case 'select-clone':
                var $src = $('#' + field.sourceId);
                var sel = '<select class="sjl-wizard-select form-control"' + dfAttr + '>';
                $src.find('option').each(function () {
                    sel += '<option value="' + escAttr($(this).val()) + '">' + escHtml($(this).text()) + '</option>';
                });
                sel += '</select>';
                return sel;

            default:
                return '';
        }
    }

    function populateWizardFields(fields) {
        $.each(fields, function (i, field) {
            var val = state.values[field.name];

            // For radios with no stored value, default to first option
            if (val === undefined && field.type === 'radio' && field.options && field.options.length) {
                val = field.options[0].value;
            }
            if (val === undefined) { return; }

            var dfSel = '[data-field="' + field.name + '"]';

            switch (field.type) {
                case 'text':
                case 'password':
                    $('#sjl-wizard-content .sjl-wizard-input' + dfSel).val(val);
                    break;
                case 'textarea':
                    $('#sjl-wizard-content .sjl-wizard-textarea' + dfSel).val(val);
                    break;
                case 'radio':
                    $('#sjl-wizard-content input[type="radio"]' + dfSel + '[value="' + val + '"]').prop('checked', true);
                    break;
                case 'checkbox':
                    $('#sjl-wizard-content input[type="checkbox"]' + dfSel).prop('checked', val === '1' || val === 1 || val === true);
                    break;
                case 'checkbox-group':
                    if (Array.isArray(val)) {
                        $('#sjl-wizard-content input[type="checkbox"]' + dfSel).prop('checked', false);
                        $.each(val, function (i, v) {
                            $('#sjl-wizard-content input[type="checkbox"]' + dfSel + '[value="' + v + '"]').prop('checked', true);
                        });
                    }
                    break;
                case 'select-clone':
                    $('#sjl-wizard-content .sjl-wizard-select' + dfSel).val(val);
                    break;
            }
        });
    }

    /**
     * Collect field values from the currently visible wizard step.
     * Skips fields whose wrapper is hidden (conditional fields not applicable
     * for the current algorithm/setting) so their stored values are preserved.
     */
    function collectStepValues() {
        // Reset only checkbox-group fields that are visible so unchecking all gives []
        $('#sjl-wizard-content input[type="checkbox"][data-field-type="checkbox-group"]').each(function () {
            var name     = $(this).data('field');
            var $wrapper = $(this).closest('.sjl-wizard-field');
            if ($wrapper.length && $wrapper.css('display') === 'none') { return; }
            state.values[name] = [];
        });

        $('#sjl-wizard-content [data-field]').each(function () {
            var $el      = $(this);
            var name     = $el.data('field');
            var $wrapper = $el.closest('.sjl-wizard-field');

            // Don't overwrite stored values for hidden conditional fields
            if ($wrapper.length && $wrapper.css('display') === 'none') {
                return; // continue
            }

            if ($el.is('input[type="radio"]')) {
                if ($el.is(':checked')) {
                    state.values[name] = $el.val();
                }
            } else if ($el.is('input[type="checkbox"]')) {
                if ($el.data('fieldType') === 'checkbox') {
                    state.values[name] = $el.is(':checked') ? '1' : '0';
                } else {
                    if (!Array.isArray(state.values[name])) {
                        state.values[name] = [];
                    }
                    if ($el.is(':checked')) {
                        state.values[name].push($el.val());
                    }
                }
            } else {
                state.values[name] = $el.val();
            }
        });
    }

    // =========================================================================
    // UI HELPERS
    // =========================================================================

    function setStepHeader(title, subtitle) {
        $('#sjl-wizard-step-title').text(title);
        $('#sjl-wizard-step-subtitle').text(subtitle || '');
    }

    function setContent(html) {
        $('#sjl-wizard-content').html(html);
    }

    function updateProgress() {
        var total   = Math.max(state.steps.length - 1, 1);
        var current = state.currentStep;
        var pct     = (current === 0) ? 0 : Math.round((current / total) * 100);

        $('#sjl-wizard-progress-fill').css('width', pct + '%');

        if (current === 0) {
            $('#sjl-wizard-progress-label').text(__('Step 1 — Select features', 'simple-jwt-login'));
        } else {
            /* translators: 1: current step number, 2: total step count */
            $('#sjl-wizard-progress-label').text(sprintf(__('Step %1$d of %2$d', 'simple-jwt-login'), current + 1, total + 1));
        }
    }

    function updateFooter() {
        var stepId    = state.steps[state.currentStep];
        var isFirst   = state.currentStep === 0;
        var isSummary = stepId === 'summary';

        $('#sjl-wizard-btn-prev').toggleClass('sjl-hidden', isFirst);
        $('#sjl-wizard-btn-next').toggleClass('sjl-hidden', isSummary);
        $('#sjl-wizard-btn-finish').toggleClass('sjl-hidden', !isSummary);
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    function featureById(id) {
        var found = null;
        $.each(SJL_WIZARD_FEATURES, function (i, f) {
            if (f.id === id) { found = f; return false; }
        });
        return found;
    }

    function optionLabel(field, value) {
        var label = null;
        $.each(field.options || [], function (i, opt) {
            if (String(opt.value) === String(value)) { label = opt.label; return false; }
        });
        return label;
    }

    function escAttr(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

})(jQuery);
