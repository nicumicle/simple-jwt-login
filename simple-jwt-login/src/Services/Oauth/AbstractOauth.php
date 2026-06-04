<?php

namespace SimpleJWTLogin\Services\Oauth;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\ServerCall;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;
use SimpleJWTLogin\Services\RouteService;

/**
 * Base class for OAuth2 / OIDC provider integrations.
 *
 * Implements the common OAuth2 authorization-code flow via the Template Method pattern.
 * Concrete providers (Google, Auth0, …) override the abstract hook methods to supply
 * provider-specific URLs, credentials, and token parsing logic.
 */
abstract class AbstractOauth extends BaseOauth implements OauthInterface
{
    /**
     * WordPress login-form action used for browser-based OAuth + 2FA.
     * Handled by OAuthTwoFactorLoginHandler hooked into login_form_{action}.
     */
    const BROWSER_2FA_ACTION = 'sjl-oauth-2fa';
    // -------------------------------------------------------------------------
    // Template-method hooks - implement in each concrete provider
    // -------------------------------------------------------------------------

    /**
     * Token exchange endpoint (authorization code → tokens).
     *
     * @return string
     */
    abstract protected function getTokenEndpoint();

    /**
     * OAuth2 authorization URL (used to build the login button form action).
     *
     * @return string
     */
    abstract public function getAuthUrl();

    /**
     * Provider client ID from settings.
     *
     * @return string
     */
    abstract protected function getClientId();

    /**
     * Provider client secret from settings.
     *
     * @return string
     */
    abstract protected function getClientSecret();

    /**
     * Redirect URI stored in settings for the authorization-code exchange.
     *
     * @return string
     */
    abstract protected function getSavedRedirectUri();

    /**
     * Provider slug used in route parameters (e.g. 'google', 'auth0').
     *
     * @return string
     */
    abstract protected function getProviderSlug();

    /**
     * Whether the provider should auto-create a WP user when none is found.
     *
     * @return bool
     */
    abstract protected function isCreateUserEnabled();

    /**
     * Extract the user's email address from the token endpoint response.
     *
     * @param array $tokenResponse Decoded JSON response from the token endpoint.
     * @return string
     * @throws Exception
     */
    abstract protected function getEmailFromTokenResponse($tokenResponse);

    /**
     * Validate a provider-issued token that was passed directly by the client.
     * Implementations should throw an Exception if the token is not valid.
     *
     * @param string $token
     * @return void
     * @throws Exception
     */
    abstract protected function validateProviderToken($token);

    /**
     * Error code to use when the provider token is considered invalid.
     *
     * @return int
     */
    abstract protected function getInvalidTokenErrorCode();

    /**
     * Error code to use when no WP user matches the provider identity.
     *
     * @return int
     */
    abstract protected function getUserNotFoundErrorCode();

    /**
     * Request parameter name for the direct token flow (e.g. 'id_token' or 'access_token').
     *
     * @return string
     */
    abstract protected function getTokenParamName();

    /**
     * Error code when both 'code' and the token parameter are missing from the request.
     *
     * @return int
     */
    abstract protected function getMissingParamErrorCode();

    /**
     * Error code when code exchange returns a non-200 response.
     *
     * @return int
     */
    abstract protected function getInvalidCodeErrorCode();

    /**
     * Extract the user's email from a provider token passed directly by the client.
     *
     * @param string $token
     * @return string
     * @throws Exception
     */
    abstract protected function getEmailFromDirectToken($token);

    // -------------------------------------------------------------------------
    // OauthInterface - validate() and call()
    // -------------------------------------------------------------------------

