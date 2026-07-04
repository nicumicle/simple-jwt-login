<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException as ExceptionsValidationException;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Helpers\JwtPayloadHelper;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Jwt\JwtInterface;
use SimpleJWTLogin\Modules\Jwt\JwtWrapper;
use SimpleJWTLogin\Modules\AuthCodeBuilder;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;
use SimpleJWTLogin\Services\Oauth\Auth0Oauth;

abstract class BaseService
{
    const REDIRECT_URL_PARAMETER = 'redirectUrl';

    /**
     * @var string
     */
    protected $requestMethod;

    /**
     * @var SimpleJWTLoginSettings
     */
    protected $jwtSettings;

    const JWT_LEEWAY = 60; // seconds

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
     * @var RefreshTokenRepositoryInterface
     */
    protected $tokenRepository;

    /**
     * @var RevokedTokenRepositoryInterface
     */
    protected $revokedTokenRepo;

    /**
     * @var WebhookLogRepositoryInterface|null
     */
    protected $webhookLogRepository;

    /**
     * @var JwtInterface|null
     */
    protected $jwtWrapper;

    /**
     * @param string $requestMethod
     * @return $this
     */
    public function withRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
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
     * @param RefreshTokenRepositoryInterface $repository
     * @return $this
     */
    public function withRefreshTokenRepository(RefreshTokenRepositoryInterface $repository)
    {
        $this->tokenRepository = $repository;

        return $this;
    }

    /**
     * @param RevokedTokenRepositoryInterface $repository
     * @return $this
     */
    public function withRevokedTokenRepository(RevokedTokenRepositoryInterface $repository)
    {
        $this->revokedTokenRepo = $repository;

        return $this;
    }

    /**
     * @param WebhookLogRepositoryInterface $repository
     * @return $this
     */
    public function withWebhookLogRepository(WebhookLogRepositoryInterface $repository)
    {
        $this->webhookLogRepository = $repository;

        return $this;
    }

