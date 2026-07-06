<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Oauth;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\Oauth\GoogleOauthSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class GoogleOauthSettingsTest extends TestCase
{
    private function make(): GoogleOauthSettings
    {
        return new GoogleOauthSettings();
    }

    private function wpData(): WordPressDataInterface
    {
        $stub = $this->createStub(WordPressDataInterface::class);
        $stub->method('sanitizeTextField')->willReturnArgument(0);

        return $stub;
    }

    public function testGetGroup(): void
    {
        $this->assertSame('google', $this->make()->getGroup());
    }

    public function testGetName(): void
    {
        $this->assertSame('Google', $this->make()->getName());
    }

    public static function isExchangeIdTokenEnabledProvider(): array
    {
        return [
            'enabled'  => [['enable_exchange_id_token' => true], true],
            'disabled' => [['enable_exchange_id_token' => false], false],
            'missing'  => [[], false],
        ];
    }

    #[DataProvider('isExchangeIdTokenEnabledProvider')]
    public function testIsExchangeIdTokenEnabled(array $data, bool $expected): void
    {
        $this->assertSame($expected, $this->make()->withSettings($data)->isExchangeIdTokenEnabled());
    }

    public function testProcessPostIncludesEnableExchangeIdTokenField(): void
    {
        $post   = ['google' => ['enable_exchange_id_token' => '1']];
        $result = $this->make()->processPost($post, $this->wpData());

        $this->assertArrayHasKey('enable_exchange_id_token', $result);
        $this->assertSame(true, $result['enable_exchange_id_token']);
    }

    public function testProcessPostExchangeIdTokenDefaultsFalseWhenMissing(): void
    {
        $result = $this->make()->processPost(['google' => []], $this->wpData());

        $this->assertSame(false, $result['enable_exchange_id_token']);
    }

    public function testValidatePassesWhenNotEnabled(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate(['google' => []]);
    }

    public function testValidatePassesWhenAllValid(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate([
            'google' => [
                'enabled'       => 1,
                'enable_oauth'  => 1,
                'client_id'     => 'g-client-id',
                'client_secret' => 'g-client-secret',
            ],
        ]);
    }

    public function testValidatePassesWithOnlyExchangeIdTokenEnabled(): void
    {
        $this->expectNotToPerformAssertions();
        $this->make()->validate([
            'google' => [
                'enabled'                  => 1,
                'enable_exchange_id_token' => 1,
                'client_id'                => 'g-client-id',
                'client_secret'            => 'g-client-secret',
            ],
        ]);
    }

    // generateCode(PREFIX_APPLICATIONS=12, errorCode) = 12000 + errorCode
    public static function validateThrowsProvider(): array
    {
        return [
            'no features enabled'   => [['enabled' => 1], 12001],
            'client_id missing'     => [['enabled' => 1, 'enable_oauth' => 1], 12002],
            'client_secret missing' => [['enabled' => 1, 'enable_oauth' => 1, 'client_id' => 'g-id'], 12003],
            'redirect_uri missing'  => [
                ['enabled' => 1, 'enable_exchange_code' => 1, 'client_id' => 'g-id', 'client_secret' => 'g-secret'],
                12004,
            ],
        ];
    }

    #[DataProvider('validateThrowsProvider')]
    public function testValidateThrows(array $groupPost, int $expectedCode): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedCode);
        $this->make()->validate(['google' => $groupPost]);
    }
}
