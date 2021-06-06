<?php
namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use WP_REST_Response;
use WP_User;

class RedirectService extends BaseService implements ServiceInterface
{

    const ACTION_NAME_REDIRECT = 1;
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
     * @param null|int $actionName
     * @return WP_REST_Response|null
     * @throws Exception
     */
    public function makeAction($actionName = null)
    {

        if ($actionName !== self::ACTION_NAME_REDIRECT) {
            throw new Exception('Invalid action provided.');
        }

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
            && isset($this->request['redirectUrl'])) {
            $url = $this->request['redirectUrl'];
        }

        if ($this->jwtSettings->getLoginSettings()->getShouldIncludeRequestParameters()) {
            $requestParams = $this->request;
            $dangerousKeys = [
                'rest_route',
                'jwt',
                'JWT',
                'email',
                'password',
                'redirectUrl',
                $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()
            ];
            foreach ($dangerousKeys as $key) {
                if (isset($requestParams[$key])) {
                    unset($requestParams[$key]);
                }
            }

            $url = $url . (strpos('?', $url) !== false ? '&' : '?') . http_build_query($requestParams);
        }

        if ($this->jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::LOGIN_REDIRECT_NAME)) {
            $this->wordPressData->triggerAction(SimpleJWTLoginHooks::LOGIN_REDIRECT_NAME, $url, $this->request);
        }

        $url = $this->replaceVariables($url, $user);

        if ($redirect === LoginSettings::NO_REDIRECT) {
            $response = [
                'success' => true,
                'message' => __('User was logged in.', 'simple-jwt-login'),
            ];
            if ($this->jwtSettings
                ->getHooksSettings()
                ->isHookEnable(SimpleJWTLoginHooks::NO_REDIRECT_RESPONSE)
            ) {
                $response = $this->wordPressData->triggerFilter(
                    SimpleJWTLoginHooks::NO_REDIRECT_RESPONSE,
                    $response,
                    $this->request
                );
            }
            return $this->wordPressData->createResponse($response);
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
        $replace = [
            '{{site_url}}' => site_url(),
            '{{user_id}}' => $user->get('id'),
            '{{user_email}}' => $user->get('user_email'),
            '{{user_login}}' => $user->get('user_login'),
            '{{user_first_name}}' => $user->get('first_name'),
            '{{user_last_name}}' => $user->get('last_name'),
            '{{user_nicename}}' => $user->get('user_nicename'),
        ];

        return str_replace(array_keys($replace), array_values($replace), $url);
    }
}