    /**
     * @return JwtInterface
     */
    protected function getJwtWrapper()
    {
        if ($this->jwtWrapper === null) {
            $this->jwtWrapper = new JwtWrapper();
        }

        return $this->jwtWrapper;
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
                        esc_html(
                            sprintf(
                                /* translators: 1: JWT property name, 2: settings parameter */
                                __('Unable to find user %1$s property in JWT.( Settings: %2$s )', 'simple-jwt-login'),
                                $value,
                                $parameter
                            )
                        ),
                        absint(ErrorCodes::ERR_UNABLE_TO_FIND_PROPERTY_FOR_USER_IN_JWT)
                    );
                }
                $payload = $payload[$value];
            }

            return (string)$payload;
        }

        if (!empty($payload['tfa_pending'])) {
            throw new Exception(
                esc_html(__('This JWT requires two-factor verification before it can be used.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REJECTED)
            );
        }

        if (!isset($payload[$parameter])) {
            throw new Exception(
                esc_html(
                    sprintf(
                        /* translators: %s: JWT property name */
                        __('Unable to find user %s property in JWT.', 'simple-jwt-login'),
                        $parameter
                    )
                ),
                absint(ErrorCodes::ERR_JWT_PARAMETER_FOR_USER_NOT_FOUND)
            );
        }

        return $payload[$parameter];
    }

    /**
     * Extract the JWT from the configured request header only.
     * Returns null when header extraction is disabled or no valid token is present.
     *
     * @return string|null
     */
    public function getJwtFromHeader()
    {
        $generalSettings = $this->jwtSettings->getGeneralSettings();
        if (!$generalSettings->isJwtFromHeaderEnabled()) {
            return null;
        }

        $headers = array_change_key_case($this->serverHelper->getHeaders(), CASE_LOWER);
        $headerKey = strtolower($generalSettings->getRequestKeyHeader());
        if (!isset($headers[$headerKey])) {
            return null;
        }

        $matches = [];
        $match = preg_match(
            '/^(?:(\w+)\s+)?([\w\-.]+)$/mi',
            $headers[$headerKey],
            $matches
        );

        $hasBearer = $match && strtolower($matches[1]) === 'bearer';
        $noPrefix  = $match && empty($matches[1]);

        if ($generalSettings->isJwtFromHeaderBearerRequired()) {
            return $hasBearer ? $matches[2] : null;
        }

        return ($hasBearer || $noPrefix) ? $matches[2] : null;
    }

    /**
     * @return string|null
     */
    public function getJwtFromRequestHeaderOrCookie()
    {
        $headerJwt = $this->getJwtFromHeader();
        if (!empty($headerJwt)) {
            return $headerJwt;
        }

        $generalSettings = $this->jwtSettings->getGeneralSettings();
        if ($generalSettings->isJwtFromCookieEnabled()) {
            $cookieKey = $generalSettings->getRequestKeyCookie();
            if (isset($this->cookie[$cookieKey])) {
                return $this->cookie[$cookieKey];
            }
        }

        if ($generalSettings->isJwtFromSessionEnabled()) {
            $sessionKey = $generalSettings->getRequestKeySession();
            if (isset($this->session[$sessionKey])) {
                return $this->session[$sessionKey];
            }
        }

        $requestUrlKey = strtolower($generalSettings->getRequestKeyUrl());
        $request = array_change_key_case($this->request, CASE_LOWER);

        return $generalSettings->isJwtFromURLEnabled() && isset($request[$requestUrlKey])
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
        $tokenHash = hash('sha256', $jwt);
        if ($this->revokedTokenRepo->existsForUser($userId, $tokenHash)) {
            throw new Exception(esc_html(__('This JWT is invalid.', 'simple-jwt-login')), absint(ErrorCodes::ERR_REVOKED_TOKEN));
        }

        return true;
    }

    /**
     * @param string $parameter
     *
     * @return mixed|string
     * @throws Exception
     */
    protected function validateJWTAndGetUserValueFromPayload($parameter)
    {
        $jwtParts = $this->extractJwtData($this->jwt);
        $iss = isset($jwtParts['payload']['iss']) ? (string)$jwtParts['payload']['iss'] : null;

        if ($iss === GoogleOauth::IIS) {
            $googleSettings = $this->jwtSettings->getIntegrationsSettings()->google();
            if ($googleSettings->isEnabled() && $googleSettings->isAllowedOnAllEndpoints()) {
                GoogleOauth::validateIdToken($this->jwt, $googleSettings->getClientId());

                return $jwtParts['payload']['email'];
            }
        }
        $auth0Settings = $this->jwtSettings->getIntegrationsSettings()->auth0();
        if ($auth0Settings->isEnabled()
            && !empty($auth0Settings->getDomain())
            && $iss === $auth0Settings->getDomain()
            && $auth0Settings->isAllowedOnAllEndpoints()
        ) {
            Auth0Oauth::validateIdToken($this->jwt, $this->jwtSettings);

            return $jwtParts['payload']['sub'];
        }

        $ruleConfig = $this->jwtSettings->getJwtRulesSettings()->findMatchingRuleConfig($jwtParts);

        $jwtKey = JwtKeyFactory::getFactoryFromConfig($this->jwtSettings, $ruleConfig);

        $algorithm = ($ruleConfig !== null)
            ? $ruleConfig['algorithm']
            : $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm();

        $jwtWrapper = $this->getJwtWrapper();
        $jwtWrapper->applyLeeway(self::JWT_LEEWAY);
        $decoded = (array)$jwtWrapper->decode(
            $this->jwt,
            $jwtKey->getPublicKey(),
            [$algorithm]
        );

        return $this->getUserParameterValueFromPayload($decoded, $parameter);
    }

    /**
     * @param string $userData
     *
     * @return object|null
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

        if (!$this->wordPressData->isInstanceOfuser($user)) {
            return null;
        }

        return $user;
    }


    /**
     * @throws Exception
     */
    protected function validateAuthKey()
    {
        $authCodeKey = $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey();
        if (!isset($this->request[$authCodeKey])) {
            throw new ExceptionsValidationException(
                esc_html(__('Auth Code is required.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_AUTH_CODE_REQUIRED)
            );
        }

        if (!$this->hasCorrectAuthCode($authCodeKey)) {
            throw new Exception(
                esc_html(
                    sprintf(
                        /* translators: %s: auth code key name */
                        __('Invalid Auth Code ( %s ) provided.', 'simple-jwt-login'),
                        $authCodeKey
                    )
                ),
                absint(ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED)
            );
        }
    }

    /**
     * @param string $authCodeKey
     * @return bool
    */
    private function hasCorrectAuthCode($authCodeKey)
    {
        $authCodes = $this->jwtSettings->getAuthCodesSettings()->getAuthCodes();
        foreach ($authCodes as $code) {
            $authCodeBuilder = new AuthCodeBuilder($code);
            if (!empty($authCodeBuilder->getExpirationDate())
                && (strtotime($authCodeBuilder->getExpirationDate()) < time())
            ) {
                continue;
            }
            if ($authCodeBuilder->getCode() === $this->request[$authCodeKey]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $jwt
     * @return array
     */
    protected function extractJwtData($jwt)
    {
        return $this->getJwtWrapper()->extractDataFromJwt($jwt);
    }

    /**
     * @param string $jwt
     * @return array|null
     */
    protected function getPayloadFromJWT($jwt)
    {
        return JwtPayloadHelper::decode($jwt);
    }

    /**
     * @param string $url
     * @return string
     */
    protected function includeRequestParameters($url)
    {
        if ($this->jwtSettings->getLoginSettings()->isRequestParametersIncluded()) {
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

            if (isset($requestParams[self::REDIRECT_URL_PARAMETER])) {
                 $redirectUrl = $requestParams[self::REDIRECT_URL_PARAMETER];
                 unset($requestParams[self::REDIRECT_URL_PARAMETER]);
                 $redirectUrl .=  $this->getDelimiter($redirectUrl)
                     . urldecode(http_build_query($requestParams));
                 return  $url
                     . $this->getDelimiter($url)
                     . self::REDIRECT_URL_PARAMETER . '=' . urlencode($redirectUrl);
            }

            return $url . $this->getDelimiter($url) . http_build_query($requestParams);
        }

        return $url;
    }

    private function getDelimiter($url)
    {
        return strpos($url, '?') !== false
            ? '&'
            : '?';
    }
}
