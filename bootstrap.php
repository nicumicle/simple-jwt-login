<?php
define('ABSPATH', 'PHPunit');
define('WP_PLUGIN_DIR', 'PHPunit/plugins');
error_reporting(E_ALL);

require_once "simple-jwt-login/autoload.php";
require_once "vendor/autoload.php";

if (! function_exists('__')) {
    function __($text, $domain)
    {
        if ($domain === null) {
            throw new Exception('Missing domain.');
        }
        return $text;
    }
}
if (! function_exists('esc_html')) {
    function esc_html($text)
    {
        return $text;
    }
}

if (!class_exists('WP_User')) {
    class WP_User
    {
        /** @var int */
        public $ID = 0;
        public $user_login;
        /** @var string */
        public $user_email = '';
        public $roles = [];

        public function __construct($user)
        {
        }

        public function set_role($role)
        {
            $this->roles = [$role];
        }

        public function add_role($role)
        {
            $this->roles[] = $role;
        }

        public function get($param)
        {
            return "";
        }

        public static function to_array()
        {
            return [];
        }
    }
}

if (!function_exists('delete_user_meta')) {
    function delete_user_meta($userId, $metaKey, $metaValue)
    {
    }
}

if (!function_exists('add_user_meta')) {
    function add_user_meta($userId, $metaKey, $metaValue, $unique)
    {
    }
}
if (!function_exists('get_user_meta')) {
    function get_user_meta($userId, $metaKey, $metaValue)
    {
    }
}

if (!function_exists('update_user_meta')) {
    function update_user_meta($userId, $metaKey, $metaValue, $prevValue = '')
    {
    }
}


