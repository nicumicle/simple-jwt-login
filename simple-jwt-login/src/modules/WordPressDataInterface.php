<?php


namespace SimpleJWTLogin\Modules;

use WP_REST_Response;

interface WordPressDataInterface
{
    /**
     * @param int $userID
     *
     * @return bool|\WP_User
     */
    public function getUserDetailsById($userID);

    /**
     * @param string $emailAddress
     *
     * @return bool|\WP_User
     */
    public function getUserDetailsByEmail($emailAddress);

    /**
     * @param \WP_User $user
     */
    public function loginUser($user);

    /**
     * @param string $url
     */
    public function redirect($url);

    /**
     * @return string|void
     */
    public function getAdminUrl();

    /**
     * @return string|void
     */
    public function getSiteUrl();

    /**
     * @param string $username
     * @param string $email
     *
     * @return bool
     */
    public function checkUserExistsByUsernameAndEmail($username, $email);

    /**
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $role
     *
     * @return int|\WP_Error
     * @throws \Exception
     */
    public function createUser($username, $email, $password, $role);

    /**
     * @param string $optionName
     *
     * @return mixed
     */
    public function getOptionFromDatabase($optionName);

    /**
     * @param string $optionName
     * @param string $value
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function add_option($optionName, $value);

    /**
     * @param string $optionName
     * @param string $value
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function update_option($optionName, $value);

    /**
     * @param int $userId
     *
     * @return WP_REST_Response
     */
    public function createResponse($userId);

    /**
     * @param string $text
     *
     * @return string
     */
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName
    public function sanitize_text_field($text);

    /**
     * @param \WP_User $user
     *
     * @return bool
     */
    public function deleteUser($user);

    /**
     * @return void
     */
    public function triggerAction();

    /**
     * @param int $userId
     *
     * @return mixed
     */
    public function buildUserFromId($userId);
}
