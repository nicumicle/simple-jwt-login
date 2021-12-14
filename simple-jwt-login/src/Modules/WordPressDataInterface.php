<?php
namespace SimpleJWTLogin\Modules;

use Exception;
use WP_REST_Response;
use WP_User;

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
     * @param WP_User $user
     *
     * @return mixed
     */
    public function getUserIdFromUser($user);
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
     * @param array  $extraParameters
     *
     * @return WP_User
     * @throws Exception
     */
    public function createUser($username, $email, $password, $role, $extraParameters);

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
    public function addOption($optionName, $value);

    /**
     * @param string $optionName
     * @param string $value
     */
    public function updateOption($optionName, $value);

    /**
     * @param array $responseJson
     *
     * @return WP_REST_Response
     */
    public function createResponse($responseJson);

    /**
     * @param string $text
     *
     * @return string
     */
    public function sanitizeTextField($text);

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
     * @return mixed
     */
    public function triggerFilter();

    /**
     * @param int $userId
     *
     * @return mixed
     */
    public function buildUserFromId($userId);

    /**
     * @param string $username
     *
     * @return bool|WP_User
     */
    public function getUserByUserLogin($username);

    /**
     * @param string $string
     * @return bool
     */
    public function isEmail($string);

    /**
     * @param int $userId
     * @param string $metaKey
     * @return mixed
     */
    public function getUserMeta($userId, $metaKey);

    /**
     * @param int $userId
     * @param string $metaKey
     * @param string $metaValue
     * @return bool
     */
    public function deleteUserMeta($userId, $metaKey, $metaValue);

    /**
     * @param int $userId
     * @param string $metaKey
     * @param string $value
     * @return false|int
     */
    public function addUserMeta($userId, $metaKey, $value);

    /**
     * @param WP_User$user
     *
     * @return mixed
     */
    public function wordpressUserToArray($user);

    /**
     * @param string|null $password
     * @param string|null $passwordHash
     * @param string $dbPassword
     *
     * @return boolean
     */
    public function checkPassword($password, $passwordHash, $dbPassword);

    /**
     * @param WP_User $user
     *
     * @return string
     */
    public function getUserPassword($user);

    /**
     * @param WP_User $user
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getUserProperty($user, $propertyName);

    /**
     * @param mixed $user
     *
     * @return bool
     */
    public function isInstanceOfuser($user);

    /**
     * @param WP_User $user
     *
     * @return array
     */
    public function convertUserToArray($user);

    /**
     * @param string $code
     * @param string $email
     *
     * @return WP_User|bool
     */
    public function checkPasswordResetKey($code, $email);

    /**
     * @param WP_User $user
     * @param string $newPassword
     */
    public function resetPassword($user, $newPassword);

    /**
     * @param WP_User $user
     *
     * @return string|bool
     */
    public function generateAndGetPasswordResetKey($user);

    /**
     * @param string $email
     */
    public function sendDefaultWordPressResetPassword($email);

    /**
     * @param string $sendTo
     * @param string $emailSubject
     * @param string $emailBody
     * @param bool $sendAsHtml
     */
    public function sendEmail($sendTo, $emailSubject, $emailBody, $sendAsHtml);

    /**
     * @param string $nonceName
     */
    public function insertNonce($nonceName);

    /**
     * @param string|null $nonceValue
     * @param string $nonceName
     *
     * @return false|int
     */
    public function checkNonce($nonceValue, $nonceName);

    /**
     * @param int $length
     * @return string
     */
    public function generatePassword($length);

    /**
     * @param string $roleName
     * @return bool
     */
    public function roleExists($roleName);

    /**
     * @param WP_User $user
     * @return array
     */
    public function getUserRoles($user);
}