    /**
     * @throws Exception
     */
    public function validate()
    {
        $tokenParam = $this->getTokenParamName();
        if (!isset($this->request['code']) && !isset($this->request[$tokenParam])) {
            throw new Exception(
                esc_html(sprintf(
                    // translators: %s = token parameter name
                    __('The code or %1$s parameter is missing from request.', 'simple-jwt-login'),
                    $tokenParam
                )),
                absint($this->getMissingParamErrorCode())
            );
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function call()
    {
        $tokenParam = $this->getTokenParamName();

        switch (true) {
            case $this->requestMethod === ServerCall::REQUEST_METHOD_GET:
                $this->handleOauth($this->request['code']);
                break;
            case !empty($this->request['code']):
                $result = $this->exchangeCode(
                    $this->request['code'],
                    $this->getSavedRedirectUri()
                );
                if ($result['status_code'] === 200) {
                    return array('success' => true, 'data' => $result['response']);
                }
                throw new Exception(
                    esc_html(__('The code you provided is invalid.', 'simple-jwt-login'))
                        . esc_html($this->handleErrorMessage($result['response'])),
                    absint($this->getInvalidCodeErrorCode())
                );
            case !empty($this->request[$tokenParam]):
                $token = $this->request[$tokenParam];
                $this->validateProviderToken($token);
                $email = $this->getEmailFromDirectToken($token);

                return $this->createWpJwtForEmail($email);
        }

        return array();
    }

    // -------------------------------------------------------------------------
    // Shared OAuth2 implementation
    // -------------------------------------------------------------------------

    /**
     * Exchange an authorization code for provider tokens.
     *
     * @param string $code
     * @param string $redirectUri
     * @return array{status_code: int, response: array}
     */
    public function exchangeCode($code, $redirectUri)
    {
        $response = wp_remote_post(
            $this->getTokenEndpoint(),
            [
                'body' => [
                    'client_id'     => $this->getClientId(),
                    'client_secret' => $this->getClientSecret(),
                    'redirect_uri'  => $redirectUri,
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                ],
            ]
        );

        return [
            'status_code' => (int) wp_remote_retrieve_response_code($response),
            'response'    => json_decode(wp_remote_retrieve_body($response), true),
        ];
    }

    /**
     * Handle the browser-based OAuth redirect flow.
     * Called when the provider redirects back to the site with ?code=…
     *
     * @param string $code
     * @return void
     */
    public function handleOauth($code)
    {
        try {
            $redirectUri = $this->settings->generateExampleLink(
                RouteService::OAUTH_TOKEN,
                ['provider' => $this->getProviderSlug()]
            );
            $result = $this->exchangeCode($code, str_replace('&amp;', '&', $redirectUri));

            if ($result['status_code'] !== 200 || !empty($result['response']['error'])) {
                $errorMessage = $this->handleErrorMessage($result['response']);
                $this->wordPressData->doAction(
                    SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_FAILED,
                    null,
                    null,
                    $errorMessage
                );
                $this->doRedirect($this->wordPressData->getLoginURL([
                    'error' => $errorMessage,
                ]));

                return;
            }

            $email = $this->getEmailFromTokenResponse($result['response']);
            $user  = $this->wordPressData->getUserDetailsByEmail($email);

            if (empty($user)) {
                if ($this->isCreateUserEnabled()) {
                    $user = $this->createUser($email);
                    if ($this->loginWithTwoFactorCheck($user)) {
                        return;
                    }
                    $this->wordPressData->loginUser($user);
                    $this->wordPressData->doAction(
                        SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_SUCCESS,
                        $this->wordPressData->getUserProperty($user, 'ID'),
                        $this->wordPressData->getUserProperty($user, 'user_email')
                    );
                    $this->doRedirect($this->wordPressData->getAdminUrl());

                    return;
                }

                $this->wordPressData->doAction(
                    SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_FAILED,
                    null,
                    $email,
                    __('User not found.', 'simple-jwt-login')
                );
                $this->doRedirect($this->wordPressData->getLoginURL([]));

                return;
            }

            if ($this->loginWithTwoFactorCheck($user)) {
                return;
            }

            $this->wordPressData->loginUser($user);
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_SUCCESS,
                $this->wordPressData->getUserProperty($user, 'ID'),
                $this->wordPressData->getUserProperty($user, 'user_email')
            );

            $this->doRedirect($this->wordPressData->getAdminUrl());
        } catch (Exception $exception) {
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_FAILED,
                null,
                null,
                $exception->getMessage()
            );
            $this->doRedirect($this->wordPressData->getLoginURL(['error' => $exception->getMessage()]));
        }
    }

