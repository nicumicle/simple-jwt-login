<?php

namespace SimpleJWTLogin\Services\Oauth;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\ServerCall;

class GoogleOauth extends AbstractOauth
{
    const PROVIDER_SLUG   = 'google';
    const IIS             = 'accounts.google.com';
    const AUTH_URL        = 'https://accounts.google.com/o/oauth2/auth';
    const TOKEN_ENDPOINT  = 'https://accounts.google.com/o/oauth2/token';
    const CHECK_TOKEN_URL = 'https://oauth2.googleapis.com/tokeninfo?id_token=%s';

    // -------------------------------------------------------------------------
    // AbstractOauth hooks
    // -------------------------------------------------------------------------

    protected function getTokenEndpoint()
    {
        return self::TOKEN_ENDPOINT;
    }

    public function getAuthUrl()
    {
        return self::AUTH_URL;
    }

    protected function getClientId()
    {
        return $this->settings->getIntegrationsSettings()->google()->getClientId();
    }

    protected function getClientSecret()
    {
        return $this->settings->getIntegrationsSettings()->google()->getClientSecret();
    }

    protected function getSavedRedirectUri()
    {
        return $this->settings->getIntegrationsSettings()->google()->getExchangeCodeRedirectUri();
    }

    protected function getProviderSlug()
    {
        return self::PROVIDER_SLUG;
    }

    protected function isCreateUserEnabled()
    {
        return $this->settings->getIntegrationsSettings()->google()->isCreateUserIfNotExistsEnabled();
    }

    /**
     * The id_token returned by the token endpoint is only trustworthy once Google
     * itself has confirmed its signature, audience and email_verified claim. Never
     * trust the email decoded locally from the raw token.
     *
     * @param array $tokenResponse
     * @return string
     * @throws Exception
     */
    protected function getEmailFromTokenResponse($tokenResponse)
    {
        if (empty($tokenResponse['id_token'])) {
            throw new Exception(
                esc_html(__('The provided id_token is invalid', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN)
            );
        }

        $tokenInfo = self::validateIdToken($tokenResponse['id_token'], $this->getClientId());

        return $tokenInfo['email'];
    }

    /**
     * @param string $token
     * @return void
     * @throws Exception
     */
    protected function validateProviderToken($token)
    {
        self::validateIdToken($token, $this->getClientId());
    }

    protected function getInvalidTokenErrorCode()
    {
        return ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN;
    }

    protected function getUserNotFoundErrorCode()
    {
        return ErrorCodes::ERR_GOOGLE_USER_NOT_FOUND;
    }

    protected function getTokenParamName()
    {
        return 'id_token';
    }

    protected function getMissingParamErrorCode()
    {
        return ErrorCodes::ERR_MISSING_GOOGLE_PARAM;
    }

    protected function getInvalidCodeErrorCode()
    {
        return ErrorCodes::ERR_GOOGLE_INVALID_CODE;
    }

    /**
     * The email must come from the claims Google validated, never from the raw
     * token decoded locally (which carries no signature guarantee).
     *
     * @param string $token
     * @return string
     * @throws Exception
     */
    protected function getEmailFromDirectToken($token)
    {
        $tokenInfo = self::validateIdToken($token, $this->getClientId());

        return $tokenInfo['email'];
    }

    // -------------------------------------------------------------------------
    // Google-specific public helper (used by AuthenticateService / views)
    // -------------------------------------------------------------------------

    /**
     * Validate a Google id_token against Google's tokeninfo endpoint, then verify
     * the returned claims bind the token to this website. Returns the validated
     * claims so callers use Google-confirmed values instead of decoding the raw token.
     *
     * A Google signature alone proves nothing: any Google OAuth client can mint a
     * valid id_token for any Google account. The 'aud' claim is what binds the token
     * to this website.
     *
     * @param string $idToken
     * @param string $clientId Configured Google OAuth client ID to assert against the token's aud claim.
     * @return array The claims returned by Google, already validated.
     * @throws Exception
     */
    public static function validateIdToken($idToken, $clientId)
    {
        $statusCode  = 400;
        $plainResult = '';
        $tokenInfo   = ServerCall::get(
            sprintf(self::CHECK_TOKEN_URL, $idToken),
            [],
            $statusCode,
            $plainResult
        );

        if ($statusCode !== 200) {
            throw new Exception(
                esc_html(__('The provided id_token is invalid', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN)
            );
        }

        return self::validateTokenInfoClaims($tokenInfo, $clientId);
    }

    /**
     * Assert that the claims Google returned come from Google, were issued for this
     * website, carry an email and mark that email as verified.
     *
     * @param array|null $tokenInfo Claims returned by the Google tokeninfo endpoint.
     * @param string $clientId
     * @return array
     * @throws Exception
     */
    public static function validateTokenInfoClaims($tokenInfo, $clientId)
    {
        if (empty($tokenInfo) || !is_array($tokenInfo) || empty($tokenInfo['email'])) {
            throw new Exception(
                esc_html(__('The provided id_token is invalid', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN)
            );
        }

        $validIssuers = [self::IIS, 'https://' . self::IIS];
        $tokenIss     = isset($tokenInfo['iss']) ? $tokenInfo['iss'] : '';
        if (!in_array($tokenIss, $validIssuers, true)) {
            throw new Exception(
                esc_html(__('The provided id_token has an invalid issuer', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN)
            );
        }

        $tokenAud = isset($tokenInfo['aud']) ? $tokenInfo['aud'] : '';
        if ($clientId === '' || $tokenAud === '' || (string)$tokenAud !== (string)$clientId) {
            throw new Exception(
                esc_html(__('The provided id_token was not issued for this website.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_GOOGLE_ID_TOKEN_INVALID_AUDIENCE)
            );
        }

        // The tokeninfo endpoint returns every claim as a string, so 'true' is the expected value.
        $emailVerified = isset($tokenInfo['email_verified']) ? $tokenInfo['email_verified'] : false;
        if ($emailVerified !== true && $emailVerified !== 'true' && $emailVerified !== '1') {
            throw new Exception(
                esc_html(__('The email address of this Google account is not verified.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_GOOGLE_ID_TOKEN_EMAIL_NOT_VERIFIED)
            );
        }

        return $tokenInfo;
    }
}
