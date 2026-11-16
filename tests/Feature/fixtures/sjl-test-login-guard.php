<?php
/**
 * Plugin Name: Simple JWT Login - test login guard
 * Description: Feature test fixture copied to mu-plugins by LoginProtectionTest. Behaves like a
 *              brute-force / user-block plugin. Inert unless the "sjl_test_login_guard" option is set.
 */

/**
 * @return array{authenticate?: string[], wp_authenticate_user?: string[]}|null
 */
function sjl_test_login_guard_config()
{
    $config = json_decode((string) get_option('sjl_test_login_guard', ''), true);

    return is_array($config) ? $config : null;
}

// Lockout plugins (Limit Login Attempts, Wordfence) block on "authenticate".
add_filter('authenticate', function ($user, $username) {
    $config = sjl_test_login_guard_config();
    if ($config !== null && in_array($username, $config['authenticate'] ?? [], true)) {
        return new WP_Error('sjl_test_locked', 'Locked by the login guard.');
    }

    return $user;
}, 99, 2);

// User-block / "pending approval" plugins check the user on "wp_authenticate_user".
add_filter('wp_authenticate_user', function ($user) {
    $config = sjl_test_login_guard_config();
    if ($config !== null
        && $user instanceof WP_User
        && in_array($user->user_login, $config['wp_authenticate_user'] ?? [], true)
    ) {
        return new WP_Error('sjl_test_blocked', 'Blocked by the login guard.');
    }

    return $user;
});

// Lockout plugins count failures on "wp_login_failed".
add_action('wp_login_failed', function ($username) {
    if (sjl_test_login_guard_config() === null) {
        return;
    }
    $failed = json_decode((string) get_option('sjl_test_login_failed', '[]'), true);
    $failed[] = $username;
    update_option('sjl_test_login_failed', wp_json_encode($failed), false);
});
