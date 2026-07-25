<?php

namespace SimpleJWTLogin\Services\Applications;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Libraries\ServerCall;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Services\RouteService;

class Google extends BaseApplication implements ApplicationInterface
{
    const IIS = "accounts.google.com";
    const AUTH_URL = "https://accounts.google.com/o/oauth2/auth";
    const CHECK_TOKEN_URL = "https://oauth2.googleapis.com/tokeninfo?id_token=%s";

    public function validate()
    {
        if (!isset($this->request['code']) && !isset($this->request['id_token'])) {
            throw new Exception(
                __('The code or id_token parameter is missing from request.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_GOOGLE_PARAM
            );
        }
    }

    /**
     * Verifies that the id_token was signed by Google AND that it was issued for this website.
     *
     * @SuppressWarnings(StaticAccess)
     * @param string $idToken
     * @param string $clientId
     * @return array The claims returned by Google, already validated.
     * @throws Exception
     */
    public static function validateIdToken($idToken, $clientId)
    {
        $statusCode = 400;
        $plainResult = '';
        $tokenInfo = ServerCall::get(
            sprintf(self::CHECK_TOKEN_URL, $idToken),
            [],
            $statusCode,
            $plainResult
        );
        if ($statusCode != 200) {
            throw new Exception(
                __("The provided id_token is invalid", 'simple-jwt-login'),
                ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN
            );
        }

        return self::validateTokenInfoClaims($tokenInfo, $clientId);
    }

    /**
     * A Google signature alone proves nothing: any Google OAuth client can mint a valid id_token
     * for any Google account. The 'aud' claim is what binds the token to this website.
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
                __("The provided id_token is invalid", 'simple-jwt-login'),
                ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN
            );
        }

        $issuer = isset($tokenInfo['iss']) ? $tokenInfo['iss'] : '';
        if (!in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            throw new Exception(
                __("The provided id_token is invalid", 'simple-jwt-login'),
                ErrorCodes::ERR_GOOGLE_INVALID_ID_TOKEN
            );
        }

        $audience = isset($tokenInfo['aud']) ? $tokenInfo['aud'] : '';
        if ($clientId === '' || $audience === '' || (string)$audience !== (string)$clientId) {
            throw new Exception(
                __('The provided id_token was not issued for this website.', 'simple-jwt-login'),
                ErrorCodes::ERR_GOOGLE_ID_TOKEN_INVALID_AUDIENCE
            );
        }

        // The tokeninfo endpoint returns every claim as a string, so 'true' is the expected value.
        $emailVerified = isset($tokenInfo['email_verified']) ? $tokenInfo['email_verified'] : false;
        if ($emailVerified !== true && $emailVerified !== 'true' && $emailVerified !== '1') {
            throw new Exception(
                __('The email address of this Google account is not verified.', 'simple-jwt-login'),
                ErrorCodes::ERR_GOOGLE_ID_TOKEN_EMAIL_NOT_VERIFIED
            );
        }

        return $tokenInfo;
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws \Exception
     */
    public function call()
    {
        $applicationsSettings = $this->settings->getApplicationsSettings();

        switch (true) {
            case $this->requestMethod == ServerCall::REQUEST_METHOD_GET:
                // This will generate the oauth Link
                $this->handleOauth($this->request['code']);
                break;
            case !empty($this->request['code']):
                $result = $this->exchangeCode(
                    $this->request['code'],
                    $applicationsSettings->getGoogleExchangeCodeRedirectUri()
                );

                $responseStatusCode = $result['status_code'];
                $jsonResult = $result['response'];

                if ($responseStatusCode == 200) {
                    return [
                        'success' => true,
                        'data' => $jsonResult,
                    ];
                }
                throw new Exception(
                    __(
                        'The code you provided is invalid.' . $this->handleErrorMessage($jsonResult),
                        'simple-jwt-login'
                    ),
                    ErrorCodes::ERR_GOOGLE_INVALID_CODE
                );
            case !empty($this->request['id_token']):
                // Use the claims validated by Google, never the ones decoded from the raw token.
                $tokenInfo = self::validateIdToken(
                    $this->request['id_token'],
                    $applicationsSettings->getGoogleClientID()
                );

                $user = $this->wordPressData->getUserDetailsByEmail(
                    $this->wordPressData->sanitizeTextField($tokenInfo['email'])
                );
                if (empty($user)) {
                    throw new Exception(
                        __('Wrong user credentials.', 'simple-jwt-login'),
                        ErrorCodes::ERR_GOOGLE_USER_NOT_FOUND
                    );
                }

                $payload = AuthenticateService::generatePayload(
                    [],
                    $this->wordPressData,
                    $this->settings,
                    $user
                );
                $response = [
                    'success' => true,
                    'data' => [
                        'jwt' => JWT::encode(
                            $payload,
                            JwtKeyFactory::getFactory($this->settings)->getPrivateKey(),
                            $this->settings->getGeneralSettings()->getJWTDecryptAlgorithm()
                        )
                    ]
                ];

                return $response;
        }

        return [];
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @param string $code
     * @param string $redirectUri
     * @return array
     */
    public function exchangeCode($code, $redirectUri)
    {
        $params = [
            'body' => [
                'client_id' => $this->settings->getApplicationsSettings()->getGoogleClientID(),
                'client_secret' => $this->settings->getApplicationsSettings()->getGoogleClientSecret(),
                'redirect_uri' => $redirectUri,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ],
        ];

        $responseStatusCode = 500;
        $plainResult = null;
        $jsonResult = ServerCall::post(
            "https://accounts.google.com/o/oauth2/token",
            $params,
            $responseStatusCode,
            $plainResult
        );

        return [
            'status_code' => $responseStatusCode,
            'response' => $jsonResult,
        ];
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * Handle OAuth code and redirects to the correct page
     *
     * @param string $code
     */
    public function handleOauth($code)
    {
        try {
            $redirectUri = $this->settings->generateExampleLink(
                RouteService::OAUTH_TOKEN,
                ['provider' => 'google']
            );
            $result = $this->exchangeCode($code, str_replace("&amp;", "&", $redirectUri));

            $responseStatusCode = $result['status_code'];
            $jsonResult = $result['response'];

            if ($responseStatusCode !== 200) {
                $this->redirect($this->wordPressData->getLoginURL([
                    'error' => $this->handleErrorMessage($jsonResult)
                ]));

                return;
            }

            $jwt = JWT::extractDataFromJwt($jsonResult['id_token']);
            $email = $jwt['payload']['email'];
            $user = $this->wordPressData->getUserDetailsByEmail($email);

            if ($user == null) {
                if ($this->settings->getApplicationsSettings()->isGoogleCreateUserIfNotExistsEnabled()) {
                    $user = $this->createUser($email);

                    $this->wordPressData->loginUser($user);
                    $this->redirect($this->wordPressData->getAdminUrl());

                    return;
                }

                $this->redirect($this->wordPressData->getLoginURL([]));

                return;
            }

            $this->wordPressData->loginUser($user);
            $this->redirect($this->wordPressData->getAdminUrl());

            return;
        } catch (Exception $e) {
            $this->redirect($this->wordPressData->getLoginURL(['error' => $e->getMessage()]));
        }
    }

    private function redirect($url)
    {
        if ($this->settings->getGeneralSettings()->isSafeRedirectEnabled()) {
            $this->wordPressData->redirectSafe($url);

            return;
        }

        $this->wordPressData->redirect($url);
    }

    /**
     * @param string[] $jsonResult
     * @return string
     */
    private function handleErrorMessage($jsonResult)
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
