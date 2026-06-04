<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Modules\AuthCodeBuilder;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\UserProperties;
use WP_REST_Response;

class RegisterUserService extends BaseService implements ServiceInterface
{
    const ACTION_NAME_CREATE_USER = 1;

    /**
     * @return WP_REST_Response|null
     * @throws Exception
     */
    public function makeAction()
    {
        try {
            $this->validateRegisterUser();

            return $this->createUser();
        } catch (Exception $exception) {
            $email = isset($this->request['email']) ? $this->request['email'] : null;
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_REGISTER_FAILED,
                null,
                $email,
                $exception->getMessage()
            );
            throw $exception;
        }
    }

    /**
     * @return WP_REST_Response|null
     * @throws Exception
     */
    public function createUser()
    {
        $registerSettings = $this->jwtSettings->getRegisterSettings();
        $hooksSettings = $this->jwtSettings->getHooksSettings();

        $email = $this->wordPressData->sanitizeTextField($this->request['email']);
        $extraParameters = UserProperties::getExtraParametersFromRequest($this->request);
        $username = !empty($extraParameters['user_login'])
            ? $this->wordPressData->sanitizeTextField($extraParameters['user_login'])
            : $email;

        if ($this->wordPressData->checkUserExistsByUsernameAndEmail($username, $email)) {
            throw new Exception(
                esc_html(__('User already exists.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS)
            );
        }

        $password = $registerSettings->isRandomPasswordForCreateUserEnabled()
            ? $this->wordPressData->generatePassword(
                $registerSettings->getRandomPasswordLength()
            )
            : $this->wordPressData->wpSlash($this->request['password']);

        $newUserRole = $registerSettings->getNewUserProfile();
        $authCodesSettings = $this->jwtSettings->getAuthCodesSettings();
        $authCodeKey = $authCodesSettings->getAuthCodeKey();
        if (isset($this->request[$authCodeKey])) {
            foreach ($authCodesSettings->getAuthCodes() as $code) {
                $authCodeBuilder = new AuthCodeBuilder($code);
                if ($authCodeBuilder->getCode() === $this->request[$authCodeKey]
                    && !empty($authCodeBuilder->getRole())
                ) {
                    $newUserRole = $authCodeBuilder->getRole();
                    break;
                }
            }
        }

        $user = $this->wordPressData->createUser(
            $username,
            $email,
            $password,
            $newUserRole,
            $extraParameters
        );
        $userId    = $this->wordPressData->getUserIdFromUser($user);
        $userEmail = (string) $this->wordPressData->getUserProperty($user, 'user_email');

        if (!empty($this->request['user_meta'])) {
            $userMeta = $this->resolveUserMeta($this->request['user_meta']);

            if (is_array($userMeta) && !empty($userMeta)) {
                $allowedUserMetaKeys = array_map(function ($value) {
                    return trim($value);
                }, explode(',', $registerSettings->getAllowedUserMeta()));

                foreach ($userMeta as $metaKey => $metaValue) {
                    if (!in_array($metaKey, $allowedUserMetaKeys, true)) {
                        continue;
                    }
                    $this->wordPressData->updateUserMeta(
                        $userId,
                        $this->wordPressData->sanitizeTextField($metaKey),
                        $this->wordPressData->sanitizeTextField($metaValue)
                    );
                }
            }
        }

        if ($registerSettings->isSendWelcomeEmailEnabled()) {
            $this->wordPressData->sendNewUserNotification($userId, $password);
        }

        if ($hooksSettings->isHookEnabled(SimpleJWTLoginHooks::REGISTER_ACTION_NAME)) {
            $this->wordPressData->doAction(SimpleJWTLoginHooks::REGISTER_ACTION_NAME, $user, $password);
        }

        $this->wordPressData->doAction(
            SimpleJWTLoginHooks::AUDIT_AUTH_REGISTER_SUCCESS,
            $userId,
            $userEmail
        );

        (new WebhooksService($this->jwtSettings, $this->webhookLogRepository))->dispatch(
            WebhooksSettings::EVENT_REGISTER,
            [
                'user_id'    => $userId,
                'user_email' => $userEmail,
            ]
        );

        if ($this->jwtSettings->getLoginSettings()->isAutologinEnabled()
            && $registerSettings->isForceLoginAfterCreateUserEnabled()
        ) {
            $this->wordPressData->loginUser($user);
            if ($hooksSettings->isHookEnabled(SimpleJWTLoginHooks::LOGIN_ACTION_NAME)) {
                $this->wordPressData->doAction(SimpleJWTLoginHooks::LOGIN_ACTION_NAME, $user);
            }

            return (new RedirectService())
                ->withRequest($this->request)
                ->withCookies($this->cookie)
                ->withSession($this->session)
                ->withSettings($this->jwtSettings)
                ->withUser($user)
                ->makeAction();
        }

        $raw = $this->wordPressData->wordpressUserToArray($user);
        unset($raw['user_pass'], $raw['ID']);

        $userArray = ['id' => $userId];
        foreach ($raw as $key => $value) {
            $newKey = (strpos($key, 'user_') === 0) ? substr($key, 5) : $key;
            $userArray[$newKey] = $value;
        }

        $userArray['roles'] = $this->wordPressData->getUserRoles($user);

        if ($registerSettings->isJwtEnabled()) {
            $payload = $this->initPayload($user);

            $userArray['jwt'] = $this->getJwtWrapper()->encode(
                $payload,
                JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
                $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
            );
        }

        $response = [
            'success' => true,
            'data'    => $userArray,
        ];

        if ($hooksSettings->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_REGISTER_USER)) {
            $response = $this->wordPressData
                ->applyFilters(
                    SimpleJWTLoginHooks::HOOK_RESPONSE_REGISTER_USER,
                    $response,
                    $user
                );
        }

        return $this->wordPressData->createResponse($response);
    }


    /**
     * @param mixed $rawUserMeta
     * @return mixed
     */
    private function resolveUserMeta($rawUserMeta)
    {
        if (is_array($rawUserMeta)) {
            return $this->wordPressData->sanitizeArray($rawUserMeta);
        }

        $sanitized = $this->wordPressData->sanitizeTextField($rawUserMeta);
        $decoded = json_decode($sanitized, true);
        if ($decoded === null && strpos($rawUserMeta, '\\"') !== false) {
            $decoded = json_decode(stripslashes($sanitized), true);
        }

        return $decoded;
    }

    /**
     * @throws Exception
     */
    private function validateRegisterUser()
    {
        $registerSettings = $this->jwtSettings->getRegisterSettings();

        if (!$registerSettings->isRegisterAllowed()) {
            throw new Exception(
                esc_html(__('Register is not allowed.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED)
            );
        }

        if ($registerSettings->isAuthKeyRequiredOnRegister()
            || isset($this->request[$this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()])
        ) {
            $this->validateAuthKey();
        }

        $allowedIPs = $registerSettings->getAllowedRegisterIps();
        if (!empty($allowedIPs) && !$this->serverHelper->isClientIpInList($allowedIPs)) {
            throw new Exception(
                esc_html(
                    sprintf(
                        /* translators: %s: client IP address */
                        __('This IP[%s] is not allowed to register users.', 'simple-jwt-login'),
                        $this->serverHelper->getClientIP()
                    )
                ),
                absint(ErrorCodes::ERR_REGISTER_IP_IS_NOT_ALLOWED)
            );
        }

        if (empty($this->request['email'])) {
            throw new ValidationException(
                esc_html(__('Missing email.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD)
            );
        }

        if (empty($this->request['password']) && !$registerSettings->isRandomPasswordForCreateUserEnabled()) {
            throw new ValidationException(
                esc_html(__('Missing password.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD)
            );
        }

        if (!$this->wordPressData->isEmail($this->request['email'])) {
            throw new ValidationException(
                esc_html(__('Invalid email address.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS)
            );
        }

        if (!empty($this->request['user_login']) && strlen($this->request['user_login']) > 60) {
            throw new ValidationException(
                esc_html(__('Username must be less than 60 characters.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS)
            );
        }
        if (empty($this->request['user_login']) && strlen($this->request['email']) > 60) {
            throw new ValidationException(
                esc_html(__('Email must be less than 60 characters.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS)
            );
        }

        $allowedDomain = $registerSettings->getAllowedRegisterDomain();
        if (!empty($allowedDomain)) {
            $parts = explode(
                '@',
                $this->wordPressData->sanitizeTextField($this->request['email'])
            );
            if (!isset($parts[1])
                || !in_array(
                    $parts[1],
                    array_map('trim', explode(',', $allowedDomain)),
                    true
                )
            ) {
                throw new Exception(
                    esc_html(__('This website does not allows users from this domain.', 'simple-jwt-login')),
                    absint(ErrorCodes::ERR_REGISTER_DOMAIN_FOR_USER)
                );
            }
        }
    }

    /**
     * @param \WP_User $user
     *
     * @return array
     */
    private function initPayload($user)
    {
        if ($this->jwtSettings->getAuthenticationSettings()->isAuthenticationEnabled()) {
            return AuthenticateService::generatePayload(
                [],
                $this->wordPressData,
                $this->jwtSettings,
                $user
            );
        }

        $userEmail = $this->wordPressData
            ->getUserProperty($user, 'user_email');
        $userId = $this->wordPressData
            ->getUserProperty($user, 'ID');
        $username = $this->wordPressData
            ->getUserProperty($user, 'user_login');
        $iss = $this->jwtSettings
            ->getAuthenticationSettings()->getAuthIss();

        return [
            AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL    => $userEmail,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_ID       => $userId,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME => $username,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT      => time(),
            AuthenticationSettings::JWT_PAYLOAD_PARAM_ISS      => $iss,
        ];
    }
}
