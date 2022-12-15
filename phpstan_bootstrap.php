<?php
include_once 'phpunit_bootstrap.php';

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


if (!function_exists('do_action')) {
    function do_action($actionName, $arg1 = null, $arg2 = null, $arg3 = null, $arg4=null)
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
if (!function_exists('get_user_by_email')) {
    function get_user_by_email($email)
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
    function wp_set_auth_cookie($userId)
    {
    }
}
if (!function_exists('wp_redirect')) {
    function wp_redirect($url)
    {
    }
}
if (!function_exists('admin_url')) {
    function admin_url()
    {
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

if (!function_exists('esc_url')) {
    function esc_url($parameter)
    {
        return $parameter;
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
    function apply_filters($name, $option1=null, $option2=null, $option3=null, $option4=null)
    {
    }
}

if (!function_exists('check_password_reset_key')) {
    function check_password_reset_key($code, $email){

    }
}

if (!function_exists('get_password_reset_key')) {
    function get_password_reset_key($user){

    }
}
if (!function_exists('retrieve_password')) {
    function  retrieve_password($username) {

    }
}

if (!function_exists('wp_email')) {
    function wp_mail($sendTo, $emailSubject, $emailBody, $headers = []) {
    }
}

if (!function_exists('reset_password')) {
    function reset_password($user, $newPassword) {
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($nonceName) {
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonceValue, $nonceName){
        return true;
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
    function role_exists($role) {
        return true;
    }
}

if (!function_exists('plugin_dir_path')) {
    /**
     * @param string|null $path
     * @return string
     */
    function plugin_dir_path($path = null) {
        return "";
    }
}

if (!function_exists('plugin_dir_url')) {
    /**
     * @param string $file
     * @return string
     */
    function plugin_dir_url( $file ) {
        return '';
    }
}
if (!function_exists('delete_option')) {
    /**
     * @param string $option
     * @return bool
     */
    function delete_option($option) {
        return true;
    }
}

if (!function_exists('register_uninstall_hook')) {
    /**
     * @param string|null $file
     * @param string|null $callable
     */
    function register_uninstall_hook($file, $callable) {
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
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1) {
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
    function add_filter($hook_name, $callback,$priority = 10, $accepted_args = 1 ){
        return true;
    }
}

if (!function_exists('plugins_url')) {
    /**
     * @param string $path
     * @param string $plugin
     * @return string
     */
    function plugins_url($path = '', $plugin = '' ) {
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
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null ) {
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
    function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false )
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
    function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' )
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
    function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
    }
}

if (!function_exists('plugin_basename')) {
    /**
     * @param string $file
     * @return string
     */
    function plugin_basename( $file ) {
        return '';
    }
}

if (!function_exists('add_shortcode')) {
    /**
     * @param string $tag
     * @param callable $callback
     * @return void
     */
    function add_shortcode($tag, $callback) {
    }
}

if (!function_exists('get_plugin_data')) {
    /**
     * @param string $plugin_file
     * @param bool $markup
     * @param bool $translate
     * @return array
     */
    function get_plugin_data($plugin_file, $markup = true, $translate = true ) {
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
    function wp_send_json_error($data = null, $status_code = null, $options = 0 ){
    }
}

if (!function_exists('home_url')) {
    /**
     * @param string $path
     * @param string|null $scheme
     * @return string
     */
    function home_url($path = '', $scheme = null ) {
        return '';
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
    function register_rest_route($namespace, $route, $args = array(), $override = false ) {
        return true;
    }
}

if (!function_exists('is_user_logged_in')) {
    /**
     * @return bool
     */
    function is_user_logged_in(){
        return false;
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

if (!class_exists('WP_User')) {
    class WP_User
    {
        public $user_login;

        public function __construct($user)
        {
        }

        public function set_role($role)
        {
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

if (!class_exists('WP_Error')) {
    class WP_Error
    {

    }
}
