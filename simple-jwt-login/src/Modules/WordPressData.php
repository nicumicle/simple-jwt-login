<?php

namespace SimpleJWTLogin\Modules;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use WP_REST_Response;
use WP_User;

class WordPressData implements WordPressDataInterface
{
    const NONCE_NAME = 'simple-jwt-login-nonce';

    /**
     * @param int $userID
     *
     * @return bool|WP_User
     */
    public function getUserDetailsById($userID)
    {
        return get_userdata((int) $userID);
    }

    /**
     * @param string $emailAddress
     *
     * @return bool|WP_User
     */
    public function getUserDetailsByEmail($emailAddress)
    {
        return get_user_by_email($emailAddress);
    }

    /**
     * @param string $username
     *
     * @return bool|WP_User
     */
    public function getUserByUserLogin($username)
    {
        return get_user_by('login', $username);
    }

    /**
     * @param WP_User $user
     */
    public function loginUser($user)
    {
        wp_set_current_user($user->get('id'));
        wp_set_auth_cookie($user->get('id'));

        do_action('wp_login', $user->user_login, $user);
    }

    /**
     * @SuppressWarnings(ExitExpression)
     * @param string $url
     */
    public function redirect($url)
    {
        wp_redirect($url);
        exit;
    }

    /**
     * @return string|void
     */
    public function getAdminUrl()
    {
        return admin_url();
    }

    /**
     * @return string|void
     */
    public function getSiteUrl()
    {
        return site_url();
    }

    /**
     * @param string $username
     * @param string $email
     *
     * @return bool
     */
    public function checkUserExistsByUsernameAndEmail($username, $email)
    {
        return username_exists($username) || email_exists($email);
    }

    /**
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $role
     * @param array  $extraParameters
     *
     * @return WP_User
     * @throws Exception
     */
    public function createUser($username, $email, $password, $role, $extraParameters = [])
    {
        $userParameters = [
            'user_pass'  => $password,
            'user_login' => $username,
            'user_email' => $email,
        ];

        $userParameters = (new UserProperties())->build($userParameters, $extraParameters);

        $result = wp_insert_user($userParameters);
        if (!is_int($result)) {
            throw new Exception(
                $result->get_error_message(
                    $result->get_error_code()
                ),
                ErrorCodes::ERR_CREATE_USER_ERROR
            );
        }

        $user   = new WP_User($result);
        $user->set_role($role);

        return $user;
    }

