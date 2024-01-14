<?php
// https://developers.google.com/identity/openid-connect/openid-connect
namespace SimpleJWTLogin\Services\Applications;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Libraries\ServerCall;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Services\RedirectService;
use SimpleJWTLogin\Services\RouteService;

class Google extends BaseApplication implements ApplicationInterface
{
    const IIS = "accounts.google.com";
    const AUTH_URL = "https://accounts.google.com/o/oauth2/auth";

    public function validate()
    {
        if (!isset($this->request['code']) && !isset($this->request['jwt'])) {
            throw new Exception(
                __('The code or jwt parameter is missing from request.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_GOOGLE_PARAM
            );
        }
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @param string $jwt
     * @return void
     * @throws Exception
     */
    public static function validateJWT($jwt)
    {
        $statusCode = 400;
        $plainResult = '';
        ServerCall::get(
            "https://oauth2.googleapis.com/tokeninfo?id_token=" . $jwt,
            [],
            $statusCode,
            $plainResult
        );
        if ($statusCode != 200) {
            throw new Exception(
                __("Invalid JWT provided", 'simple-jwt-login'),
                ErrorCodes::ERR_GOOGLE_INVALID_JWT
            );
        }
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws \Exception
     */
    public function call()
    {
        switch (true) {
            case $this->requestMethod == ServerCall::REQUEST_METHOD_GET:
                // This will generate the oauth Link
                $this->handleOauth($this->request['code']);
                break;
            case !empty($this->request['code']):
                $result = $this->exchangeCode(
                    $this->request['code'],
                    $this->settings->getApplicationsSettings()->getGoogleRedirectURI()
                );

                $responseStatusCode = $result['status_code'];
                $jsonResult = $result['response'];

                if ($responseStatusCode == 200) {
                    return [
                        'success' => true,
                        'data' => $jsonResult,
                    ];
                }
                $error = "";

                if (isset($jsonResult['error_description'])) {
                    $error = " " . ucfirst($jsonResult['error_description']);
                }
                if (isset($jsonResult['error'])) {
                    $error = " " . ucfirst($jsonResult['error']);
                }
                throw new Exception(
                    __('The code you provided is invalid.' . $error, 'simple-jwt-login'),
                    ErrorCodes::ERR_GOOGLE_INVALID_CODE
                );
            case !empty($this->request['id_token']):
                $jwt = $this->request['id_token'];
                self::validateJWT($jwt);

                $decoded = JWT::extractDataFromJwt($jwt);

                $user = $this->wordPressData->getUserDetailsByEmail(
                    $this->wordPressData->sanitizeTextField($decoded['payload']['email'])
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
                $this->wordPressData->redirect($this->wordPressData->getLoginURL());
                return;
            }

            $jwt = JWT::extractDataFromJwt($jsonResult['id_token']);
            $email = $jwt['payload']['email'];
            $user = $this->wordPressData->getUserDetailsByEmail($email);


            $this->wordPressData->loginUser($user);

            $this->wordPressData->redirect($this->wordPressData->getAdminUrl());
            return;
        } catch (Exception $e) {

            $this->wordPressData->redirect($this->wordPressData->getLoginURL());
        }
    }
}