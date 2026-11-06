<?php

namespace SimpleJwtLoginTests\Unit\Routes\Handlers;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\HooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Routes\Handlers\RouteHandler;
use SimpleJWTLogin\Services\BaseService;
use WP_REST_Response;

class RouteHandlerTest extends TestCase
{
    public function testServiceExceptionIsReturnedAsRestResponse()
    {
        $response = $this->buildHandler(RouteHandlerTestFailingService::class)->__invoke();

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(401, $response->get_status());
        $this->assertSame(
            [
                'success' => false,
                'data'    => [
                    'message'    => 'Invalid refresh token.',
                    'error_code' => ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH,
                ],
            ],
            $response->get_data()
        );
    }

    public function testServiceResultIsReturnedUnchanged()
    {
        $response = $this->buildHandler(RouteHandlerTestSuccessService::class)->__invoke();

        $this->assertSame(['success' => true], $response);
    }

    /**
     * @param string $serviceClass
     * @return RouteHandler
     */
    private function buildHandler($serviceClass)
    {
        $hooksSettings = $this->createStub(HooksSettings::class);
        $hooksSettings->method('isHookEnabled')->willReturn(false);
        $generalSettings = $this->createStub(GeneralSettings::class);
        $generalSettings->method('isJwtFromSessionEnabled')->willReturn(false);

        $jwtSettings = $this->createStub(SimpleJWTLoginSettings::class);
        $jwtSettings->method('getHooksSettings')->willReturn($hooksSettings);
        $jwtSettings->method('getGeneralSettings')->willReturn($generalSettings);

        return new RouteHandler(
            ['name' => 'auth/refresh', 'method' => 'POST', 'service' => $serviceClass],
            [],
            [],
            $jwtSettings,
            new ServerHelper(['REQUEST_METHOD' => 'POST']),
            $this->createStub(RefreshTokenRepositoryInterface::class),
            $this->createStub(RevokedTokenRepositoryInterface::class),
            null
        );
    }
}

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
class RouteHandlerTestFailingService extends BaseService
{
    public function makeAction()
    {
        throw new Exception('Invalid refresh token.', ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH);
    }
}

class RouteHandlerTestSuccessService extends BaseService
{
    public function makeAction()
    {
        return ['success' => true];
    }
}
