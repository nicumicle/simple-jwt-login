<?php

namespace SimpleJwtLoginTests\Feature\Autologin;

use GuzzleHttp\TransferStats;
use SimpleJwtLoginTests\Feature\TestBase;

class SuccessTest extends TestBase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption([
            "allow_authentication" => true,
            "jwt_payload" => ["email","exp","id","iss","site","username"],
            "jwt_auth_ttl" => 60,
            "jwt_auth_refresh_ttl" => "20160",
            "auth_ip" => "",
            "auth_requires_auth_code" => false,
            "auth_password_base64" => false,
            "jwt_auth_iss" => "tests",
            "decryption_key" => "test",
            // Register user
            "allow_register" => true,
            "new_user_profile" => "subscriber",
            "register_ip" => "",
            "register_domain" => "",
            "require_register_auth" => false,
            // Delete user
            "allow_delete" => true,
            "require_delete_auth" => false,
            "delete_ip" => "",
            "delete_user_by" => 0,
            "jwt_delete_by_parameter" => "email",
            // Autologin: We need this for refresh token
            "jwt_login_by" => 0,
            "jwt_login_by_parameter" => "email",
            "allow_autologin" => true,
            "security" => [
                "safe_redirect" => true,
            ]
        ]);
    }

    public function testSuccessAutologin()
    {
        $this->initClient(
            [
                'cookies' => true,
                'http_errors' => false,
                'allow_redirects' => [
                    'track_redirects' => true,
                ],
            ]
        );

        // Register random user
        list ($email, $password, $statusCode, $response) = $this->registerRandomUser();

        $this->assertSame(200, $statusCode, "Unable to register user");

        // Auth new USer
        list ($statusCode, $responseContents) = $this->authUser($email, $password);

        $this->assertSame(
            200,
            $statusCode,
            "Autologin failed to authenticate user "
        );

        $authResponse = json_decode($responseContents, true);
        $this->assertArrayHasKey('data', $authResponse);
        $this->assertArrayHasKey('jwt', $authResponse['data']);
        $jwt = $authResponse['data']['jwt'];


        $siteURL = $this->getWpOptionValue('siteurl');
        $home = $this->getWpOptionValue('home');

        $this->updateWpOption('siteurl', self::API_URL);
        $this->updateWpOption('home', self::API_URL);

        $response = null;
        $finalURL = null;
        $exceptionThrown = false;

        try {
            $response = $this->client->get(
                self::API_URL . "?rest_route=/simple-jwt-login/v1/autologin&JWT=" . $jwt,
                [
                    'on_stats' => function (TransferStats $stats) use (&$finalURL) {
                        $finalURL = $stats->getEffectiveUri();
                    }
                ]
            );
        } catch (\Throwable $e) {
            $exceptionThrown = true;
        } finally {
            $this->updateWpOption('siteurl', $siteURL);
            $this->updateWpOption('home', $home);
        }
        $this->assertFalse($exceptionThrown);

        $this->assertNotNull($response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($finalURL);
        $this->assertSame(self::API_URL, $finalURL->getScheme() . "://" . $finalURL->getHost());
        $this->assertSame('/wp-admin/', $finalURL->getPath());
    }
}
