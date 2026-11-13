<?php

namespace SimpleJwtLoginTests\Unit\ThirdParty;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use WP_Error;

class ForceLoginTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wp']);
        parent::tearDown();
    }

    /**
     * The filter must never return true (that skips core's cookie nonce check = CSRF)
     * and may only clear Force Login's own error on a route WordPress resolved
     * inside the plugin namespace.
     *
     * @return array<string, array{bool, string|null, string, string, string}>
     */
    public static function filterProvider()
    {
        return [
            'force login error on plugin route is cleared' => [
                true, 'rest_unauthorized', '/simple-jwt-login/v1/auth', '/wp-json/simple-jwt-login/v1/auth', 'null',
            ],
            'plain permalink plugin route is cleared' => [
                true, 'rest_unauthorized', '/simple-jwt-login/v1/auth', '/?rest_route=/simple-jwt-login/v1/auth', 'null',
            ],
            'integration disabled keeps the error' => [
                false, 'rest_unauthorized', '/simple-jwt-login/v1/auth', '/wp-json/simple-jwt-login/v1/auth', 'same',
            ],
            'core route keeps the error' => [
                true, 'rest_unauthorized', '/wp/v2/users', '/wp-json/wp/v2/users', 'same',
            ],
            'duplicated rest_route dispatching a core route keeps the error' => [
                true, 'rest_unauthorized', '/wp/v2/users', '/?rest_route=/simple-jwt-login/v1/&rest_route=/wp/v2/users', 'same',
            ],
            'invalid cookie nonce on plugin route is kept' => [
                true, 'rest_cookie_invalid_nonce', '/simple-jwt-login/v1/auth', '/wp-json/simple-jwt-login/v1/auth', 'same',
            ],
            'invalid cookie nonce in the reported exploit is kept' => [
                true, 'rest_cookie_invalid_nonce', '/wp/v2/users', '/?rest_route=/simple-jwt-login/v1/&rest_route=/wp/v2/users', 'same',
            ],
            'other plugin error on plugin route is kept' => [
                true, 'ip_blocked', '/simple-jwt-login/v1/auth', '/wp-json/simple-jwt-login/v1/auth', 'same',
            ],
            'no error stays null, never true' => [
                true, null, '/simple-jwt-login/v1/auth', '/wp-json/simple-jwt-login/v1/auth', 'null',
            ],
        ];
    }

    #[DataProvider('filterProvider')]
    public function testFilter($enabled, $errorCode, $resolvedRoute, $requestUri, $expected)
    {
        $filter = $this->loadFilter($enabled, $resolvedRoute, $requestUri);
        $input  = $errorCode === null ? null : new WP_Error($errorCode, 'blocked');

        $result = $filter($input);

        $this->assertNotTrue($result);
        if ($expected === 'null') {
            $this->assertNull($result);
            return;
        }
        $this->assertSame($input, $result);
    }

    /**
     * @param bool $enabled
     * @param string $resolvedRoute
     * @param string $requestUri
     * @return callable
     */
    private function loadFilter($enabled, $resolvedRoute, $requestUri)
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                '_schema_version' => SimpleJWTLoginSettings::SCHEMA_VERSION,
                'integrations'    => [
                    '3rdparty' => ['force_login' => ['enabled' => $enabled]],
                ],
            ]));
        $wordPressData->method('getSiteUrl')->willReturn('http://test.com');

        $_SERVER['HTTP_HOST']   = 'test.com';
        $_SERVER['REQUEST_URI'] = $requestUri;
        $GLOBALS['wp']          = (object) ['query_vars' => ['rest_route' => $resolvedRoute]];
        $GLOBALS['simple_jwt_login_test_filters'] = [];

        // simple-jwt-login.php defines this variable before including the integration file.
        extract(['simpleJwtLoginJwtSettings' => new SimpleJWTLoginSettings($wordPressData)]);
        require __DIR__ . '/../../../simple-jwt-login/3rd-party/force_login.php';

        return $GLOBALS['simple_jwt_login_test_filters']['rest_authentication_errors'][0];
    }
}
