<?php

namespace SimpleJWTLogin\Services\Applications;

use Exception;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Libraries\ServerCall;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Services\RouteService;

/**
 * Base class for OAuth2 / OIDC provider integrations.
 *
 * Implements the common OAuth2 authorization-code flow via the Template Method pattern.
 * Concrete providers (Google, Auth0, …) override the abstract hook methods to supply
 * provider-specific URLs, credentials, and token parsing logic.
 */
abstract class AbstractOAuthApplication extends BaseApplication
{
    // -------------------------------------------------------------------------
    // Template-method hooks — implement in each concrete provider
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
        $params = [
            'body' => [
                'client_id'     => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'redirect_uri'  => $redirectUri,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
            ],
        ];

        $responseStatusCode = 500;
        $plainResult = null;
        $jsonResult = ServerCall::post(
            $this->getTokenEndpoint(),
            $params,
            $responseStatusCode,
            $plainResult
        );

        return [
            'status_code' => $responseStatusCode,
            'response'    => $jsonResult,
        ];
    }

    /**
     * Handle the browser-based OAuth redirect flow.
     * Called when the provider redirects back to the site with ?code=…
     *
     * @param string $code
     * @return void
     * @SuppressWarnings(StaticAccess)
     */
    public function handleOauth($code)
    {
        try {
            $redirectUri = $this->settings->generateExampleLink(
                RouteService::OAUTH_TOKEN,
                ['provider' => $this->getProviderSlug()]
            );
            $result = $this->exchangeCode($code, str_replace("&amp;", "&", $redirectUri));

            if ($result['status_code'] !== 200) {
                $this->doRedirect($this->wordPressData->getLoginURL([
                    'error' => $this->handleErrorMessage($result['response']),
                ]));

                return;
            }

            $email = $this->getEmailFromTokenResponse($result['response']);
            $user  = $this->wordPressData->getUserDetailsByEmail($email);

            if ($user === null) {
                if ($this->isCreateUserEnabled()) {
                    $user = $this->createUser($email);
                    $this->wordPressData->loginUser($user);
                    $this->doRedirect($this->wordPressData->getAdminUrl());

                    return;
                }

                $this->doRedirect($this->wordPressData->getLoginURL([]));

                return;
            }

            $this->wordPressData->loginUser($user);
            $this->doRedirect($this->wordPressData->getAdminUrl());
        } catch (Exception $e) {
            $this->doRedirect($this->wordPressData->getLoginURL(['error' => $e->getMessage()]));
        }
    }

    /**
     * Validate a provider token, look up the matching WP user, and return a WP JWT.
     *
     * @param string $token  Provider-issued token (id_token, access_token, …).
     * @param string $email  Email extracted from the token (or empty to derive it internally).
     * @return array
     * @throws Exception
     * @SuppressWarnings(StaticAccess)
     */
    protected function createWpJwtForEmail($email)
    {
        $user = $this->wordPressData->getUserDetailsByEmail(
            $this->wordPressData->sanitizeTextField($email)
        );

        if (empty($user)) {
            throw new Exception(
                __('Wrong user credentials.', 'simple-jwt-login'),
                $this->getUserNotFoundErrorCode()
            );
        }

        $payload = AuthenticateService::generatePayload(
            [],
            $this->wordPressData,
            $this->settings,
            $user
        );

        return [
            'success' => true,
            'data'    => [
                'jwt' => JWT::encode(
                    $payload,
                    JwtKeyFactory::getFactory($this->settings)->getPrivateKey(),
                    $this->settings->getGeneralSettings()->getJWTDecryptAlgorithm()
                ),
            ],
        ];
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
        $error = "";

        if (isset($jsonResult['error_description'])) {
            $error = ucfirst($jsonResult['error_description']) . ".";
        }
        if (isset($jsonResult['error'])) {
            $error .= ($error === "" ? " " : "") . ucfirst($jsonResult['error']);
        }

        return $error;
    }
}
