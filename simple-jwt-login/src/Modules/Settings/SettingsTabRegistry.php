<?php

namespace SimpleJWTLogin\Modules\Settings;

/**
 * Single source of truth for the admin settings tabs.
 *
 * Each tab is declared once here with everything the admin layout needs: the
 * SettingsErrors prefix (its stable index), the DOM id, the tab title, the view
 * file, whether it can surface a validation error, and its place in the sidebar
 * (top-level item or grouped, with icon and optional short label).
 *
 * views/layout.php derives the page list, the view map and the sidebar tree from
 * this definition instead of maintaining three hand-synced arrays. To add a tab,
 * add one node here.
 */
class SettingsTabRegistry
{
    /**
     * Ordered sidebar definition. Top-level entries are either a single tab
     * ('item') or a labelled 'group' containing tabs. Every tab leaf carries the
     * full metadata used across the layout.
     *
     * Leaf keys:
     *  - index        SettingsErrors::PREFIX_* (stable identifier)
     *  - id           DOM id used for the tab pane / nav link
     *  - name         Tab title (translated)
     *  - view         View file rendered for the tab
     *  - check_error  Whether this tab can highlight a validation error
     *  - icon         Dashicon class for the sidebar
     *  - sidebar_name Optional shorter sidebar label (defaults to name)
     *
     * @return array<int, array>
     */
    protected static function definition()
    {
        return [
            self::item(
                SettingsErrors::PREFIX_DASHBOARD,
                'simple-jwt-login-tab-dashboard',
                __('Dashboard', 'simple-jwt-login'),
                'dashboard-view.php',
                true,
                'dashicons-dashboard'
            ),
            self::item(
                SettingsErrors::PREFIX_GENERAL,
                'simple-jwt-login-tab-general',
                __('General', 'simple-jwt-login'),
                'general-view.php',
                true,
                'dashicons-admin-settings'
            ),
            [
                'type'  => 'group',
                'label' => __('Routes', 'simple-jwt-login'),
                'icon'  => 'dashicons-networking',
                'items' => [
                    self::leaf(
                        SettingsErrors::PREFIX_LOGIN,
                        'simple-jwt-login-tab-login',
                        __('Login', 'simple-jwt-login'),
                        'routes/login-view.php',
                        true,
                        'dashicons-admin-users'
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_REGISTER,
                        'simple-jwt-login-tab-register',
                        __('Register User', 'simple-jwt-login'),
                        'routes/register-view.php',
                        true,
                        'dashicons-plus-alt',
                        __('Register', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_DELETE,
                        'simple-jwt-login-tab-delete',
                        __('Delete User', 'simple-jwt-login'),
                        'routes/delete-view.php',
                        true,
                        'dashicons-trash',
                        __('Delete', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_RESET_PASSWORD,
                        'simple-jwt-login-tab-reset-password',
                        __('Reset Password', 'simple-jwt-login'),
                        'routes/reset-password-view.php',
                        true,
                        'dashicons-lock'
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_AUTHENTICATION,
                        'auth-tab-login',
                        __('Authentication', 'simple-jwt-login'),
                        'routes/auth-view.php',
                        true,
                        'dashicons-shield',
                        __('Authenticate', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_REFRESH_TOKEN,
                        'simple-jwt-login-tab-refresh-token',
                        __('Refresh Token', 'simple-jwt-login'),
                        'routes/refresh-token-view.php',
                        true,
                        'dashicons-update',
                        __('Refresh Token', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_VALIDATE_TOKEN,
                        'simple-jwt-login-tab-validate-token',
                        __('Validate Token', 'simple-jwt-login'),
                        'routes/validate-token-view.php',
                        false,
                        'dashicons-yes-alt',
                        __('Validate Token', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_REVOKE_TOKEN,
                        'simple-jwt-login-tab-revoke-token',
                        __('Revoke Token', 'simple-jwt-login'),
                        'routes/revoke-token-view.php',
                        false,
                        'dashicons-dismiss',
                        __('Revoke Token', 'simple-jwt-login')
                    ),
                ],
            ],
            [
                'type'  => 'group',
                'label' => __('Security', 'simple-jwt-login'),
                'icon'  => 'dashicons-shield',
                'items' => [
                    self::leaf(
                        SettingsErrors::PREFIX_AUTH_CODES,
                        'simple-jwt-login-tab-auth-codes',
                        __('Auth Codes', 'simple-jwt-login'),
                        'security/auth-codes-view.php',
                        true,
                        'dashicons-tickets-alt'
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_PROTECT_ENDPOINTS,
                        'simple-jwt-login-tab-protect-endpoints',
                        __('Protect endpoints', 'simple-jwt-login'),
                        'security/protect-endpoints-view.php',
                        true,
                        'dashicons-shield-alt',
                        __('Protect Endpoints', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_CORS,
                        'simple-jwt-login-tab-cors',
                        __('CORS', 'simple-jwt-login'),
                        'security/cors-view.php',
                        true,
                        'dashicons-randomize'
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_API_KEYS,
                        'simple-jwt-login-tab-api-keys',
                        __('API Keys', 'simple-jwt-login'),
                        'security/api-keys-view.php',
                        false,
                        'dashicons-admin-network',
                        __('API Keys', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_REVOKED_TOKENS,
                        'simple-jwt-login-tab-revoked-tokens',
                        __('Revoked Tokens', 'simple-jwt-login'),
                        'security/revoked-tokens-view.php',
                        false,
                        'dashicons-dismiss',
                        __('Revoked Tokens', 'simple-jwt-login')
                    ),
                ],
            ],
            [
                'type'  => 'group',
                'label' => __('Integrations', 'simple-jwt-login'),
                'icon'  => 'dashicons-admin-plugins',
                'items' => [
                    self::leaf(
                        SettingsErrors::PREFIX_APPLICATIONS,
                        'simple-jwt-login-tab-integrations',
                        __('OAuth', 'simple-jwt-login'),
                        'integrations/oauth/oauth-apps.php',
                        true,
                        'dashicons-cloud',
                        __('OAuth', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_3RD_PARTY_APPS,
                        'simple-jwt-login-tab-3rd-party-apps',
                        __('Third Party Integrations', 'simple-jwt-login'),
                        'integrations/3rd-party/3rd-party-apps.php',
                        false,
                        'dashicons-admin-plugins',
                        __('Third Party Integrations', 'simple-jwt-login')
                    ),
                ],
            ],
            [
                'type'  => 'group',
                'label' => __('Webhooks', 'simple-jwt-login'),
                'icon'  => 'dashicons-rest-api',
                'items' => [
                    self::leaf(
                        SettingsErrors::PREFIX_WEBHOOKS,
                        'simple-jwt-login-tab-webhooks',
                        __('Webhooks', 'simple-jwt-login'),
                        'webhooks/config.php',
                        true,
                        'dashicons-admin-settings',
                        __('Config', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_WEBHOOK_LOGS,
                        'simple-jwt-login-tab-webhook-logs',
                        __('Webhook Logs', 'simple-jwt-login'),
                        'webhooks/logs-view.php',
                        false,
                        'dashicons-list-view',
                        __('Logs', 'simple-jwt-login')
                    ),
                ],
            ],
            [
                'type'  => 'group',
                'label' => __('Audit Logs', 'simple-jwt-login'),
                'icon'  => 'dashicons-backup',
                'items' => [
                    self::leaf(
                        SettingsErrors::PREFIX_AUDIT_LOGS,
                        'simple-jwt-login-tab-audit-logs',
                        __('Audit Logs', 'simple-jwt-login'),
                        'audit-logs/config.php',
                        true,
                        'dashicons-admin-settings',
                        __('Config', 'simple-jwt-login')
                    ),
                    self::leaf(
                        SettingsErrors::PREFIX_AUDIT_LOG_LOGS,
                        'simple-jwt-login-tab-audit-log-logs',
                        __('Audit Log Entries', 'simple-jwt-login'),
                        'audit-logs/logs-view.php',
                        false,
                        'dashicons-list-view',
                        __('Logs', 'simple-jwt-login')
                    ),
                ],
            ],
            self::item(
                SettingsErrors::PREFIX_HOOKS,
                'simple-jwt-login-tab-hooks',
                __('Hooks', 'simple-jwt-login'),
                'hooks-view.php',
                true,
                'dashicons-admin-plugins',
                __('Hooks', 'simple-jwt-login')
            ),
            self::item(
                SettingsErrors::PREFIX_JWT_DECODER,
                'simple-jwt-login-tab-jwt-decoder',
                __('JWT Decoder', 'simple-jwt-login'),
                'jwt-decoder-view.php',
                false,
                'dashicons-editor-code',
                __('JWT Decoder', 'simple-jwt-login')
            ),
        ];
    }

    /**
     * Page descriptors used to build $settingsPages and the tab panes.
     * Ordered as the tabs appear in the sidebar; Dashboard stays first.
     *
     * @return array<int, array> Each: ['index','id','name','check_error'].
     */
    public static function pages()
    {
        $pages = [];
        foreach (self::leaves() as $leaf) {
            $pages[] = [
                'index'       => $leaf['index'],
                'id'          => $leaf['id'],
                'name'        => $leaf['name'],
                'check_error' => $leaf['check_error'],
            ];
        }

        return $pages;
    }

    /**
     * Map of tab index => view file.
     *
     * @return array<int, string>
     */
    public static function views()
    {
        $views = [];
        foreach (self::leaves() as $leaf) {
            $views[$leaf['index']] = $leaf['view'];
        }

        return $views;
    }

    /**
     * Sidebar navigation tree in the shape expected by views/layout.php.
     *
     * @return array<int, array>
     */
    public static function sidebar()
    {
        $sidebar = [];
        foreach (self::definition() as $node) {
            if (isset($node['type']) && $node['type'] === 'group') {
                $sidebar[] = [
                    'type'  => 'group',
                    'label' => $node['label'],
                    'icon'  => $node['icon'],
                    'items' => array_map([__CLASS__, 'sidebarItem'], $node['items']),
                ];
                continue;
            }
            $sidebar[] = ['type' => 'item'] + self::sidebarItem($node);
        }

        return $sidebar;
    }

    /**
     * Flatten the definition into an ordered list of tab leaves.
     *
     * @return array<int, array>
     */
    protected static function leaves()
    {
        $leaves = [];
        foreach (self::definition() as $node) {
            if (isset($node['type']) && $node['type'] === 'group') {
                foreach ($node['items'] as $item) {
                    $leaves[] = $item;
                }
                continue;
            }
            $leaves[] = $node;
        }

        return $leaves;
    }

    /**
     * Reduce a tab leaf to the sidebar item shape: index + icon, plus a name
     * override only when it differs from the page title (matching legacy output).
     *
     * @param array $leaf
     * @return array
     */
    protected static function sidebarItem($leaf)
    {
        $item = [
            'index' => $leaf['index'],
            'icon'  => $leaf['icon'],
        ];
        if (isset($leaf['sidebar_name'])) {
            $item['name'] = $leaf['sidebar_name'];
        }

        return $item;
    }

    /**
     * Build a top-level tab leaf (rendered as a single sidebar item).
     *
     * @param int $index
     * @param string $tabId
     * @param string $name
     * @param string $view
     * @param bool $checkError
     * @param string $icon
     * @param string|null $sidebarName
     * @return array
     */
    protected static function item($index, $tabId, $name, $view, $checkError, $icon, $sidebarName = null)
    {
        return ['type' => 'item'] + self::leaf($index, $tabId, $name, $view, $checkError, $icon, $sidebarName);
    }

    /**
     * Build a tab leaf node.
     *
     * @param int $index
     * @param string $tabId
     * @param string $name
     * @param string $view
     * @param bool $checkError
     * @param string $icon
     * @param string|null $sidebarName
     * @return array
     */
    protected static function leaf($index, $tabId, $name, $view, $checkError, $icon, $sidebarName = null)
    {
        $leaf = [
            'index'       => $index,
            'id'          => $tabId,
            'name'        => $name,
            'view'        => $view,
            'check_error' => $checkError,
            'icon'        => $icon,
        ];
        if ($sidebarName !== null) {
            $leaf['sidebar_name'] = $sidebarName;
        }

        return $leaf;
    }
}