    /**
     * @param string $option
     *
     * @return mixed|void
     */
    public function getOptionFromDatabase($option)
    {
        return get_option($option);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function isEmail($email)
    {
        return (bool) is_email($email);
    }

    /**
     * @param string $optionName
     * @param string $value
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function addOption($optionName, $value)
    {
        add_option($optionName, $value);
    }

    /**
     * @param string $optionName
     * @param string $value
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function updateOption($optionName, $value)
    {
        update_option($optionName, $value);
    }

    /**
     * @param array $responseJson
     *
     * @return WP_REST_Response
     */
    public function createResponse($responseJson)
    {
        $response = new WP_REST_Response($responseJson);
        $response->set_status(200);

        return $response;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function sanitizeTextField($text)
    {
        return sanitize_text_field($text);
    }

    /**
     * @param WP_User $user
     *
     * @return bool|int
     */
    public function deleteUser($user)
    {
        $userId = $user->get('id');
        $return = wp_delete_user($userId);

        return $return === false
            ? $return
            : $userId;
    }

    /**
     * Call do_action function from WordPress with arguments
     */
    public function triggerAction()
    {
        call_user_func_array('do_action', func_get_args());
    }

    /**
     * Call do_action function from WordPress with arguments
     */
    public function triggerFilter()
    {
        return call_user_func_array('apply_filters', func_get_args());
    }

    /**
     * @param int $userID
     *
     * @return WP_User
     */
    public function buildUserFromId($userID)
    {
        return new WP_User($userID);
    }

    /**
     * @param WP_User $user
     *
     * @return mixed
     */
    public function getUserIdFromUser($user)
    {
        return $user->get('id');
    }

    /**
     * @param WP_User$user
     *
     * @return mixed
     */
    public function wordpressUserToArray($user)
    {
        return $user->to_array();
    }

    /**
     * @param int $userId
     * @param string $metaKey
     * @return mixed
     */
    public function getUserMeta($userId, $metaKey)
    {
        return get_user_meta($userId, $metaKey, false);
    }

    /**
     * @param int $userId
     * @param string $metaKey
     * @param string $value
     * @return false|int
     */
    public function addUserMeta($userId, $metaKey, $value)
    {
        return add_user_meta($userId, $metaKey, $value, false);
    }

    /**
     * @param int $userId
     * @param string $metaKey
     * @param string $metaValue
     * @return bool
     */
    public function deleteUserMeta($userId, $metaKey, $metaValue)
    {
        return delete_user_meta($userId, $metaKey, $metaValue);
    }

    /**
     * @param string|null $password
     * @param string|null $passwordHash
     * @param string $dbPassword
     *
     * @return bool
     */
    public function checkPassword($password, $passwordHash, $dbPassword)
    {
        if ($password !== null) {
            return wp_check_password($password, $dbPassword);
        } elseif ($passwordHash !== null) {
            return hash_equals($dbPassword, $passwordHash);
        }

        return false;
    }

    /**
     * @param WP_User $user
     *
     * @return string
     */
    public function getUserPassword($user)
    {
        return $user->get('user_pass');
    }

    /**
     * @param WP_User $user
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getUserProperty($user, $propertyName)
    {
        return $user->get($propertyName);
    }

    public function isInstanceOfuser($user)
    {
        return $user instanceof WP_User;
    }

    public function convertUserToArray($user)
    {
        return $user->to_array();
    }

    /**
     * @param string $code
     * @param string $email
     *
     * @return bool|WP_User
     */
    public function checkPasswordResetKey($code, $email)
    {
        $result = check_password_reset_key($code, $email);
        if ($result instanceof WP_User) {
            return $result;
        }

        return false;
    }

    /**
     * @param WP_User $user
     * @param string $newPassword
     */
    public function resetPassword($user, $newPassword)
    {
        reset_password($user, $newPassword);
    }

    /**
     * @param WP_User $user
     *
     * @return string|bool
     */
    public function generateAndGetPasswordResetKey($user)
    {
        $result = get_password_reset_key($user);
        if ($result instanceof \WP_Error) {
            return false;
        }

        return $result;
    }

    public function sendDefaultWordPressResetPassword($username)
    {
        retrieve_password($username);
    }

    /**
     * @param string $sendTo
     * @param string $emailSubject
     * @param string $emailBody
     * @param bool $sendAsHtml
     */
    public function sendEmail($sendTo, $emailSubject, $emailBody, $sendAsHtml)
    {
        $headers = $sendAsHtml
            ? $headers = 'Content-type: text/html'
            : [];
        wp_mail($sendTo, $emailSubject, $emailBody, $headers);
    }

    /**
     * @param string $name
     */
    public function insertNonce($name)
    {
        wp_nonce_field($name);
    }

    /**
     * @param string|null $nonceValue
     * @param string $nonceName
     *
     * @return false|int
     */
    public function checkNonce($nonceValue, $nonceName)
    {
        return wp_verify_nonce($nonceValue, $nonceName);
    }

    /**
     * @param int $length
     * @return string
     */
    public function generatePassword($length)
    {
        return wp_generate_password($length);
    }

    /**
     * @param string $role
     * @return bool
     */
    /** * @SuppressWarnings(PHPMD.Superglobals) */
    public function roleExists($role)
    {
        return isset($GLOBALS['wp_roles']) && $GLOBALS['wp_roles']->is_role($role);
    }

    /**
     * @param WP_User $user
     * @return array
     */
    public function getUserRoles($user)
    {
        if (isset($user->roles)) {
            return $user->roles;
        }

        return [];
    }
}
