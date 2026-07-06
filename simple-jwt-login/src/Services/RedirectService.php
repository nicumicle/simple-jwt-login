<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use WP_REST_Response;
use WP_User;

class RedirectService extends BaseService implements ServiceInterface
{
    /**
     * @var WP_User
     */
    private $user;

    /**
     * @param WP_User $user
     * @return $this
     */
    public function withUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return WP_REST_Response|null
     * @throws Exception
     */
    public function makeAction()
    {
        $this->wordPressData = $this->jwtSettings->getWordPressData();

        return $this->redirectAfterLogin($this->user);
    }

    /**
     * Do the actual redirect after login
     *
     * @param WP_User $user
     * @return WP_REST_Response|null
     * @throws Exception
     */
    private function redirectAfterLogin($user)
    {
        $redirect = $this->jwtSettings->getLoginSettings()->getRedirect();

        switch ($redirect) {
            case LoginSettings::REDIRECT_HOMEPAGE:
                $url = $this->wordPressData->getSiteUrl();
                break;
            case LoginSettings::REDIRECT_CUSTOM:
                $url = $this->jwtSettings->getLoginSettings()->getCustomRedirectURL();
                break;
            case LoginSettings::REDIRECT_DASHBOARD:
            default:
                $url = $this->wordPressData->getAdminUrl();
                break;
        }

        if ($this->jwtSettings->getLoginSettings()->isRedirectParameterAllowed()
            && isset($this->request[self::REDIRECT_URL_PARAMETER])
            && !empty($this->request[self::REDIRECT_URL_PARAMETER])
        ) {
            $url = $this->wordPressData->sanitizeTextField($this->request[self::REDIRECT_URL_PARAMETER]);
        }

        $url = $this->includeRequestParameters($url);

        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::LOGIN_REDIRECT_NAME)) {
            $this->wordPressData->doAction(SimpleJWTLoginHooks::LOGIN_REDIRECT_NAME, $url, $this->request);
        }

        $url = $this->replaceVariables($url, $user);

        if ($redirect === LoginSettings::NO_REDIRECT) {
            $response = [
                'success' => true,
                'message' => __('User was logged in.', 'simple-jwt-login'),
            ];
            if ($this->jwtSettings
                ->getHooksSettings()
                ->isHookEnabled(SimpleJWTLoginHooks::NO_REDIRECT_RESPONSE)
            ) {
                $response = $this->wordPressData->applyFilters(
                    SimpleJWTLoginHooks::NO_REDIRECT_RESPONSE,
                    $response,
                    $this->request
                );
            }

            return $this->wordPressData->createResponse($response);
        }

        if ($this->jwtSettings->getGeneralSettings()->isSafeRedirectEnabled()) {
             $this->wordPressData->redirectSafe($url);

             return null;
        }
        $this->wordPressData->redirect($url);

        return null;
    }

    /**
     * @param string $url
     * @param WP_User $user
     * @return string
     */
    private function replaceVariables($url, $user)
    {
        $candidates = [
            '{{site_url}}'        => $this->wordPressData->getSiteUrl(),
            '{{user_id}}'         => $this->wordPressData->getUserProperty($user, 'ID'),
            '{{user_email}}'      => $this->wordPressData->getUserProperty($user, 'user_email'),
            '{{user_login}}'      => $this->wordPressData->getUserProperty($user, 'user_login'),
            '{{user_first_name}}' => $this->wordPressData->getUserProperty($user, 'first_name'),
            '{{user_last_name}}'  => $this->wordPressData->getUserProperty($user, 'last_name'),
            '{{user_nicename}}'   => $this->wordPressData->getUserProperty($user, 'user_nicename'),
        ];

        $replace = [];
        foreach ($candidates as $key => $value) {
            $replace[$key] = $value ?: $key;
        }

        return str_replace(array_keys($replace), array_values($replace), $url);
    }
}
