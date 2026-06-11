<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Oauth;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\Oauth\Auth0OauthSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class Auth0OauthSettingsTest extends TestCase
{
    private function make(): Auth0OauthSettings
    {
        return new Auth0OauthSettings();
    }

    private function wpData(): WordPressDataInterface
    {
        $stub = $this->createStub(WordPressDataInterface::class);
        $stub->method('sanitizeTextField')->willReturnArgument(0);

        return $stub;
    }

    public function testGetGroup(): void
    {
        $this->assertSame('auth0', $this->make()->getGroup());
    }

    public function testGetName(): void
    {
        $this->assertSame('Auth0', $this->make()->getName());
    }

    public static function getDomainProvider(): array
    {
        return [
            'set'     => [['domain' => 'myapp.auth0.com'], 'myapp.auth0.com'],
            'missing' => [[], ''],
        ];
    }

    #[DataProvider('getDomainProvider')]
    public function testGetDomain(array $data, string $expected): void
    {
        $this->assertSame($expected, $this->make()->withSettings($data)->getDomain());
    }

    public static function isExchangeTokenEnabledProvider(): array
    {
        return [
            'enabled'  => [['enable_exchange_token' => true], true],
            'disabled' => [['enable_exchange_token' => false], false],
            'missing'  => [[], false],
        ];
    }

    #[DataProvider('isExchangeTokenEnabledProvider')]
    public function testIsExchangeTokenEnabled(array $data, bool $expected): void
    {
        $this->assertSame($expected, $this->make()->withSettings($data)->isExchangeTokenEnabled());
    }

    public static function allowUnverifiedEmailProvider(): array
    {
        return [
            'enabled'  => [['allow_unverified_email' => true], true],
            'disabled' => [['allow_unverified_email' => false], false],
            'missing'  => [[], false],
        ];
    }

    #[DataProvider('allowUnverifiedEmailProvider')]
    public function testAllowUnverifiedEmail(array $data, bool $expected): void
    {
        $this->assertSame($expected, $this->make()->withSettings($data)->allowUnverifiedEmail());
    }

    public function testProcessPostIncludesExtraFields(): void
    {
        $post = [
            'auth0' => [
                'domain'                 => 'myapp.auth0.com',
                'enable_exchange_token'  => '1',
                'allow_unverified_email' => '1',
            ],
        ];
        $result = $this->make()->processPost($post, $this->wpData());

        $this->assertArrayHasKey('domain', $result);
        $this->assertArrayHasKey('enable_exchange_token', $result);
        $this->assertArrayHasKey('allow_unverified_email', $result);
        $this->assertSame('myapp.auth0.com', $result['domain']);
        $this->assertSame(true, $result['enable_exchange_token']);
        $this->assertSame(true, $result['allow_unverified_email']);
    }

    public function testProcessPostExtraFieldsDefaultWhenMissing(): void
    {
        $result = $this->make()->processPost(['auth0' => []], $this->wpData());

        $this->assertSame('', $result['domain']);
        $this->assertSame(false, $result['enable_exchange_token']);
        $this->assertSame(false, $result['allow_unverified_email']);
    }

    public function testValidatePassesWhenNotEnabled(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate(['auth0' => []]);
    }

    public function testValidatePassesWhenAllValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate([
            'auth0' => [
                'enabled'       => 1,
                'enable_oauth'  => 1,
                'domain'        => 'myapp.auth0.com',
                'client_id'     => 'a0-client-id',
                'client_secret' => 'a0-client-secret',
            ],
        ]);
    }

    public function testValidatePassesWithOnlyExchangeTokenEnabled(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate([
            'auth0' => [
                'enabled'               => 1,
                'enable_exchange_token' => 1,
                'domain'                => 'myapp.auth0.com',
                'client_id'             => 'a0-client-id',
                'client_secret'         => 'a0-client-secret',
            ],
        ]);
    }

    // generateCode(PREFIX_APPLICATIONS=12, errorCode) = 12000 + errorCode
    public static function validateThrowsProvider(): array
    {
        return [
            'no features enabled'   => [['enabled' => 1], 12006],
            'domain missing'        => [['enabled' => 1, 'enable_oauth' => 1], 12007],
            'client_id missing'     => [
                ['enabled' => 1, 'enable_oauth' => 1, 'domain' => 'myapp.auth0.com'],
                12008,
            ],
            'client_secret missing' => [
                ['enabled' => 1, 'enable_oauth' => 1, 'domain' => 'myapp.auth0.com', 'client_id' => 'a0-id'],
                12009,
            ],
            'redirect_uri missing'  => [
                [
                    'enabled'              => 1,
                    'enable_exchange_code' => 1,
                    'domain'               => 'myapp.auth0.com',
                    'client_id'            => 'a0-id',
                    'client_secret'        => 'a0-secret',
                ],
                12010,
            ],
        ];
    }

    #[DataProvider('validateThrowsProvider')]
    public function testValidateThrows(array $groupPost, int $expectedCode): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedCode);
        $this->make()->validate(['auth0' => $groupPost]);
    }
}
