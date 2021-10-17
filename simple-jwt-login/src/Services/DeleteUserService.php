<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\DeleteUserSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;

class DeleteUserService extends BaseService implements ServiceInterface
{
    /**
     * @return mixed|\WP_REST_Response
     * @throws Exception
     */
    public function makeAction()
    {
        return $this->deleteUser();
    }

    /**
     * Main Function for Delete user route
     * @throws Exception
     */
    public function deleteUser()
    {
        if ($this->jwtSettings->getDeleteUserSettings()->isDeleteAllowed() === false) {
            throw  new Exception(
                __('Delete is not enabled.', 'simple-jwt-login'),
                ErrorCodes::ERR_DELETE_IS_NOT_ENABLED
            );
        }

        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new Exception(
                __('The `jwt` parameter is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_DELETE_MISSING_JWT
            );
        }

        if ($this->jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete()
            && $this->validateAuthKey() === false
        ) {
            throw new Exception(
                sprintf(
                    __('Missing AUTH KEY ( %s ).', 'simple-jwt-login'),
                    $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()
                ),
                ErrorCodes::ERR_DELETE_MISSING_AUTH_KEY
            );
        }

        $allowedIpsString = trim($this->jwtSettings->getDeleteUserSettings()->getAllowedDeleteIps());
        if (!empty($allowedIpsString) && !$this->serverHelper->isClientIpInList($allowedIpsString)) {
            throw new Exception(
                sprintf(
                    __('You are not allowed to delete users from this IP: %s', 'simple-jwt-login'),
                    $this->serverHelper->getClientIP()
                ),
                ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP
            );
        }

        $getUserBy = $this->jwtSettings->getDeleteUserSettings()->getDeleteUserBy();
        $registerParameter = $this->validateJWTAndGetUserValueFromPayload(
            $this->jwtSettings->getDeleteUserSettings()->getJwtDeleteByParameter()
        );

        switch ($getUserBy) {
            case DeleteUserSettings::DELETE_USER_BY_EMAIL:
                $user = $this->wordPressData->getUserDetailsByEmail($registerParameter);
                break;
            case DeleteUserSettings::DELETE_USER_BY_ID:
                $user = $this->wordPressData->getUserDetailsById($registerParameter);
                break;
            case DeleteUserSettings::DELETE_USER_BY_USER_LOGIN:
                $user = $this->wordPressData->getUserByUserLogin($registerParameter);
                break;
            default:
                $user = false;
        }

        if ($user === false) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        $this->validateJwtRevoked(
            $this->wordPressData->getUserProperty($user, 'id'),
            $this->jwt
        );

        $result = $this->wordPressData->deleteUser($user);

        if ($result === false) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        if ($this->jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::DELETE_USER_ACTION_NAME)) {
            $this->wordPressData->triggerAction(SimpleJWTLoginHooks::DELETE_USER_ACTION_NAME, $user);
        }

        return $this->wordPressData->createResponse([
            'message' => __('User was successfully deleted.', 'simple-jwt-login'),
            'id' => $result
        ]);
    }
}
