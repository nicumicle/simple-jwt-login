<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT;
use SimpleJWTLogin\Modules\AuthCodeBuilder;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
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
        $this->validateRegisterUser();

        return $this->createUser();
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @return WP_REST_Response|null
     * @throws Exception
     */
    public function createUser()
    {
        $email = esc_html($this->request['email']);
        $extraParameters = UserProperties::getExtraParametersFromRequest($this->request);
        $username = !empty($extraParameters['user_login'])
            ? esc_html($extraParameters['user_login'])
            : $email;

        if ($this->wordPressData->checkUserExistsByUsernameAndEmail($username, $email) == true) {
            throw new Exception(
                __('User already exists.', 'simple-jwt-login'),
                ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS
            );
        }

        $password = $this->jwtSettings->getRegisterSettings()->isRandomPasswordForCreateUserEnabled()
            ? $this->randomString(10)
            : esc_html($this->request['password']);

        $newUserRole = $this->jwtSettings->getRegisterSettings()->getNewUSerProfile();
        if (isset($this->request[$this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()])) {
            $authCodes = $this->jwtSettings->getAuthCodesSettings()->getAuthCodes();
            foreach ($authCodes as $code) {
                $authCodeBuilder = new AuthCodeBuilder($code);
                $authCodeKey = $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey();
                if ($authCodeBuilder->getCode() === $this->request[$authCodeKey]
                    && !empty($authCodeBuilder->getRole())
                ) {
                    $newUserRole = $authCodeBuilder->getRole();
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
        $userId = $this->wordPressData->getUserIdFromUser($user);

        if (!empty($this->request['user_meta'])) {
            $userMeta = json_decode($this->request['user_meta'], true);
            if ($userMeta === null
                && strpos($this->request['user_meta'], '\\"') !== false
            ) {
                $userMeta = json_decode(
                    stripslashes($this->request['user_meta']),
                    true
                );
            }
            $allowedUserMetaKeys = array_map(function ($value) {
                return trim($value);
            }, explode(',', $this->jwtSettings->getRegisterSettings()->getAllowedUserMeta()));

            if (is_array($userMeta) && !empty($userMeta)) {
                foreach ($userMeta as $metaKey => $metaValue) {
                    if (!in_array($metaKey, $allowedUserMetaKeys)) {
                        continue;
                    }
                    $this->wordPressData->addUserMeta($userId, esc_html($metaKey), esc_html($metaValue));
                }
            }
        }

        if ($this->jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::REGISTER_ACTION_NAME)) {
            $this->wordPressData->triggerAction(SimpleJWTLoginHooks::REGISTER_ACTION_NAME, $user, $password);
        }

        if ($this->jwtSettings->getLoginSettings()->isAutologinEnabled()
            && $this->jwtSettings->getRegisterSettings()->isForceLoginAfterCreateUserEnabled()
        ) {
            $this->wordPressData->loginUser($user);
            if ($this->jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::LOGIN_ACTION_NAME)) {
                $this->wordPressData->triggerAction(SimpleJWTLoginHooks::LOGIN_ACTION_NAME, $user);
            }

            return (new RedirectService())
                ->withRequest($this->request)
                ->withCookies($this->cookie)
                ->withSession($this->session)
                ->withSettings($this->jwtSettings)
                ->withUser($user)
                ->makeAction();
        }

        $userArray = $this->wordPressData->wordpressUserToArray($user);
        if (isset($userArray['user_pass'])) {
            unset($userArray['user_pass']);
        }

        $response = [
            'success' => true,
            'id' => $userId,
            'message' => __('User was successfully created.', 'simple-jwt-login'),
            'user' => $userArray,
            'roles' => $this->wordPressData->getUserRoles($user),
        ];

        if ($this->jwtSettings->getRegisterSettings()->isJwtEnabled()) {
            $payload = $this->initPayload($user);

            $response['jwt'] = JWT::encode(
                $payload,
                JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
                $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
            );
        }

        return $this->wordPressData->createResponse($response);
    }


    /**
     * @throws Exception
     */
    private function validateRegisterUser()
    {
        if ($this->jwtSettings->getRegisterSettings()->isRegisterAllowed() === false) {
            throw  new Exception(
                __('Register is not allowed.', 'simple-jwt-login'),
                ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED
            );
        }

        if ((
            $this->jwtSettings->getRegisterSettings()->isAuthKeyRequiredOnRegister()
                || isset($this->request[$this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()])
        ) && $this->validateAuthKey() === false
        ) {
            throw  new Exception(
                sprintf(
                    __('Invalid Auth Code ( %s ) provided.', 'simple-jwt-login'),
                    $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()
                ),
                ErrorCodes::ERR_REGISTER_INVALID_AUTH_KEY
            );
        }

        $allowedIPs = $this->jwtSettings->getRegisterSettings()->getAllowedRegisterIps();
        if (!empty($allowedIPs) && !$this->serverHelper->isClientIpInList($allowedIPs)) {
            throw new Exception(
                sprintf(
                    __('This IP[%s] is not allowed to register users.', 'simple-jwt-login'),
                    $this->serverHelper->getClientIP()
                ),
                ErrorCodes::ERR_REGISTER_IP_IS_NOT_ALLOWED
            );
        }


        if (!isset($this->request['email'])
            || (
                !isset($this->request['password'])
                && $this->jwtSettings->getRegisterSettings()->isRandomPasswordForCreateUserEnabled() === false
            )
        ) {
            throw new Exception(
                __('Missing email or password.', 'simple-jwt-login'),
                ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD
            );
        }

        if ($this->wordPressData->isEmail($this->request['email']) === false) {
            throw  new Exception(
                __('Invalid email address.', 'simple-jwt-login'),
                ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS
            );
        }

        if (!empty($this->jwtSettings->getRegisterSettings()->getAllowedRegisterDomain())) {
            $parts = explode('@', $this->request['email']);
            if (!isset($parts[1])
                || !in_array(
                    $parts[1],
                    array_map(
                        'trim',
                        explode(',', $this->jwtSettings->getRegisterSettings()->getAllowedRegisterDomain())
                    )
                )
            ) {
                throw new Exception(
                    __('This website does not allows users from this domain.', 'simple-jwt-login'),
                    ErrorCodes::ERR_REGISTER_DOMAIN_FOR_USER
                );
            }
        }
    }

    /**
     * @param int $length
     *
     * @return string
     */
    private function randomString($length = 8)
    {
        return $this->wordPressData->generatePassword($length);
    }

    /**
     * @SuppressWarnings(StaticAccess)
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
            ->getUserProperty($user, 'id');
        $username = $this->wordPressData
            ->getUserProperty($user, 'user_login');

        return [
            AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL    => $userEmail,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_ID       => $userId,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME => $username,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT      => time(),
        ];
    }
}
