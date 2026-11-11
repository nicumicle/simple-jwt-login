<?php

namespace SimpleJWTLogin\Repositories\Wordpress;

use Exception;
use WP_REST_Response;
use WP_User;

interface Repository
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
     * @param string|null $token
     */
    public function loginUser($user, $token = null);

    /**
     * Sets the current WordPress user for the duration of a REST API request.
     * Unlike loginUser(), this does NOT set auth cookies or fire the wp_login action.
     * Safe to call multiple times per request - subsequent calls for the same user are no-ops.
     *
     * @param \WP_User $user
     */
    public function setCurrentUser($user);

    /**
     * @param string $url
     */
    public function redirect($url);

    /**
     * @param string $url
     */
    public function redirectSafe($url);

    /**
     * @return string
     */
    public function getAdminUrl();

    /**
     * @return string
     */
    public function getSiteUrl();

    /**
     * @param ?array $params
     * @return mixed
     */
    public function getLoginURL($params);

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
     * @param array  $roles
     * @param array  $extraParameters
     *
     * @return WP_User
     * @throws Exception
     */
    public function createUser($username, $email, $password, $roles, $extraParameters);

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
     * Strip HTML that is not allowed in post content (wp_kses_post).
     *
     * @param string $html
     *
     * @return string
     */
    public function ksesPost($html);

    /**
     * Role slug => display name for every registered WordPress role.
     *
     * @return array<string, string>
     */
    public function getRoleNames();

    /**
     * Capability => granted flag for a role; empty when the role does not exist.
     *
     * @param string $role
     * @return array<string, bool>
     */
    public function getRoleCapabilities($role);

    /**
     * @return string
     */
    public function getWordPressVersion();

    /**
     * @param array $array
     * @return array
     */
    public function sanitizeArray($array);

    /**
     * @param \WP_User $user
     *
     * @return bool
     */
    public function deleteUser($user);

    /**
     * @return void
     */
    public function doAction();

    /**
     * @return mixed
     */
    public function applyFilters();

    /**
     * Register a WordPress action callback.
     *
     * @param string   $hookName
     * @param callable $callback
     * @param int      $priority
     * @param int      $acceptedArgs
     *
     * @return void
     */
    public function addAction($hookName, $callback, $priority = 10, $acceptedArgs = 1);

    /**
     * Whether the SAPI can flush the response to the client and keep running
     * (PHP-FPM). When false, callers should run deferred work inline instead.
     *
     * @return bool
     */
    public function canFinishRequest();

    /**
     * Flush the response to the client and close the connection while PHP keeps
     * running, so deferred work does not add to request latency. No-op when the
     * SAPI does not provide fastcgi_finish_request().
     *
     * @return void
     */
    public function finishRequest();

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
     * @param string $metaKey
     * @return int[] WordPress user IDs that have at least one row for the given usermeta key
     */
    public function getUserIdsWithMeta($metaKey);

    /**
     * @param int $userId
     * @param string $metaKey
     * @param string $value
     * @return bool|int
     */
    public function updateUserMeta($userId, $metaKey, $value);

    /**
     * @param WP_User $user
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
     * @param string $code
     * @param string $email
     *
     * @return WP_User|bool
     */
    public function checkPasswordResetKeyByEmail($code, $email);

    /**
     * @param string $code
     * @param string $login
     * @return WP_User|bool
     */
    public function checkPasswordResetKeyByUserLogin($code, $login);

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
     * @param int $userId
     * @param string $password
     */
    public function sendNewUserNotification($userId, $password);

    /**
     * @param \WP_User $user
     */
    public function sendPasswordChangedNotification($user);

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

    /**
     * @return bool
     */
    public function isUserLoggedIn();

    /**
     * @param string|array $value
     * @return string|array
     */
    public function wpUnslash($value);

    /**
     * @param string|array $value
     * @return string|array
     */
    public function wpSlash($value);

    /**
     * @param string $capability
     * @return bool
     */
    public function currentUserCan($capability);

    /**
     * @return int
     */
    public function getCurrentUserId();

    /**
     * @param string $url
     * @param int $component
     * @return array|string|int|false|null
     */
    public function parseUrl($url, $component = -1);

    /**
     * Register a WordPress ability (Abilities API, WP 6.9+).
     *
     * @param string $name
     * @param array  $args
     * @return mixed The WP_Ability instance, or null on failure / when the API is unavailable.
     */
    public function registerAbility($name, $args);

    /**
     * Register a WordPress ability category (Abilities API, WP 6.9+).
     *
     * @param string $slug
     * @param array  $args
     * @return mixed The WP_Ability_Category instance, or null on failure / when the API is unavailable.
     */
    public function registerAbilityCategory($slug, $args);

    /**
     * Unregister a WordPress ability if it is registered (Abilities API, WP 6.9+).
     * Only affects the current request: the registry lives in memory.
     *
     * @param string $name
     * @return void
     */
    public function unregisterAbility($name);

    /**
     * Unregister a WordPress ability category if it is registered (Abilities API, WP 6.9+).
     *
     * @param string $slug
     * @return void
     */
    public function unregisterAbilityCategory($slug);

    /**
     * Whether a WordPress action has fired (did_action()).
     *
     * @param string $hookName
     * @return bool
     */
    public function didAction($hookName);

    /**
     * Whether a WordPress action is currently running (doing_action()).
     *
     * @param string $hookName
     * @return bool
     */
    public function doingAction($hookName);

    /**
     * Whether the code runs inside WP-CLI.
     *
     * @return bool
     */
    public function isCli();

    /**
     * Whether the WordPress Abilities API (WordPress 6.9+) is available.
     *
     * @return bool
     */
    public function isAbilitiesApiAvailable();

    /**
     * Read an option straight from the database, bypassing the in-request and
     * WordPress object caches (for long-running processes such as MCP stdio).
     *
     * @param string $optionName
     * @return mixed
     */
    public function getFreshOption($optionName);
}
