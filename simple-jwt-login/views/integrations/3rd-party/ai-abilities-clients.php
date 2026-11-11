<?php

if (!defined('ABSPATH')) {
    /**
     * @phpstan-ignore-next-line
     */
    exit;
}

/**
 * Tabbed connection examples, one tab per AI client. Included from
 * ai-abilities.php (inside its closure), once per connection option.
 *
 * @var string $mcpClientTransport 'stdio' (WP-CLI) or 'http' (Application Password).
 * @var string $mcpUserLogin
 * @var string $mcpWpPath
 * @var string $mcpEndpoint
 */

$mcpJsonFlags   = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
$mcpServe       = 'mcp-adapter serve --server=mcp-adapter-default-server --user=' . $mcpUserLogin;
$mcpStdioArgs   = [
    'mcp-adapter',
    'serve',
    '--server=mcp-adapter-default-server',
    '--user=' . $mcpUserLogin,
    '--path=' . $mcpWpPath,
];
$mcpBasicHeader = 'Basic BASE64_CREDENTIALS';
$mcpBase64Line  = "printf '%s' '" . $mcpUserLogin . ":APPLICATION_PASSWORD' | base64 | tr -d '\\n'";

/*
 * Each tab: slug => [tab label, [[example label, code, multi-line file?], ...]].
 */
$mcpTabs = [];

if ($mcpClientTransport === 'stdio') {
    $mcpServeCommands = [
        __('Same machine:', 'simple-jwt-login') => 'wp ' . $mcpServe . ' --path=' . $mcpWpPath,
        __('Remote server over SSH (replace user@your-server):', 'simple-jwt-login') => 'ssh user@your-server wp ' . $mcpServe . ' --path=' . $mcpWpPath,
        __('WordPress in Docker (replace wordpress with your container name):', 'simple-jwt-login') => 'docker exec -i wordpress wp ' . $mcpServe . ' --allow-root --path=' . $mcpWpPath,
    ];
    $mcpCliExamples = function ($cliPrefix) use ($mcpServeCommands) {
        $examples = [];
        foreach ($mcpServeCommands as $label => $serveCommand) {
            $examples[] = [$label, $cliPrefix . ' ' . $serveCommand, false];
        }

        return $examples;
    };
    $mcpStdioServer = ['command' => 'wp', 'args' => $mcpStdioArgs];
    $mcpServersJson = (string) wp_json_encode(['mcpServers' => ['my-site' => $mcpStdioServer]], $mcpJsonFlags);

    $mcpTabs = [
        'claude-code'    => ['Claude Code', $mcpCliExamples('claude mcp add my-site --')],
        'codex'          => ['Codex', $mcpCliExamples('codex mcp add my-site --')],
        'cursor'         => ['Cursor', [
            [__('.cursor/mcp.json (this project) or ~/.cursor/mcp.json (all projects):', 'simple-jwt-login'), $mcpServersJson, true],
        ]],
        'gemini'         => ['Gemini CLI', [
            [__('~/.gemini/settings.json:', 'simple-jwt-login'), $mcpServersJson, true],
        ]],
        'vscode'         => ['VS Code / Copilot', [
            [
                __('.vscode/mcp.json:', 'simple-jwt-login'),
                (string) wp_json_encode(
                    ['servers' => ['my-site' => array_merge(['type' => 'stdio'], $mcpStdioServer)]],
                    $mcpJsonFlags
                ),
                true,
            ],
        ]],
        'claude-desktop' => ['Claude Desktop', [
            [__('claude_desktop_config.json (Settings > Developer > Edit Config):', 'simple-jwt-login'), $mcpServersJson, true],
        ]],
    ];
}

