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
