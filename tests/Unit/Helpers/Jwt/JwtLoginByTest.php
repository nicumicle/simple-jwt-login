<?php

namespace SimpleJwtLoginTests\Unit\Helpers\Jwt;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\Jwt\JwtLoginBy;
use SimpleJWTLogin\Modules\Settings\LoginSettings;

class JwtLoginByTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>|null, array{login_by: int, parameter: string}}>
     */
    public static function resolveProvider(): array
    {
        return [
            'no rule uses the global settings' => [
                null,
                ['login_by' => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID, 'parameter' => 'id'],
            ],
            'rule settings win' => [
                ['login_by' => LoginSettings::JWT_LOGIN_BY_USER_LOGIN, 'login_by_parameter' => 'sub'],
                ['login_by' => LoginSettings::JWT_LOGIN_BY_USER_LOGIN, 'parameter' => 'sub'],
            ],
            'rule without login-by defaults to email, no claim' => [
                [],
                ['login_by' => LoginSettings::JWT_LOGIN_BY_EMAIL, 'parameter' => ''],
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null                    $ruleConfig
     * @param array{login_by: int, parameter: string}      $expected
     */
    #[DataProvider('resolveProvider')]
    public function testResolve($ruleConfig, array $expected): void
    {
        $loginSettings = $this->createStub(LoginSettings::class);
        $loginSettings->method('getJWTLoginBy')->willReturn(LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID);
        $loginSettings->method('getJwtLoginByParameter')->willReturn('id');

        $this->assertSame($expected, JwtLoginBy::resolve($loginSettings, $ruleConfig));
    }
}
