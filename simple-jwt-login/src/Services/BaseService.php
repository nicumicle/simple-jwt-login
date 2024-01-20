<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Libraries\ServerCall;
use SimpleJWTLogin\Modules\AuthCodeBuilder;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\Applications\Google;
use WP_User;

abstract class BaseService
{
    /**
     * @var string
     */
    protected $requestMetod;

    /**
     * @var SimpleJWTLoginSettings
     */
    protected $jwtSettings;

    const JWT_LEEVAY = 60; //seconds

    /**
     * @var array
     */
    protected $request;

    /**
     * @var string
     */
    protected $jwt = '';

    /**
     * @var WordPressDataInterface
     */
    protected $wordPressData;

    /**
     * @var array
     */
    protected $cookie;

    /**
     * @var array
     */
    protected $session;

    /**
     * @var ServerHelper
     */
    protected $serverHelper;

    /**
     * @param string $requestMethod
     * @return $this
     */
    public function withRequestMethod($requestMethod)
    {
        $this->requestMetod = $requestMethod;
        return $this;
    }

    /**
     * @param SimpleJWTLoginSettings $settings
     *
     * @return $this
     */
    public function withSettings(SimpleJWTLoginSettings $settings)
    {
        $this->jwtSettings = $settings;
        $this->wordPressData = $settings->getWordPressData();

        return $this;
    }

    /**
     * @param array $request
     *
     * @return $this
     */
    public function withRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param array $cookie
     *
     * @return $this
     */
    public function withCookies($cookie)
    {
        $this->cookie = $cookie;

        return $this;
    }

    /**
     * @param array $session
     *
     * @return $this
     */
    public function withSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @param ServerHelper $serverHelper
     * @return $this
     */
    public function withServerHelper(ServerHelper $serverHelper)
    {
        $this->serverHelper = $serverHelper;

        return $this;
    }

    /**
     * @param array $payload
     * @param string $parameter
     *
     * @return mixed|string
     * @throws Exception
     */
    protected function getUserParameterValueFromPayload($payload, $parameter)
    {
        if (strpos($parameter, '.') !== false) {
            $array = explode('.', $parameter);
            foreach ($array as $value) {
                $payload = (array)$payload;
                if (!isset($payload[$value])) {
                    throw new Exception(
                        sprintf(
                            __('Unable to find user %s property in JWT.( Settings: %s )', 'simple-jwt-login'),
                            $value,
                            $parameter
                        ),
                        ErrorCodes::ERR_UNABLE_TO_FIND_PROPERTY_FOR_USER_IN_JWT
                    );
                }
                $payload = $payload[$value];
            }

            return (string)$payload;
        }

        if (!isset($payload[$parameter])) {
            throw new Exception(
                sprintf(
                    __('Unable to find user %s property in JWT.', 'simple-jwt-login'),
                    $parameter
                ),
                ErrorCodes::ERR_JWT_PARAMETER_FOR_USER_NOT_FOUND
            );
        }

        return $payload[$parameter];
    }

    /**
     * @return string|null
     */
    public function getJwtFromRequestHeaderOrCookie()
    {
        if ($this->jwtSettings->getGeneralSettings()->isJwtFromHeaderEnabled()) {
            $headers = array_change_key_case($this->serverHelper->getHeaders(), CASE_LOWER);
            $headerKey = strtolower($this->jwtSettings->getGeneralSettings()->getRequestKeyHeader());
            if (isset($headers[$headerKey])) {
                $matches = [];
                preg_match(
                    '/^(?:Bearer)?[\s]*(.*)$/mi',
                    $headers[$headerKey],
                    $matches
                );

                if (isset($matches[1]) && !empty(trim($matches[1]))) {
                    return $matches[1];
                }
            }
        }
        if ($this->jwtSettings->getGeneralSettings()->isJwtFromCookieEnabled()) {
            if (isset($this->cookie[$this->jwtSettings->getGeneralSettings()->getRequestKeyCookie()])) {
                return $this->cookie[$this->jwtSettings->getGeneralSettings()->getRequestKeyCookie()];
            }
        }

        if ($this->jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
            if (isset($this->session[$this->jwtSettings->getGeneralSettings()->getRequestKeySession()])) {
                return $this->session[$this->jwtSettings->getGeneralSettings()->getRequestKeySession()];
            }
        }

        $requestUrlKey = strtolower($this->jwtSettings->getGeneralSettings()->getRequestKeyUrl());
        $request = array_change_key_case($this->request, CASE_LOWER);

        return $this->jwtSettings->getGeneralSettings()->isJwtFromURLEnabled() && isset($request[$requestUrlKey])
            ? $request[$requestUrlKey]
            : null;
    }

