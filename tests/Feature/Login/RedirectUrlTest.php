<?php

namespace SimpleJwtLoginTests\Feature\Login;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Services\BaseService;
use SimpleJwtLoginTests\Feature\TestBase;

class RedirectUrlTest extends TestBase
{
    /**
     * @return array<string,mixed>
     */
    private static function baseSettings(): array
    {
        return [
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => 'test',
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'register_ip'             => '',
            'register_domain'         => '',
            'require_register_auth'   => false,
            'allow_delete'            => true,
            'require_delete_auth'     => false,
            'delete_ip'               => '',
            'delete_user_by'          => 0,
            'jwt_delete_by_parameter' => 'email',
            'jwt_login_by'            => 0,
            'jwt_login_by_parameter'  => 'email',
            'allow_autologin'         => true,
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::baseSettings());
    }

    #[TestDox('NO_REDIRECT returns a JSON success response instead of a redirect')]
    public function testNoRedirectReturnsJsonResponse(): void
    {
        [$email, $password, $statusCode] = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'redirect' => LoginSettings::NO_REDIRECT,
        ]));

        try {
            $response = $this->client->get(
                self::API_URL . '?rest_route=/simple-jwt-login/v1/autologin&JWT=' . $jwt
            );

            $this->assertSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertTrue($body['success']);
            $this->assertArrayHasKey('message', $body);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            $this->deleteUser($jwt);
        }
    }

    #[TestDox('REDIRECT_DASHBOARD sends a 302 to wp-admin')]
    public function testRedirectToDashboard(): void
    {
        [$email, $password, $statusCode] = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'redirect' => LoginSettings::REDIRECT_DASHBOARD,
        ]));

        try {
            $response = $this->client->get(
                self::API_URL . '?rest_route=/simple-jwt-login/v1/autologin&JWT=' . $jwt,
                ['allow_redirects' => false]
            );

            $this->assertSame(302, $response->getStatusCode());
            $this->assertStringContainsString('wp-admin', $response->getHeaderLine('Location'));
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            $this->deleteUser($jwt);
        }
    }

    #[TestDox('REDIRECT_HOMEPAGE sends a 302 to the site URL')]
    public function testRedirectToHomepage(): void
    {
        [$email, $password, $statusCode] = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        $originalSiteUrl = $this->getWpOptionValue('siteurl');
        $originalHome    = $this->getWpOptionValue('home');
        $this->updateWpOption('siteurl', self::API_URL);
        $this->updateWpOption('home', self::API_URL);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'redirect' => LoginSettings::REDIRECT_HOMEPAGE,
        ]));

        try {
            $response = $this->client->get(
                self::API_URL . '?rest_route=/simple-jwt-login/v1/autologin&JWT=' . $jwt,
                ['allow_redirects' => false]
            );

            $this->assertSame(302, $response->getStatusCode());
            $location = $response->getHeaderLine('Location');
            $this->assertStringContainsString(self::API_URL, $location);
            $this->assertStringNotContainsString('wp-admin', $location);
        } finally {
            $this->updateWpOption('siteurl', $originalSiteUrl);
            $this->updateWpOption('home', $originalHome);
            self::updateSimpleJWTOption(self::baseSettings());
            $this->deleteUser($jwt);
        }
    }

    #[TestDox('REDIRECT_CUSTOM sends a 302 to the configured custom URL')]
    public function testRedirectToCustomUrl(): void
    {
        [$email, $password, $statusCode] = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        $customUrl = self::API_URL . '/my-custom-page';

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'redirect'     => LoginSettings::REDIRECT_CUSTOM,
            'redirect_url' => $customUrl,
        ]));

        try {
            $response = $this->client->get(
                self::API_URL . '?rest_route=/simple-jwt-login/v1/autologin&JWT=' . $jwt,
                ['allow_redirects' => false]
            );

            $this->assertSame(302, $response->getStatusCode());
            $this->assertStringContainsString('/my-custom-page', $response->getHeaderLine('Location'));
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            $this->deleteUser($jwt);
        }
    }

    #[TestDox('redirectUrl query parameter overrides the redirect destination when the option is enabled')]
    public function testRedirectUrlParameterOverridesDestinationWhenAllowed(): void
    {
        [$email, $password, $statusCode] = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        $overrideUrl = self::API_URL . '/override-target';

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'redirect'                       => LoginSettings::REDIRECT_DASHBOARD,
            'allow_usage_redirect_parameter' => true,
        ]));

        try {
            $response = $this->client->get(
                self::API_URL . '?rest_route=/simple-jwt-login/v1/autologin&JWT=' . $jwt
                    . '&' . BaseService::REDIRECT_URL_PARAMETER . '=' . urlencode($overrideUrl),
                ['allow_redirects' => false]
            );

            $this->assertSame(302, $response->getStatusCode());
            $location = $response->getHeaderLine('Location');
            $this->assertStringContainsString('/override-target', $location);
            $this->assertStringNotContainsString('wp-admin', $location);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            $this->deleteUser($jwt);
        }
    }

    #[TestDox('redirectUrl query parameter is ignored when the option is disabled')]
    public function testRedirectUrlParameterIgnoredWhenNotAllowed(): void
    {
        [$email, $password, $statusCode] = $this->registerRandomUser();
        $this->assertSame(200, $statusCode, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        $overrideUrl = self::API_URL . '/override-target';

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'redirect'                       => LoginSettings::REDIRECT_DASHBOARD,
            'allow_usage_redirect_parameter' => false,
        ]));

        try {
            $response = $this->client->get(
                self::API_URL . '?rest_route=/simple-jwt-login/v1/autologin&JWT=' . $jwt
                    . '&' . BaseService::REDIRECT_URL_PARAMETER . '=' . urlencode($overrideUrl),
                ['allow_redirects' => false]
            );

            $this->assertSame(302, $response->getStatusCode());
            $location = $response->getHeaderLine('Location');
            $this->assertStringContainsString('wp-admin', $location);
            $this->assertStringNotContainsString('/override-target', $location);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            $this->deleteUser($jwt);
        }
    }
}