if (!function_exists('do_action')) {
    function do_action($actionName, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null)
    {
    }
}
if (!function_exists('add_option')) {
    function add_option($actionName, $arg1, $arg2 = null)
    {
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value = '', $deprecated = '', $autoload = 'yes')
    {
    }
}
if (!function_exists('username_exists')) {
    function username_exists($username)
    {
    }
}
if (!function_exists('email_exists')) {
    function email_exists($email)
    {
    }
}
if (!function_exists('get_userdata')) {
    function get_userdata($userID)
    {
    }
}
if (!function_exists('get_user_by')) {
    function get_user_by($field, $value)
    {
    }
}
if (!function_exists('wp_set_current_user')) {
    function wp_set_current_user($userId)
    {
    }
}
if (!function_exists('wp_set_auth_cookie')) {
    function wp_set_auth_cookie($userId, $remember = false, $secure = '', $token = '')
    {
    }
}
if (!function_exists('is_ssl')) {
    function is_ssl()
    {
        return false;
    }
}
if (!function_exists('wp_redirect')) {
    function wp_redirect($url)
    {
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($url)
    {
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '')
    {
        return $path;
    }
}
if (!function_exists('site_url')) {
    function site_url()
    {
        return 'test';
    }
}
if (!function_exists('esc_html')) {
    function esc_html($parameter)
    {
        return $parameter;
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($parameter)
    {
        return $parameter;
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($parameter, $domain)
    {
        return $parameter;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($parameter)
    {
        return $parameter;
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($parameter)
    {
        return $parameter;
    }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url, $component = -1)
    {
        return parse_url($url, $component);
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text)
    {
        return $text;
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses($string, $allowed_html)
    {
        return $string;
    }
}

if (!function_exists('nl2br')) {
    function nl2br($string)
    {
        return str_replace("\n", '<br />', $string);
    }
}

if (!function_exists('wp_insert_user')) {
    function wp_insert_user($userParameters)
    {
    }
}
if (!function_exists('get_option')) {
    function get_option($optionName)
    {
    }
}
if (!function_exists('is_email')) {
    function is_email($value)
    {
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return $value;
    }
}
if (!function_exists('wp_delete_user')) {
    function wp_delete_user($value)
    {
    }
}

if (!function_exists('wp_check_password')) {
    function wp_check_password($password, $dbPassword)
    {
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($name, $option1 = null, $option2 = null, $option3 = null, $option4 = null)
    {
    }
}

if (!function_exists('check_password_reset_key')) {
    function check_password_reset_key($code, $email)
    {
    }
}

if (!function_exists('get_password_reset_key')) {
    function get_password_reset_key($user)
    {
    }
}
if (!function_exists('retrieve_password')) {
    function retrieve_password($username)
    {
    }
}

if (!function_exists('wp_email')) {
    function wp_mail($sendTo, $emailSubject, $emailBody, $headers = [])
    {
    }
}

if (!function_exists('wp_new_user_notification')) {
    function wp_new_user_notification($userId, $deprecated = null, $notify = '', $password = '')
    {
    }
}

if (!function_exists('reset_password')) {
    function reset_password($user, $newPassword)
    {
    }
}

if (!function_exists('wp_password_change_notification')) {
    function wp_password_change_notification($user)
    {
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($nonceName)
    {
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonceValue, $nonceName)
    {
        return true;
    }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer($action = -1, $queryArg = '_wpnonce')
    {
        return 1;
    }
}

if (!function_exists('absint')) {
    function absint($maybeint)
    {
        return abs((int) $maybeint);
    }
}

if (!function_exists('wp_generate_password')) {
    /**
     * @param int $length
     * @param bool $specialChar
     * @param false $extraSpecialChars
     * @return string
     */
    function wp_generate_password($length = 12, $specialChar = true, $extraSpecialChars = false)
    {
        return '';
    }
}

if (!function_exists('role_exists')) {
    /**
     * @param string $role
     * @return bool
     */
    function role_exists($role)
    {
        return true;
    }
}

if (!function_exists('plugin_dir_path')) {
    /**
     * @param string|null $path
     * @return string
     */
    function plugin_dir_path($path = null)
    {
        return "";
    }
}

if (!function_exists('plugin_dir_url')) {
    /**
     * @param string $file
     * @return string
     */
    function plugin_dir_url($file)
    {
        return '';
    }
}
if (!function_exists('delete_option')) {
    /**
     * @param string $option
     * @return bool
     */
    function delete_option($option)
    {
        return true;
    }
}

if (!class_exists('wpdb')) {
    class wpdb
    {
        /** @var string */
        public $prefix = '';

        /** @var int */
        public $insert_id = 0;

        /**
         * @param string $table
         * @param array  $data
         * @param array  $format
         * @return int|false
         */
        public function insert($table, $data, $format = [])
        {
            return false;
        }

        /**
         * @param string $query
         * @return string
         */
        public function prepare($query, ...$args)
        {
            return '';
        }

        /**
         * @param string $query
         * @return object|null
         */
        public function get_row($query)
        {
            return null;
        }

        /**
         * @param string $query
         * @return string|null
         */
        public function get_var($query)
        {
            return null;
        }

        /**
         * @param string $query
         * @param string $output
         * @return array|null
         */
        public function get_results($query, $output = 'OBJECT')
        {
            return null;
        }

        /**
         * @param string     $table
         * @param array      $data
         * @param array|null $where
         * @param array|null $format
         * @param array|null $where_format
         * @return int|false
         */
        public function update($table, $data, $where, $format = null, $where_format = null)
        {
            return false;
        }

        /**
         * @param string $table
         * @param array  $where
         * @param array  $where_format
         * @return int|false
         */
        public function delete($table, $where, $where_format = [])
        {
            return false;
        }

        /**
         * @param string $query
         * @return int|bool
         */
        public function query($query)
        {
            return false;
        }

        /**
         * @return string
         */
        public function get_charset_collate()
        {
            return '';
        }

        /**
         * @param string $text
         * @return string
         */
        public function esc_like($text)
        {
            return addcslashes($text, '_%\\');
        }
    }
}

if (!function_exists('register_activation_hook')) {
    /**
     * @param string   $file
     * @param callable $callback
     * @return void
     */
    function register_activation_hook($file, $callback)
    {
    }
}

if (!function_exists('register_deactivation_hook')) {
    /**
     * @param string   $file
     * @param callable $callback
     * @return void
     */
    function register_deactivation_hook($file, $callback)
    {
    }
}

if (!function_exists('wp_next_scheduled')) {
    /**
     * @param string $hook
     * @param array  $args
     * @return int|false
     */
    function wp_next_scheduled($hook, $args = [])
    {
        return false;
    }
}

if (!function_exists('wp_schedule_event')) {
    /**
     * @param int    $timestamp
     * @param string $recurrence
     * @param string $hook
     * @param array  $args
     * @return bool|WP_Error
     */
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = [])
    {
        return true;
    }
}

if (!function_exists('wp_clear_scheduled_hook')) {
    /**
     * @param string $hook
     * @param array  $args
     * @return int|false
     */
    function wp_clear_scheduled_hook($hook, $args = [])
    {
        return false;
    }
}

if (!function_exists('dbDelta')) {
    /**
     * @param string|string[] $queries
     * @param bool            $execute
     * @return array
     */
    function dbDelta($queries = '', $execute = true)
    {
        return [];
    }
}

if (!function_exists('register_uninstall_hook')) {
    /**
     * @param string|null $file
     * @param string|null $callable
     */
    function register_uninstall_hook($file, $callable)
    {
    }
}

if (!function_exists('add_action')) {
    /**
     * @param string $hook_name
     * @param callable $callback
     * @param int|float $priority
     * @param int $accepted_args
     * @return void
     */
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
    }
}

if (!function_exists('fastcgi_finish_request')) {
    /**
     * @return bool
     */
    function fastcgi_finish_request()
    {
        return true;
    }
}

if (!function_exists('add_filter')) {
    /**
     * @param string $hook_name
     * @param callable $callback
     * @param int $priority
     * @param int $accepted_args
     * @return true
     */
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        return true;
    }
}

if (!function_exists('plugins_url')) {
    /**
     * @param string $path
     * @param string $plugin
     * @return string
     */
    function plugins_url($path = '', $plugin = '')
    {
        return  '';
    }
}

if (!function_exists('add_menu_page')) {
    /**
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param callable $callback
     * @param string $icon_url
     * @param int|float|null $position
     * @return string
     */
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null)
    {
        return '';
    }
}

if (!function_exists('load_plugin_textdomain')) {
    /**
     * @param string $domain
     * @param string|false $deprecated
     * @param string|false $plugin_rel_path
     * @return bool
     */
    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false)
    {
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    /**
     * @param string $handle
     * @param string $src
     * @param string[] $deps
     * @param string|bool|null $ver
     * @param string $media
     * @return void
     */
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
    {
    }
}

if (!function_exists('wp_enqueue_script')) {
    /**
     * @param string $handle
     * @param string $src
     * @param string[] $deps
     * @param string|bool|null $ver
     * @param bool $in_footer
     * @return void
     */
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false)
    {
    }
}

if (!function_exists('wp_set_script_translations')) {
    /**
     * @param string $handle
     * @param string $domain
     * @param string|null $path
     * @return void
     */
    function wp_set_script_translations($handle, $domain, $path = null)
    {
    }
}

if (!function_exists('plugin_basename')) {
    /**
     * @param string $file
     * @return string
     */
    function plugin_basename($file)
    {
        return '';
    }
}

if (!function_exists('add_shortcode')) {
    /**
     * @param string $tag
     * @param callable $callback
     * @return void
     */
    function add_shortcode($tag, $callback)
    {
    }
}

if (!function_exists('get_plugin_data')) {
    /**
     * @param string $plugin_file
     * @param bool $markup
     * @param bool $translate
     * @return array
     */
    function get_plugin_data($plugin_file, $markup = true, $translate = true)
    {
        return [];
    }
}

if (!function_exists('wp_send_json_error')) {
    /**
     * @param mixed|null $data
     * @param int|null $status_code
     * @param int $options
     * @return void
     */
    function wp_send_json_error($data = null, $status_code = null, $options = 0)
    {
    }
}

if (!function_exists('wp_send_json_success')) {
    /**
     * @param mixed|null $data
     * @param int|null $status_code
     * @param int $options
     * @return void
     */
    function wp_send_json_success($data = null, $status_code = null, $options = 0)
    {
    }
}

if (!function_exists('check_ajax_referer')) {
    /**
     * @param string $action
     * @param string|false $query_arg
     * @param bool $die
     * @return int|false
     */
    function check_ajax_referer($action = -1, $query_arg = false, $die = true)
    {
        return 1;
    }
}

if (!function_exists('home_url')) {
    /**
     * @param string $path
     * @param string|null $scheme
     * @return string
     */
    function home_url($path = '', $scheme = null)
    {
        return '';
    }
}

if (!function_exists('wp_login_url')) {
    function wp_login_url()
    {
    }
}
if (!function_exists('login_header')) {
    function login_header($title = 'Log In', $message = '', $wp_error = null)
    {
    }
}
if (!function_exists('login_footer')) {
    function login_footer($input_id = '', $extra_html = '')
    {
    }
}

if (!function_exists('register_rest_route')) {
    /**
     * @param string $namespace
     * @param string $route
     * @param array $args
     * @param bool $override
     * @return bool
     */
    function register_rest_route($namespace, $route, $args = array(), $override = false)
    {
        return true;
    }
}

if (!function_exists('is_user_logged_in')) {
    /**
     * @return bool
     */
    function is_user_logged_in()
    {
        return false;
    }
}

if (!function_exists('is_admin')) {
    /**
     * @return bool
     */
    function is_admin()
    {
        return false;
    }
}

if (!function_exists('current_user_can')) {
    /**
     * @param string $capability
     * @return bool
     */
    function current_user_can($capability)
    {
        return false;
    }
}

if (!function_exists('get_current_user_id')) {
    /**
     * @return int
     */
    function get_current_user_id()
    {
        return 0;
    }
}

if (!function_exists('wp_remote_request')) {
    function wp_remote_request($url, $args)
    {
    }
}

if (!function_exists('wp_safe_remote_request')) {
    function wp_safe_remote_request($url, $args)
    {
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response)
    {
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response)
    {
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = [])
    {
    }
}

if (!function_exists('wp_roles')) {
    function wp_roles()
    {
        global $wp_roles;
        return $wp_roles;
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        public function __construct($parameter)
        {
        }

        public function set_status($status)
        {
        }
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        /** @var string */
        private $code;
        /** @var string */
        private $message;

        public function __construct($code = '', $message = '', $data = '')
        {
            $this->code    = (string) $code;
            $this->message = (string) $message;
        }

        /**
         * @return string
         */
        public function get_error_code()
        {
            return $this->code;
        }

        /**
         * @param string|int $code
         * @return string
         */
        public function get_error_message($code = '')
        {
            return $this->message;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing)
    {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('wp_unslash')) {
    /**
     * @param string|array $value
     * @return string|array
     */
    function wp_unslash($value) {
        return $value;
    }
}

if (!function_exists('wp_slash')) {
    /**
     * @param string|array $value
     * @return string|array
     */
    function wp_slash($value) {
        return $value;
    }
}

if (!function_exists('wp_strip_all_tags')) {
    /**
     * @param string $text
     * @param bool $remove_breaks
     * @return string
     */
    function wp_strip_all_tags($text, $remove_breaks = false) {
        return strip_tags($text);
    }
}

if (!function_exists('esc_html__')) {
    /**
     * @param string $text
     * @param string $domain
     * @return string
     */
    function esc_html__($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('remove_query_arg')) {
    /**
     * @param string|string[] $key
     * @param string|false $query
     * @return string
     */
    function remove_query_arg($key, $query = false)
    {
        return '';
    }
}

if (!function_exists('add_query_arg')) {
    /**
     * @param string|array $key
     * @param string|false $value
     * @param string|false $url
     * @return string
     */
    function add_query_arg($key, $value = false, $url = false)
    {
        return '';
    }
}

if (!function_exists('wp_nonce_url')) {
    /**
     * @param string $actionurl
     * @param int|string $action
     * @param string $name
     * @return string
     */
    function wp_nonce_url($actionurl, $action = -1, $name = '_wpnonce')
    {
        return '';
    }
}

if (!function_exists('wp_get_current_user')) {
    /**
     * @return WP_User
     */
    function wp_get_current_user()
    {
        return new WP_User(0);
    }
}

if (!function_exists('wp_json_encode')) {
    /**
     * @param mixed $data
     * @param int   $options
     * @param int   $depth
     * @return string|false
     */
    function wp_json_encode($data, $options = 0, $depth = 512)
    {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('esc_js')) {
    /**
     * @param string $text
     * @return string
     */
    function esc_js($text)
    {
        return $text;
    }
}

if (!function_exists('rest_url')) {
    /**
     * @param string $path
     * @return string
     */
    function rest_url($path = '')
    {
        return 'http://example.com/wp-json/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_create_nonce')) {
    /**
     * @param string|int $action
     * @return string
     */
    function wp_create_nonce($action = -1)
    {
        return '';
    }
}

if (!function_exists('simple_jwt_login_init_session')) {
    /**
     * @return array
     */
    function simple_jwt_login_init_session()
    {
        return [];
    }
}

if (!function_exists('trailingslashit')) {
    /**
     * @param string $string
     * @return string
     */
    function trailingslashit($string)
    {
        return rtrim($string, '/\\') . '/';
    }
}

if (!function_exists('esc_sql')) {
    /**
     * @param string $sql
     * @return string
     */
    function esc_sql($sql)
    {
        return addslashes($sql);
    }
}

if (!function_exists('sanitize_hex_color')) {
    /**
     * @param string $color
     * @return string|null
     */
    function sanitize_hex_color($color)
    {
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return $color;
        }
        return null;
    }
}

