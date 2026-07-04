<?php


namespace SimpleJwtLoginTests\Unit\Services;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\RouteService;

class RouteServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RevokedTokenRepository
     */
    private $revokedTokenRepoMock;

    public function testGetAllRoutes()
    {
        $allRoutes = (new RouteService())
            ->getAllRoutes();
        $this->assertNotEmpty($allRoutes);
        foreach ($allRoutes as $route) {
            $this->assertArrayHasKey('name', $route);
            $this->assertArrayHasKey('method', $route);
            $this->assertArrayHasKey('service', $route);
        }
    }

    public function testUserNotFound()
    {
        $this->wordPressDataMock = $this
            ->createStub(WordPressDataInterface::class);
        $this->revokedTokenRepoMock = $this
            ->createStub(RevokedTokenRepository::class);

        $settings = [
            'decryption_key' => '123',
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter' => 'user',
        ];
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock
            ->method('isInstanceOfuser')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('getUserDetailsByEmail')
            ->willReturn(null);

        $routeService = (new RouteService())
            ->withSession([])
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressDataMock
                )
            )
            ->withRequest([])
            ->withCookies([])
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('WordPress User not found.');
        $jwt = JWT::encode(['user' => 'test'], '123');
        $routeService->getUserIdFromJWT($jwt);
    }

    public function testGetUserIdFromJWT()
    {
        $this->wordPressDataMock = $this
            ->createStub(WordPressDataInterface::class);
        $this->revokedTokenRepoMock = $this
            ->createStub(RevokedTokenRepository::class);

        $settings = [
            'decryption_key' => '123',
            'jwt_login_by' => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter' => 'user',
        ];
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock
            ->method('isInstanceOfuser')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('getUserDetailsByEmail')
            ->willReturn('123');
        $this->wordPressDataMock
            ->method('getUserProperty')
            ->willReturn(2);

        $routeService = (new RouteService())
            ->withSession([])
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressDataMock
                )
            )
            ->withRequest([])
            ->withCookies([])
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $jwt = JWT::encode(['user' => 'test'], '123');
        $userId = $routeService->getUserIdFromJWT($jwt);
        $this->assertSame(2, $userId);
    }
}