if ($mcpClientTransport === 'http') {
    $mcpHeaders     = ['Authorization' => $mcpBasicHeader];
    $mcpBase64Hint  = [
        __('BASE64_CREDENTIALS is the output of (replace APPLICATION_PASSWORD):', 'simple-jwt-login'),
        $mcpBase64Line,
        false,
    ];

    $mcpTabs = [
        'claude-code' => ['Claude Code', [
            [
                __('Replace APPLICATION_PASSWORD:', 'simple-jwt-login'),
                'claude mcp add --transport http my-site ' . $mcpEndpoint
                    . ' --header "Authorization: Basic $(' . $mcpBase64Line . ')"',
                false,
            ],
        ]],
        'codex'       => ['Codex', [
            $mcpBase64Hint,
            [
                __('~/.codex/config.toml:', 'simple-jwt-login'),
                "[mcp_servers.my-site]\nurl = \"" . $mcpEndpoint . "\"\nhttp_headers = { \"Authorization\" = \"" . $mcpBasicHeader . '" }',
                true,
            ],
        ]],
        'cursor'      => ['Cursor', [
            $mcpBase64Hint,
            [
                __('.cursor/mcp.json (this project) or ~/.cursor/mcp.json (all projects):', 'simple-jwt-login'),
                (string) wp_json_encode(
                    ['mcpServers' => ['my-site' => ['url' => $mcpEndpoint, 'headers' => $mcpHeaders]]],
                    $mcpJsonFlags
                ),
                true,
            ],
        ]],
        'gemini'      => ['Gemini CLI', [
            [
                __('Replace APPLICATION_PASSWORD:', 'simple-jwt-login'),
                'gemini mcp add --transport http --header "Authorization: Basic $(' . $mcpBase64Line . ')" my-site ' . $mcpEndpoint,
                false,
            ],
        ]],
        'vscode'      => ['VS Code / Copilot', [
            $mcpBase64Hint,
            [
                __('.vscode/mcp.json:', 'simple-jwt-login'),
                (string) wp_json_encode(
                    ['servers' => ['my-site' => ['type' => 'http', 'url' => $mcpEndpoint, 'headers' => $mcpHeaders]]],
                    $mcpJsonFlags
                ),
                true,
            ],
        ]],
    ];
}

$mcpTabPrefix = 'sjl-mcp-' . $mcpClientTransport . '-';
$mcpFirstTab  = true;
?>
<div class="sjl-client-tabs">
    <div class="sjl-client-tablist" role="tablist" aria-label="<?php echo esc_attr__('AI client', 'simple-jwt-login'); ?>">
        <?php foreach ($mcpTabs as $mcpSlug => $mcpTab) : ?>
            <button type="button"
                    class="sjl-client-tab<?php echo $mcpFirstTab ? ' active' : ''; ?>"
                    role="tab"
                    id="<?php echo esc_attr($mcpTabPrefix . $mcpSlug . '-tab'); ?>"
                    aria-controls="<?php echo esc_attr($mcpTabPrefix . $mcpSlug); ?>"
                    aria-selected="<?php echo $mcpFirstTab ? 'true' : 'false'; ?>"
                    tabindex="<?php echo $mcpFirstTab ? '0' : '-1'; ?>"
                    data-sjl-client="<?php echo esc_attr($mcpSlug); ?>">
                <?php echo esc_html($mcpTab[0]); ?>
            </button>
            <?php $mcpFirstTab = false; ?>
        <?php endforeach; ?>
    </div>
    <?php $mcpFirstTab = true; ?>
    <?php foreach ($mcpTabs as $mcpSlug => $mcpTab) : ?>
        <div class="sjl-client-panel"
             role="tabpanel"
             id="<?php echo esc_attr($mcpTabPrefix . $mcpSlug); ?>"
             aria-labelledby="<?php echo esc_attr($mcpTabPrefix . $mcpSlug . '-tab'); ?>"
             <?php echo $mcpFirstTab ? '' : 'hidden'; ?>>
            <?php foreach ($mcpTab[1] as $mcpExample) : ?>
                <div class="sjl-gen-url-example">
                    <p class="sjl-gen-url-example-label"><?php echo esc_html($mcpExample[0]); ?></p>
                    <div class="generated-code sjl-code-command<?php echo $mcpExample[2] ? ' sjl-code-file' : ''; ?>">
                        <span class="code"><?php echo esc_html($mcpExample[1]); ?></span>
                        <span class="copy-button">
                            <button class="btn btn-secondary btn-xs"><?php echo esc_html__('Copy', 'simple-jwt-login'); ?></button>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if ($mcpClientTransport === 'stdio' && $mcpTab[1][0][2]) : ?>
                <p class="description">
                    <?php echo esc_html__(
                        'Over SSH, set "command" to "ssh" and start "args" with "user@your-server", "wp". In Docker, set "command" to "docker", start "args" with "exec", "-i", "wordpress", "wp" and add "--allow-root".',
                        'simple-jwt-login'
                    ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php $mcpFirstTab = false; ?>
    <?php endforeach; ?>
</div>
