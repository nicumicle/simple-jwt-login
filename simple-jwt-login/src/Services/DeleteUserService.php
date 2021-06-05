<?php
namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\DeleteUserSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;

class DeleteUserService extends BaseService implements ServiceInterface
{
    const ACTION_NAME_DELETE_USER = 1;

    /**
     * @param null|int $actionName
     * @return mixed|\WP_REST_Response
     * @throws Exception
     */
    public function makeAction($actionName = null)
    {
        if ($actionName !== self::ACTION_NAME_DELETE_USER) {
            throw  new Exception('Action not implemented.');
        }
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
            && !isset($this->request[$this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()])
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


        $registerParameter = $this->validateJWTAndGetUserValueFromPayload();
        $getUserBy = $this->jwtSettings->getDeleteUserSettings()->getDeleteUserBy();
        $user = $getUserBy === DeleteUserSettings::DELETE_USER_BY_ID
            ? $this->wordPressData->getUserDetailsById($registerParameter)
            : $this->wordPressData->getUserDetailsByEmail($registerParameter);

        if ($user === false) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        $this->validateJwtRevoked($user->get('id'), $this->jwt);

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