    /**
     * Validate a provider token, look up the matching WP user, and return a WP JWT.
     * Returns a 2FA challenge response instead if the user has 2FA configured.
     *
     * @param string $email  Email extracted from the token (or empty to derive it internally).
     * @return array
     * @throws Exception
     */
    protected function createWpJwtForEmail($email)
    {
        $user = $this->wordPressData->getUserDetailsByEmail(
            $this->wordPressData->sanitizeTextField($email)
        );

        if (empty($user)) {
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_FAILED,
                null,
                $email,
                __('Wrong user credentials.', 'simple-jwt-login')
            );
            throw new Exception(
                esc_html(__('Wrong user credentials.', 'simple-jwt-login')),
                absint($this->getUserNotFoundErrorCode())
            );
        }

        $challenge = $this->handleTwoFactorChallengeForOauth($user);
        if ($challenge !== null) {
            return $challenge;
        }

        $this->wordPressData->doAction(
            SimpleJWTLoginHooks::AUDIT_AUTH_OAUTH_SUCCESS,
            $this->wordPressData->getUserProperty($user, 'ID'),
            $this->wordPressData->getUserProperty($user, 'user_email')
        );

        $payload = AuthenticateService::generatePayload(
            [],
            $this->wordPressData,
            $this->settings,
            $user
        );

