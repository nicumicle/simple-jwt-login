<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException as ExceptionsValidationException;
use SimpleJWTLogin\Helpers\CrossSiteRequestHelper;
use SimpleJWTLogin\Helpers\Jwt\JwtAudience;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Helpers\Jwt\JwtLoginBy;
use SimpleJWTLogin\Helpers\Jwt\PasswordChangeCutoff;
use SimpleJWTLogin\Helpers\JwtPayloadHelper;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Jwt\JwtInterface;
use SimpleJWTLogin\Modules\Jwt\JwtWrapper;
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
     * The JWT last split by extractJwtData(), and its parts.
     *
     * @var string|null
     */
    private $extractedJwt;

    /**
     * @var array
     */
    private $extractedData = [];

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
        // Checked before any claim is read, so no login-by parameter (flat or dotted) can skip it.
        if (!empty($payload[AuthenticateService::TFA_PENDING_CLAIM])) {
            throw new Exception(
                esc_html(__('This JWT requires two-factor verification before it can be used.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REJECTED)
            );
        }

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
        // Cookie and session JWTs are sent by the browser on its own: a CORS "*"
        // entry must never trust them, or any site could use them (CSRF).
        $ambientAllowed = $this->isTrustedRequest(false);
        $headerJwt = $ambientAllowed || $this->isTrustedRequest(true) ? $this->getJwtFromHeader() : null;
        if (!empty($headerJwt)) {
            return $headerJwt;
        }

        $ambientJwt = $ambientAllowed ? $this->getAmbientJwt() : null;
        if ($ambientJwt !== null) {
            return $ambientJwt;
        }

        $generalSettings = $this->jwtSettings->getGeneralSettings();
        $requestUrlKey = strtolower($generalSettings->getRequestKeyUrl());
        $request = array_change_key_case($this->request, CASE_LOWER);
        if (!$generalSettings->isJwtFromURLEnabled() || !isset($request[$requestUrlKey])) {
            return null;
        }

        // $_REQUEST may include cookies (request_order / variables_order): a cookie
        // named like the URL key is still an ambient credential.
        $cookies = array_change_key_case((array) $this->cookie, CASE_LOWER);
        if (!$ambientAllowed && isset($cookies[$requestUrlKey])) {
            return null;
        }

        return $request[$requestUrlKey];
    }

    /**
     * @return string|null
     */
    protected function getAmbientJwt()
    {
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

        return null;
    }

    /**
     * CSRF protection for every route that reads a JWT (plugin routes, the API
     * middleware on all endpoints, Protect Endpoints): a header, cookie or session
     * JWT only authenticates requests coming from this site or from an origin
     * listed in the CORS settings.
     * Without a ServerHelper the origin cannot be checked: fail closed.
     *
     * @param bool $allowWildcard Whether a CORS "*" entry trusts every origin.
     * @return bool
     */
    protected function isTrustedRequest($allowWildcard)
    {
        if ($this->serverHelper === null) {
            return false;
        }
        $trustedUrls = $this->wordPressData !== null ? [$this->wordPressData->getSiteUrl()] : [];

        return !CrossSiteRequestHelper::isCrossSite(
            $this->serverHelper,
            $trustedUrls,
            $this->corsTrustedOrigins($allowWildcard)
        );
    }

    /**
     * @param bool $allowWildcard
     * @return string[]
     */
    protected function corsTrustedOrigins($allowWildcard)
    {
        $corsSettings = $this->jwtSettings->getCorsSettings();
        if (!$corsSettings->isCorsEnabled() || !$corsSettings->isAllowOriginEnabled()) {
            return [];
        }
        if ($allowWildcard) {
            return $corsSettings->getAllowedOrigins();
        }

        return array_values(array_diff($corsSettings->getAllowedOrigins(), ['*']));
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
        $jwtParts  = $this->extractJwtData($jwt);
        $payload   = isset($jwtParts['payload']) ? (array) $jwtParts['payload'] : [];
        if ($this->revokedTokenRepo->existsForUser($userId, $tokenHash)
            || PasswordChangeCutoff::isRevokedByPasswordChange(
                $this->jwtSettings->getAuthenticationSettings(),
                $this->wordPressData,
                $userId,
                $payload
            )
        ) {
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
                // Use the claims Google validated, never the ones decoded from the raw token.
                $tokenInfo = GoogleOauth::validateIdToken($this->jwt, $googleSettings->getClientId());

                return $tokenInfo['email'];
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

        $userValue = $this->getUserParameterValueFromPayload($decoded, $parameter);
        JwtAudience::assertAccepted(
            $decoded,
            JwtAudience::expected($this->jwtSettings->getAuthenticationSettings(), $ruleConfig)
        );

        return $userValue;
    }

    /**
     * Validates the current JWT and returns its WordPress user, looked up with the login-by
     * settings of the JWT Rule it matches (else the global ones).
     *
     * @return object|null
     * @throws Exception
     */
    protected function getUserFromCurrentJwt()
    {
        $loginBy = JwtLoginBy::resolve(
            $this->jwtSettings->getLoginSettings(),
            $this->jwtSettings->getJwtRulesSettings()->findMatchingRuleConfig($this->extractJwtData($this->jwt))
        );

        return $this->getUserDetails(
            $this->validateJWTAndGetUserValueFromPayload($loginBy['parameter']),
            $loginBy['login_by']
        );
    }

    /**
     * @param string $userData
     * @param int    $loginBy LoginSettings::JWT_LOGIN_BY_*
     *
     * @return object|null
     */
    protected function getUserDetails($userData, $loginBy)
    {
        switch ((int) $loginBy) {
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
        $authCodesSettings = $this->jwtSettings->getAuthCodesSettings();
        $authCodeKey       = $authCodesSettings->getAuthCodeKey();
        if (!isset($this->request[$authCodeKey])) {
            throw new ExceptionsValidationException(
                esc_html(__('Auth Code is required.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_AUTH_CODE_REQUIRED)
            );
        }

        if ($authCodesSettings->findValidAuthCode($this->request[$authCodeKey]) === null) {
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
     * @param string $jwt
     * @return array
     */
    protected function extractJwtData($jwt)
    {
        // One request checks the same JWT several times (rule lookup, decode, revocation).
        if ($this->extractedJwt !== $jwt) {
            $this->extractedData = $this->getJwtWrapper()->extractDataFromJwt($jwt);
            $this->extractedJwt = $jwt;
        }

        return $this->extractedData;
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
            $dangerousKeys = array_map(function ($value) {
                return strtolower(trim($value));
            }, explode(',', $this->jwtSettings->getLoginSettings()->getDangerousQueryParameters()));
            // The JWT is read from its URL key in any letter case: never copy it into a redirect.
            $dangerousKeys[] = strtolower($this->jwtSettings->getGeneralSettings()->getRequestKeyUrl());

            $requestParams = [];
            foreach ((array) $this->request as $key => $value) {
                if (!in_array(strtolower((string) $key), $dangerousKeys, true)) {
                    $requestParams[$key] = $value;
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
