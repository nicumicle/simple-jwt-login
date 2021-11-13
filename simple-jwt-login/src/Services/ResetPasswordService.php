<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use WP_REST_Response;

class ResetPasswordService extends BaseService implements ServiceInterface
{
    public function makeAction()
    {
        if ($this->jwtSettings->getResetPasswordSettings()->isResetPasswordEnabled() === false) {
            throw  new Exception(
                __('Reset Password is not allowed.', 'simple-jwt-login'),
                ErrorCodes::ERR_RESET_PASSWORD_IS_NOT_ALLOWED
            );
        }

        if ($this->jwtSettings->getResetPasswordSettings()->isAuthKeyRequired()
            && $this->validateAuthKey() === false
        ) {
            throw  new Exception(
                sprintf(
                    __('Invalid Auth Code ( %s ) provided.', 'simple-jwt-login'),
                    $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()
                ),
                ErrorCodes::ERR_RESET_PASSWORD_INVALID_AUTH_KEY
            );
        }

        switch ($this->serverHelper->getRequestMethod()) {
            case RouteService::METHOD_PUT:
                return $this->changeUserPassword();
            case RouteService::METHOD_POST:
                return $this->sendResetPassword();
            default:
                throw new Exception(
                    __('Route called with invalid request method.', 'simple-jwt-login'),
                    ErrorCodes::ERR_ROUTE_CALLED_WITH_INVALID_METHOD
                );
        }
    }

    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    private function changeUserPassword()
    {

        $this->validateChangePassword();

        $newPassword = $this->request['new_password'];

        $jwtAllowed = $this->jwtSettings->getResetPasswordSettings()->isJwtAllowed();
        if ($jwtAllowed === false && empty($this->request['code'])) {
            throw new Exception(
                __('Missing code parameter.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_CODE_FOR_CHANGE_PASSWORD
            );
        }

        $user = $this->getUser($jwtAllowed);
        $this->wordPressData->resetPassword($user, $newPassword);

        return $this->wordPressData->createResponse(
            [
                'success' => true,
                'message' => __('User Password has been changed.', 'simple-jwt-login'),
            ]
        );
    }

    private function validateChangePassword()
    {
        if (empty($this->request['email'])) {
            throw new Exception(
                __('Missing email parameter.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_EMAIL_FOR_CHANGE_PASSWORD
            );
        }

        if (empty($this->request['new_password'])) {
            throw new Exception(
                __('Missing new_password parameter.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_CHANGE_PASSWORD
            );
        }
    }

    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    private function sendResetPassword()
    {
        $this->validateSendResetPassword();
        $email = $this->request['email'];

        $user = $this->wordPressData->getUserDetailsByEmail($email);
        if (empty($user)) {
            throw new Exception(
                __('Wrong user.', 'simple-jwt-login'),
                ErrorCodes::ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD
            );
        }
        switch ($this->jwtSettings->getResetPasswordSettings()->getFlowType()) {
            case ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB:
                $this->wordPressData->generateAndGetPasswordResetKey($user);
                $message = __('The Code has been saved into the database.', 'simple-jwt-login');
                break;
            case ResetPasswordSettings::FLOW_SEND_DEFAULT_WP_EMAIL:
                $this->wordPressData->sendDefaultWordPressResetPassword(
                    $this->wordPressData->getUserProperty($user, 'user_login')
                );
                $message = __('Reset password email has been sent.', 'simple-jwt-login');
                break;
            case ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL:
                $code = $this->wordPressData->generateAndGetPasswordResetKey($user);
                $sendTo = $this->wordPressData->getUserProperty($user, 'user_email');
                $emailSubject = $this->jwtSettings->getResetPasswordSettings()->getResetPasswordEmailSubject();
                $emailBody = $this->jwtSettings->getResetPasswordSettings()->getResetPasswordEmailBody();
                if ($this->jwtSettings
                    ->getHooksSettings()
                    ->isHookEnable(SimpleJWTLoginHooks::RESET_PASSWORD_CUSTOM_EMAIL_TEMPLATE)
                ) {
                    $emailBody = $this->wordPressData->triggerFilter(
                        SimpleJWTLoginHooks::RESET_PASSWORD_CUSTOM_EMAIL_TEMPLATE,
                        $emailBody,
                        $this->request
                    );
                }
                $emailBody = $this->replaceVariablesInEmailBody(
                    $emailBody,
                    $user,
                    $code
                );
                $emailType = $this->jwtSettings->getResetPasswordSettings()->getResetPasswordEmailType();

                $sendAsHtml = $emailType === ResetPasswordSettings::EMAIL_TYPE_HTML;
                $this->wordPressData->sendEmail($sendTo, $emailSubject, $emailBody, $sendAsHtml);

                $message = __('Reset password email has been sent.', 'simple-jwt-login');
                break;
            default:
                throw new Exception(
                    __('Invalid flow type.', 'simple-jwt-login'),
                    ErrorCodes::ERR_RESET_PASSWORD_INVALID_FLOW
                );
        }

        return $this->wordPressData->createResponse(
            [
                'success' => true,
                'message' => $message,
            ]
        );
    }

    /**
     * @throws Exception
     */
    private function validateSendResetPassword()
    {
        if (empty($this->request['email'])) {
            throw new Exception(
                __('Missing email parameter.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_RESET_PASSWORD
            );
        }
    }

    /**
     * @param string $emailBody
     * @param \WP_User $user
     * @param string $code
     *
     * @return mixed
     */
    private function replaceVariablesInEmailBody($emailBody, $user, $code)
    {
        $variables = array_keys($this->jwtSettings->getResetPasswordSettings()->getEmailContentVariables());
        foreach ($variables as $variableKey) {
            switch ($variableKey) {
                case "{{CODE}}":
                    $replace = $code;
                    break;
                case "{{NAME}}":
                    $replace = $this->wordPressData->getUserProperty($user, 'first_name')
                               . $this->wordPressData->getUserProperty($user, 'last_name');
                    break;
                case "{{EMAIL}}":
                    $replace = $this->wordPressData->getUserProperty($user, 'user_login');
                    break;
                case "{{NICKNAME}}":
                    $replace = $this->wordPressData->getUserProperty($user, 'nickname');
                    break;
                case "{{FIRST_NAME}}":
                    $replace = $this->wordPressData->getUserProperty($user, 'first_name');
                    break;
                case "{{LAST_NAME}}":
                    $replace = $this->wordPressData->getUserProperty($user, 'last_name');
                    break;
                case "{{SITE}}":
                    $replace = $this->wordPressData->getSiteUrl();
                    break;
                case "{{IP}}":
                    $replace = $this->serverHelper->getClientIP();
                    break;
                default:
                    $replace = $variableKey;
                    break;
            }

            $emailBody = str_replace($variableKey, $replace, $emailBody);
        }

        return $emailBody;
    }

    private function getUser(bool $jwtAllowed)
    {
        if ($jwtAllowed && empty($this->request['code'])) {
            $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
            if (empty($this->jwt)) {
                throw new Exception(
                    __('The `jwt` parameter is missing.', 'simple-jwt-login'),
                    ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE
                );
            }
            $loginParameter = $this->validateJWTAndGetUserValueFromPayload(
                $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
            );
            $user = $this->getUserDetails($loginParameter);
            if (empty($user)
                || $this->wordPressData->getUserProperty($user, 'user_email') !== $this->request['email']
            ) {
                throw new Exception(
                    __('This JWT can not change your password.', 'simple-jwt-login')
                );
            }

            return $user;
        }

        $code = $this->request['code'];
        $user = $this->wordPressData->checkPasswordResetKey($code, $this->request['email']);
        if (empty($user)) {
            throw new Exception(
                __('Invalid code provided.', 'simple-jwt-login'),
                ErrorCodes::ERR_INVALID_RESET_PASSWORD_CODE
            );
        }

        return $user;
    }
}
