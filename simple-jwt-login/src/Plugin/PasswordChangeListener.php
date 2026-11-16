<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Helpers\Jwt\PasswordChangeCutoff;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository;
use WP_User;

/**
 * Cuts off every session of a user whose password changed, whichever way it changed:
 * the plugin's reset password endpoint and wp-login.php go through reset_password()
 * ("after_password_reset"), profile / admin / REST user edits through wp_update_user()
 * ("profile_update"). Only when "Revoke sessions on password change" is on.
 */
class PasswordChangeListener
{
    /**
     * @var Repository
     */
    private $wordPressData;

    /**
     * @var RefreshTokenRepositoryInterface
     */
    private $refreshTokenRepo;

    /**
     * @var AuthenticationSettings
     */
    private $authSettings;

    public function __construct(
        Repository $wordPressData,
        RefreshTokenRepositoryInterface $refreshTokenRepo,
        AuthenticationSettings $authSettings
    ) {
        $this->wordPressData    = $wordPressData;
        $this->refreshTokenRepo = $refreshTokenRepo;
        $this->authSettings     = $authSettings;
    }

    /**
     * @param WP_User $user
     * @return void
     */
    public function onPasswordReset($user)
    {
        $this->revokeUserTokens((int) $this->wordPressData->getUserProperty($user, 'ID'));
    }

    /**
     * Hooked on "wp_set_password" (WordPress 6.2+), which reset_password() and every direct
     * wp_set_password() call fire. Revoking twice for one change is harmless.
     *
     * @param string $password Never stored or logged.
     * @param int $userId
     * @return void
     */
    public function onPasswordSet($password, $userId)
    {
        unset($password);
        $this->revokeUserTokens((int) $userId);
    }

    /**
     * @param int $userId
     * @param WP_User $oldUserData
     * @param array $userData The data saved by wp_insert_user(), with the new password hash (WordPress 5.8+).
     * @return void
     */
    public function onProfileUpdate($userId, $oldUserData, $userData = array())
    {
        if (!isset($userData['user_pass'])
            || $userData['user_pass'] === $this->wordPressData->getUserProperty($oldUserData, 'user_pass')
        ) {
            return;
        }
        $this->revokeUserTokens((int) $userId);
    }

    /**
     * @param int $userId
     * @return void
     */
    private function revokeUserTokens($userId)
    {
        if (!$this->authSettings->isRevokeOnPasswordChangeEnabled()) {
            return;
        }
        $this->refreshTokenRepo->deleteByUserId($userId);
        PasswordChangeCutoff::markPasswordChanged($this->wordPressData, $userId);
    }
}
