<?php

namespace SimpleJwtLoginTests\Unit\ThirdParty;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class ForceLoginTest extends TestCase
{
    /**
     * The force-login filter runs on every REST request. It must reuse the
     * settings object built once at plugin bootstrap instead of decoding the
     * settings option again on every request.
     */
    public function testFilterReusesBootstrapSettings()
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                '_schema_version' => SimpleJWTLoginSettings::SCHEMA_VERSION,
                'integrations'    => [
                    '3rdparty' => ['force_login' => ['enabled' => true]],
                ],
            ]));
        $wordPressData->method('getSiteUrl')->willReturn('http://test.com');

        $jwtSettings = new SimpleJWTLoginSettings($wordPressData);
        $routeNamespace = $jwtSettings->getGeneralSettings()->getRouteNamespace();

        $_SERVER['HTTP_HOST'] = 'test.com';
        $_SERVER['REQUEST_URI'] = '/wp-json/' . $routeNamespace . 'auth';
        $GLOBALS['simple_jwt_login_test_filters'] = [];

        // simple-jwt-login.php defines this variable before including the integration file.
        extract(['simpleJwtLoginJwtSettings' => $jwtSettings]);
        require __DIR__ . '/../../../simple-jwt-login/3rd-party/force_login.php';

        $filter = $GLOBALS['simple_jwt_login_test_filters']['rest_authentication_errors'][0];

        $this->assertTrue($filter(null));
    }
}