        return [
            'success' => true,
            'data'    => [
                'jwt' => $this->getJwtWrapper()->encode(
                    $payload,
                    JwtKeyFactory::getFactory($this->settings)->getPrivateKey(),
                    $this->settings->getGeneralSettings()->getJWTDecryptAlgorithm()
                ),
            ],
        ];
    }

    /**
     * Check if 2FA is required for the user and build a challenge response array.
     * Returns null when 2FA is not active or the user does not have it configured.
     *
     * @param mixed $user WP_User
     * @return array|null
     * @throws Exception
     */
    protected function handleTwoFactorChallengeForOauth($user)
    {
        $tfaSettings = $this->settings->getIntegrationsSettings()->twoFactor();
        if (!$tfaSettings->isEnabled()) {
            return null;
        }

        $bridge = $this->getTwoFactorBridge();
        if (!$bridge->isAvailable() || !$bridge->isUserUsing2FA($user)) {
            return null;
        }

        $result = $this->buildInterimJwt($user, $tfaSettings, $bridge);

        return [
            'success' => true,
            'data'    => [
                'jwt'                 => $result['jwt'],
                'two_factor_required' => true,
                'provider'            => $result['provider'],
            ],
        ];
    }

    /**
     * If the user has 2FA configured, generate an interim JWT, redirect to the
     * plugin-owned 2FA login page, and return true. The 2FA login page shows an
     * HTML form — only after the user enters a valid code is the session started.
     * Returns false when 2FA is not required (caller proceeds with normal login).
     *
     * @param mixed $user WP_User
     * @return bool
     * @throws Exception
     */
    protected function loginWithTwoFactorCheck($user)
    {
        $bridge = $this->getTwoFactorBridge();
        if (!$bridge->isAvailable() || !$bridge->isUserUsing2FA($user)) {
            return false;
        }

        $userId = (int) $this->wordPressData->getUserProperty($user, 'ID');
        $nonce  = $bridge->createNonce($userId);
        if ($nonce === false) {
            throw new Exception(
                esc_html(__('Unable to create two-factor nonce.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_TWO_FACTOR_INVALID_NONCE)
            );
        }

        $provider      = $bridge->getPrimaryProvider($user);
        $providerClass = $provider !== null ? get_class($provider) : '';

        $twoFactorUrl = $this->wordPressData->getLoginURL([
            'action'        => 'validate_2fa',
            'provider'      => $providerClass,
            'wp-auth-id'    => (string) $userId,
            'wp-auth-nonce' => $nonce['key'],
            'redirect_to'   => (string) $this->wordPressData->getAdminUrl(),
            'rememberme'    => '0',
        ]);

        $this->doHtmlRedirect((string) $twoFactorUrl);

        return true;
    }

    /**
     * Output a minimal HTML page that redirects the browser via meta-refresh and
     * JavaScript. Used for the browser-based OAuth flow where the REST API framework
     * has already set Content-Type: application/json, making a plain HTTP 302
     * unreliable for rendering the destination as HTML.
     *
     * @param string $url
     * @return void
     * @SuppressWarnings(ExitExpression)
     */
    protected function doHtmlRedirect($url)
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        echo '<!DOCTYPE html><html><head>'
            . '<meta http-equiv="refresh" content="0;url=' . esc_url($url) . '">'
            . '<script>window.location.replace(' . wp_json_encode($url) . ');</script>'
            . '</head><body></body></html>';
        exit;
    }

    /**
     * Create a login nonce, encode an interim JWT with the 2FA state, and
     * optionally trigger email token delivery for Two_Factor_Email users.
     * Returns ['jwt' => string, 'provider' => string].
     *
     * @param mixed $user
     * @param mixed $tfaSettings  TwoFactorSettings instance
     * @param TwoFactorBridge $bridge
     * @return array{jwt: string, provider: string}
     * @throws Exception
     */
    protected function buildInterimJwt($user, $tfaSettings, $bridge)
    {
        $userId = (int) $this->wordPressData->getUserProperty($user, 'ID');
        $nonce  = $bridge->createNonce($userId);
        if ($nonce === false) {
            throw new Exception(
                esc_html(__('Unable to create two-factor nonce.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_TWO_FACTOR_INVALID_NONCE)
            );
        }

        $provider      = $bridge->getPrimaryProvider($user);
        $providerClass = $provider !== null ? get_class($provider) : '';

        $ttlSeconds    = $tfaSettings->getInterimTtl() * 60;
        $interimPayload = [
            'iat'                                  => time(),
            'exp'                                  => time() + $ttlSeconds,
            AuthenticateService::TFA_PENDING_CLAIM => 1,
            'tfa_user_id'                          => $userId,
            'tfa_nonce'                            => $nonce['key'],
            'tfa_provider'                         => $providerClass,
        ];

        $jwt = $this->getJwtWrapper()->encode(
            $interimPayload,
            JwtKeyFactory::getFactory($this->settings)->getPrivateKey(),
            $this->settings->getGeneralSettings()->getJWTDecryptAlgorithm()
        );

        if ($provider !== null
            && strpos($providerClass, 'Two_Factor_Email') !== false
            && method_exists($provider, 'generate_and_email_token')
        ) {
            $provider->generate_and_email_token($user);
        }

        return ['jwt' => $jwt, 'provider' => $providerClass];
    }

    /**
     * Redirect the browser, respecting the safe-redirect setting.
     *
     * @param string $url
     * @return void
     */
    protected function doRedirect($url)
    {
        if ($this->settings->getGeneralSettings()->isSafeRedirectEnabled()) {
            $this->wordPressData->redirectSafe($url);

            return;
        }

        $this->wordPressData->redirect($url);
    }

    /**
     * Parse a human-readable error string from a provider JSON error response.
     *
     * @param array $jsonResult
     * @return string
     */
    protected function handleErrorMessage($jsonResult)
    {
        $error = '';

        if (isset($jsonResult['error_description'])) {
            $error = ucfirst($jsonResult['error_description']) . '.';
        }
        if (isset($jsonResult['error'])) {
            $error .= ($error !== '' ? ' ' : '') . ucfirst($jsonResult['error']);
        }

        return $error;
    }
}
