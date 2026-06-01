<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use WP_REST_Response;

class ResetPasswordService extends BaseService implements ServiceInterface
{
    public function makeAction()
    {
        try {
            return $this->doResetPassword();
        } catch (Exception $exception) {
            $email = isset($this->request['email']) ? $this->request['email'] : null;
            $this->wordPressData->triggerAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_PASSWORD_RESET_FAILED,
                null,
                $email,
                $exception->getMessage()
            );
            throw $exception;
        }
    }

    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    private function doResetPassword()
    {
        if (!$this->jwtSettings->getResetPasswordSettings()->isResetPasswordEnabled()) {
            throw new Exception(
                esc_html(__('Reset Password is not allowed.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_RESET_PASSWORD_IS_NOT_ALLOWED)
            );
        }

        if ($this->jwtSettings->getResetPasswordSettings()->isAuthKeyRequired()) {
            $this->validateAuthKey();
        }

        switch ($this->serverHelper->getRequestMethod()) {
            case RouteService::METHOD_PUT:
                return $this->changeUserPassword();
            case RouteService::METHOD_POST:
                return $this->sendResetPassword();
            default:
                throw new Exception(
                    esc_html(__('Route called with invalid request method.', 'simple-jwt-login')),
                    absint(ErrorCodes::ERR_ROUTE_CALLED_WITH_INVALID_METHOD)
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
        $newPassword = (string) $this->request['new_password'];
        if ($this->jwtSettings->getAuthenticationSettings()->isAuthPasswordBase64Encoded()) {
            $newPassword = base64_decode($newPassword);
        }
        $newPassword = $this->wordPressData->wpSlash($newPassword);
        $jwtAllowed = $this->jwtSettings->getResetPasswordSettings()->isJwtAllowed();
        if (!$jwtAllowed && empty($this->request['code'])) {
            throw new Exception(
                esc_html(__('Missing code parameter.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_MISSING_CODE_FOR_CHANGE_PASSWORD)
            );
        }

        $user      = $this->getUser($jwtAllowed);
        $userId    = (int) $this->wordPressData->getUserProperty($user, 'ID');
        $userEmail = (string) $this->wordPressData->getUserProperty($user, 'user_email');

        $this->wordPressData->resetPassword($user, $newPassword);

        if ($this->jwtSettings->getResetPasswordSettings()->shouldSendPasswordChangedEmail()) {
            $this->wordPressData->sendPasswordChangedNotification($user);
        }

        $this->wordPressData->triggerAction(
            SimpleJWTLoginHooks::AUDIT_AUTH_PASSWORD_RESET_SUCCESS,
            $userId,
            $userEmail
        );

        (new WebhooksService($this->jwtSettings, $this->webhookLogRepository))->dispatch(
            WebhooksSettings::EVENT_RESET_PASSWORD,
            [
                'user_id'    => $userId,
                'user_email' => $userEmail,
            ]
        );

        $response = [
            'success' => true,
            'data'    => [
                'message' => __('User Password has been changed.', 'simple-jwt-login'),
            ],
        ];

        if ($this->jwtSettings->getHooksSettings()
                ->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_CHANGE_USER_PASSWORD)
        ) {
            $response = $this->wordPressData
                ->triggerFilter(
                    SimpleJWTLoginHooks::HOOK_RESPONSE_CHANGE_USER_PASSWORD,
                    $response,
                    $user
                );
        }

        return $this->wordPressData->createResponse($response);
    }

    private function validateChangePassword()
    {
        if (empty($this->request['email'])) {
            throw new ValidationException(
                esc_html(__('Missing email parameter.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_MISSING_EMAIL_FOR_CHANGE_PASSWORD)
            );
        }
        if (empty($this->request['new_password'])) {
            throw new ValidationException(
                esc_html(__('Missing new_password parameter.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_CHANGE_PASSWORD)
            );
        }
        $isJWTAllowed = $this->jwtSettings->getResetPasswordSettings()->isJwtAllowed();
        if ($isJWTAllowed && empty($this->request['code']) && !$this->getJwtFromRequestHeaderOrCookie()) {
            throw new ValidationException(
                esc_html(__('Missing code or jwt parameter.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE)
            );
        }
        if (!$isJWTAllowed && empty($this->request['code'])) {
            throw new ValidationException(
                esc_html(__('Missing code parameter.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_MISSING_CODE_FOR_CHANGE_PASSWORD)
            );
        }
        if (!$this->wordPressData->isEmail($this->request['email'])) {
            throw new ValidationException(
                esc_html(__('Invalid email parameter.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_INVALID_EMAIL_FOR_CHANGE_PASSWORD)
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
        $email = $this->wordPressData->sanitizeTextField($this->request['email']);

        $user = $this->wordPressData->getUserDetailsByEmail($email);
        if (empty($user)) {
            throw new Exception(
                esc_html(__('Wrong user.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD)
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
                $emailSubject = $this->replaceVariables($this->jwtSettings->getResetPasswordSettings()->getResetPasswordEmailSubject(), $user, $code);
                $emailBody = $this->replaceVariables($this->jwtSettings->getResetPasswordSettings()->getResetPasswordEmailBody(), $user, $code);
                if ($this->jwtSettings
                    ->getHooksSettings()
                    ->isHookEnabled(SimpleJWTLoginHooks::RESET_PASSWORD_CUSTOM_EMAIL_TEMPLATE)
                ) {
                    $emailBody = $this->wordPressData->triggerFilter(
                        SimpleJWTLoginHooks::RESET_PASSWORD_CUSTOM_EMAIL_TEMPLATE,
                        $emailBody,
                        $this->request
                    );
                }
                $emailBody = $this->replaceVariables(
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
                    esc_html(__('Invalid flow type.', 'simple-jwt-login')),
                    absint(ErrorCodes::ERR_RESET_PASSWORD_INVALID_FLOW)
                );
        }

        $userId    = (int) $this->wordPressData->getUserProperty($user, 'ID');
        $userEmail = (string) $this->wordPressData->getUserProperty($user, 'user_email');

        $this->wordPressData->triggerAction(
            SimpleJWTLoginHooks::AUDIT_AUTH_PASSWORD_RESET_REQUEST,
            $userId,
            $userEmail
        );

        (new WebhooksService($this->jwtSettings, $this->webhookLogRepository))->dispatch(
            WebhooksSettings::EVENT_RESET_PASSWORD_REQUEST,
            [
                'user_id'    => $userId,
                'user_email' => $userEmail,
            ]
        );

        $response = [
            'success' => true,
            'data'    => [
                'message' => $message,
            ],
        ];

        if ($this->jwtSettings->getHooksSettings()
            ->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_SEND_RESET_PASSWORD)
        ) {
            $response = $this->wordPressData->triggerFilter(
                SimpleJWTLoginHooks::HOOK_RESPONSE_SEND_RESET_PASSWORD,
                $response,
                $user
            );
        }

        return $this->wordPressData->createResponse($response);
    }

    /**
     * @throws Exception
     * @throws ValidationException
     */
    private function validateSendResetPassword()
    {
        if (empty($this->request['email'])) {
            throw new ValidationException(
                esc_html(__('Missing email parameter.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_RESET_PASSWORD)
            );
        }
        if (!$this->wordPressData->isEmail($this->request['email'])) {
            throw new ValidationException(
                esc_html(__('Invalid email parameter.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_INVALID_EMAIL_FOR_RESET_PASSWORD)
            );
        }
    }

    /**
     * @param string $input
     * @param \WP_User $user
     * @param string $code
     *
     * @return mixed
     */
    private function replaceVariables($input, $user, $code)
    {
        $variables = array_keys($this->jwtSettings->getResetPasswordSettings()->getEmailContentVariables());
        foreach ($variables as $variableKey) {
            switch ($variableKey) {
                case '{{CODE}}':
                    $replace = $code;
                    break;
                case '{{NAME}}':
                    $replace = trim(
                        $this->wordPressData->getUserProperty($user, 'first_name')
                        . ' '
                        . $this->wordPressData->getUserProperty($user, 'last_name')
                    );
                    break;
                case '{{USERNAME}}':
                    $replace = $this->wordPressData->getUserProperty($user, 'user_login');
                    break;
                case '{{EMAIL}}':
                    $replace = $this->wordPressData->getUserProperty($user, 'user_email');
                    break;
                case '{{NICKNAME}}':
                    $replace = $this->wordPressData->getUserProperty($user, 'nickname');
                    break;
                case '{{FIRST_NAME}}':
                    $replace = $this->wordPressData->getUserProperty($user, 'first_name');
                    break;
                case '{{LAST_NAME}}':
                    $replace = $this->wordPressData->getUserProperty($user, 'last_name');
                    break;
                case '{{SITE}}':
                    $replace = $this->wordPressData->getSiteUrl();
                    break;
                case '{{IP}}':
                    $replace = $this->serverHelper->getClientIP();
                    break;
                default:
                    $replace = $variableKey;
                    break;
            }

            if ($replace === null) {
                $replace = $variableKey;
            }

            $input = str_replace($variableKey, $replace, $input);
        }

        return $input;
    }

    /**
     * @param bool $jwtAllowed
     * @return bool|\WP_User
     * @throws Exception
     */
    private function getUser($jwtAllowed)
    {
        if ($jwtAllowed && empty($this->request['code'])) {
            $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
            if (empty($this->jwt)) {
                throw new ValidationException(
                    esc_html(__('The `jwt` parameter is missing.', 'simple-jwt-login')),
                    absint(ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE)
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
                    esc_html(__('This JWT can not change your password.', 'simple-jwt-login')),
                    absint(ErrorCodes::ERR_JWT_CANNOT_CHANGE_PASSWORD)
                );
            }

            $this->validateJwtRevoked(
                $this->wordPressData->getUserProperty($user, 'ID'),
                $this->jwt
            );

            return $user;
        }

        $code = $this->wordPressData->sanitizeTextField($this->request['code']);
        $user = $this->wordPressData->checkPasswordResetKeyByEmail(
            $code,
            $this->wordPressData->sanitizeTextField($this->request['email'])
        );
        if (empty($user)) {
            throw new Exception(
                esc_html(__('Invalid code provided.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_INVALID_RESET_PASSWORD_CODE)
            );
        }

        return $user;
    }
}