    /**
     * @param int $userId
     * @param string $jwt
     * @return bool
     * @throws Exception
     */
    protected function validateJwtRevoked($userId, $jwt)
    {
        $revokedTokensArray = $this->wordPressData->getUserMeta(
            $userId,
            SimpleJWTLoginSettings::REVOKE_TOKEN_KEY
        );

        if (empty($revokedTokensArray)) {
            return true;
        }
        foreach ($revokedTokensArray as $token) {
            if ($token === $jwt) {
                throw new Exception(__('This JWT is invalid.', 'simple-jwt-login'), ErrorCodes::ERR_REVOKED_TOKEN);
            }
        }

        return true;
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @param string $parameter
     *
     * @return mixed|string
     * @throws Exception
     */
    protected function validateJWTAndGetUserValueFromPayload($parameter)
    {
        $jwtParts = JWT::extractDataFromJwt($this->jwt);
        if (isset($jwtParts['payload']['iss'])) {
            switch ($jwtParts['payload']['iss']) {
                case Google::IIS:
                    if ($this->jwtSettings->getApplicationsSettings()->isGoogleEnabled()
                        && $this->jwtSettings->getApplicationsSettings()->isGoogleJwtAllowedOnAllEndpoints()) {
                        Google::validateIdToken($this->jwt);

                        return $jwtParts['payload']['email'];
                    }
                    break;
            }
        }

        JWT::$leeway = self::JWT_LEEVAY;
        $decoded = (array)JWT::decode(
            $this->jwt,
            JwtKeyFactory::getFactory($this->jwtSettings)->getPublicKey(),
            [$this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()]
        );

        return $this->getUserParameterValueFromPayload($decoded, $parameter);
    }

    /**
     * @param string $userData
     *
     * @return WP_User|null
     */
    protected function getUserDetails($userData)
    {
        switch ($this->jwtSettings->getLoginSettings()->getJWTLoginBy()) {
            case LoginSettings::JWT_LOGIN_BY_EMAIL:
                $user = $this->wordPressData->getUserDetailsByEmail($userData);
                break;
            case LoginSettings::JWT_LOGIN_BY_USER_LOGIN:
                $user = $this->wordPressData->getUserByUserLogin($userData);
                break;
            case LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID:
            default:
                $user = $this->wordPressData->getUserDetailsById((int)$userData);
                break;
        }

        if ($this->wordPressData->isInstanceOfuser($user) === false) {
            return null;
        }

        return $user;
    }


    /**
     * @return bool
     */
    protected function validateAuthKey()
    {
        $authCodeKey = $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey();
        if (!isset($this->request[$authCodeKey])) {
            return false;
        }
        foreach ($this->jwtSettings->getAuthCodesSettings()->getAuthCodes() as $code) {
            $authCodeBuilder = new AuthCodeBuilder($code);
            if (!empty($authCodeBuilder->getExpirationDate())
                && (strtotime($authCodeBuilder->getExpirationDate()) < time())
            ) {
                return false;
            }
            if ($authCodeBuilder->getCode() === $this->request[$authCodeKey]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $jwt
     * @return array|null
     */
    protected function getPayloadFromJWT($jwt)
    {
        $jwtParts = explode('.', $jwt);
        return isset($jwtParts[1])
            ? json_decode(base64_decode($jwtParts[1]), true)
            : null;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function includeRequestParameters($url)
    {
        if ($this->jwtSettings->getLoginSettings()->getShouldIncludeRequestParameters()) {
            $requestParams = $this->request;

            $dangerousKeys = array_map(function ($value) {
                return trim($value);
            }, explode(',', $this->jwtSettings->getLoginSettings()->getDangerousQueryParameters()));

            foreach ($dangerousKeys as $key) {
                if (isset($requestParams[$key])) {
                    unset($requestParams[$key]);
                }
            }
            if (empty($requestParams)) {
                return $url;
            }

            if (isset($requestParams['redirectUrl'])) {
                 $redirectUrl = $requestParams['redirectUrl'];
                 unset($requestParams['redirectUrl']);
                 $redirectUrl .=  $this->getDelimiter($redirectUrl)
                     . urldecode(http_build_query($requestParams));
                 return  $url
                     . $this->getDelimiter($url)
                     . 'redirectUrl=' . urlencode($redirectUrl);
            }

            return $url . $this->getDelimiter($url) . http_build_query($requestParams);
        }

        return $url;
    }

    private function getDelimiter($url)
    {
        if (strpos($url, '?') !== false) {
            return '&';
        }

        return '?';
    }
}
