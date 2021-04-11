<?php

namespace SimpleJwtLoginTests\Modules;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\RouteService;
use SimpleJWTLogin\Modules\SimpleJWTLoginService;

class RouteServiceTest extends TestCase
{

    public function testGetAllRoutesReturnsCorrectArray()
    {
        $routeService = new RouteService();
        $this->assertTrue(!empty($routeService->getAllRoutes()));
    }

    public function testRouteServiceUsersEndpointInvalidMethod()
    {
        $this->expectExceptionMessage('Invalid method for this route.');
        $routeService = new RouteService();
        $routeService->makeAction(RouteService::USER_ROUTE, 'UNEXISTING_METHING');
    }

    public function testRouteServiceUsersEndpointInvalidEndpoint()
    {
        $this->expectExceptionMessage('Invalid route name.');
        $routeService = new RouteService();
        $routeService->makeAction('some-invalid-endpoint', 'POST');
    }

    public function testMakeAction()
    {
        $jwtServiceMock = $this->getMockBuilder(SimpleJWTLoginService::class)
            ->getMock();
        $jwtServiceMock->method('doLogin')->willReturn(true);
        $jwtServiceMock->method('validateRegisterUser')->willReturn(true);
        $jwtServiceMock->method('createUser')->willReturn(true);
        $jwtServiceMock->method('deleteUser')->willReturn(true);
        $jwtServiceMock->method('authenticateUser')->willReturn(true);
        $jwtServiceMock->method('refreshJwt')->willReturn(true);
        $jwtServiceMock->method('validateAuth')->willReturn(true);
        $jwtServiceMock->method('revokeToken')->willReturn(true);

        $routeService = new RouteService();
        $routeService->withService($jwtServiceMock);
        $allRoutes = $routeService->getAllRoutes();
        foreach ($allRoutes as $route) {
            $result = $routeService->makeAction($route['name'], $route['method']);
            $this->assertTrue($result);
        }
    }
}
